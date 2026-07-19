<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$sourcePath = $root . '/storage/kamiqr-source.json';
$dbPath = $root . '/storage/database.sqlite';
$uploadRoot = $root . '/public/uploads/menu/kamiqr';
$publicUploadRoot = 'uploads/menu/kamiqr';

if (!is_file($sourcePath)) {
    fwrite(STDERR, "Missing source JSON: {$sourcePath}\n");
    exit(1);
}

if (!is_file($dbPath)) {
    fwrite(STDERR, "Missing SQLite database: {$dbPath}\n");
    exit(1);
}

$source = json_decode((string) file_get_contents($sourcePath), true);
if (!is_array($source) || empty($source['data'])) {
    fwrite(STDERR, "Invalid source JSON.\n");
    exit(1);
}

$data = $source['data'];
$assetsUrl = rtrim((string) ($source['assetsUrl'] ?? 'https://kamigroup.fra1.cdn.digitaloceanspaces.com/kami/prod'), '/');
$stats = [
    'sections' => 0,
    'categories' => 0,
    'items' => 0,
    'images_downloaded' => 0,
    'images_failed' => 0,
];

function text_value(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if (is_string($value) || is_numeric($value)) {
        return trim((string) $value);
    }

    if (!is_array($value)) {
        return '';
    }

    foreach (['RU', 'ru', 'KZ', 'EN', 'en'] as $key) {
        if (isset($value[$key]) && trim((string) $value[$key]) !== '') {
            return trim((string) $value[$key]);
        }
    }

    foreach ($value as $entry) {
        $text = text_value($entry);
        if ($text !== '') {
            return $text;
        }
    }

    return '';
}

function money_from_raw(mixed $raw): string
{
    $raw = (int) $raw;
    if ($raw <= 0) {
        return '';
    }

    $amount = (int) round($raw / 100);

    return number_format($amount, 0, '.', ' ') . ' ₸';
}

function price_string(array $prices): string
{
    $values = [];
    $primary = $prices['primary'] ?? null;
    if (is_array($primary)) {
        $value = money_from_raw($primary['price'] ?? 0);
        if ($value !== '') {
            $values[$value] = true;
        }
    }

    foreach (($prices['others'] ?? []) as $other) {
        if (!is_array($other)) {
            continue;
        }
        $value = money_from_raw($other['price'] ?? 0);
        if ($value !== '') {
            $values[$value] = true;
        }
    }

    return implode(' / ', array_keys($values));
}

function variant_labels(array $prices): array
{
    $labels = [];
    $primary = $prices['primary'] ?? null;
    if (is_array($primary)) {
        $label = text_value($primary['label'] ?? []);
        if ($label !== '') {
            $labels[$label] = true;
        }
    }

    foreach (($prices['others'] ?? []) as $other) {
        if (!is_array($other)) {
            continue;
        }
        $label = text_value($other['label'] ?? []);
        if ($label !== '') {
            $labels[$label] = true;
        }
    }

    return array_keys($labels);
}

function clean_inline_text(string $text): string
{
    $text = preg_replace('/[ \t]{2,}/u', ' ', $text) ?? $text;

    return trim($text);
}
function clean_description(string $description): string
{
    $description = trim($description);
    $description = preg_replace("/\r\n|\r/", "\n", $description) ?? $description;
    $description = preg_replace("/\n{3,}/", "\n\n", $description) ?? $description;

    return trim($description);
}

function local_asset_path(string $remoteFile, string $kind, string $assetsUrl, string $uploadRoot, string $publicUploadRoot, array &$stats): string
{
    $remoteFile = trim($remoteFile);
    if ($remoteFile === '') {
        return '';
    }

    $extension = strtolower(pathinfo($remoteFile, PATHINFO_EXTENSION)) ?: 'jpg';
    $base = pathinfo($remoteFile, PATHINFO_FILENAME);
    $subdir = $kind === 'sections' ? 'sections' : 'items';
    $localName = $kind === 'items' ? $base . '_thumb.' . $extension : $base . '.' . $extension;
    $localDir = $uploadRoot . '/' . $subdir;
    $localPath = $localDir . '/' . $localName;
    $publicPath = $publicUploadRoot . '/' . $subdir . '/' . $localName;

    if (is_file($localPath) && filesize($localPath) > 0) {
        return $publicPath;
    }

    if (!is_dir($localDir)) {
        mkdir($localDir, 0775, true);
    }

    $candidates = [];
    if ($kind === 'sections') {
        $candidates[] = $assetsUrl . '/menuSections/' . rawurlencode($remoteFile);
    } else {
        $candidates[] = $assetsUrl . '/menuItemThumbnails/' . rawurlencode($base . '_thumb.' . $extension);
        $candidates[] = $assetsUrl . '/menuItems/' . rawurlencode($remoteFile);
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'header' => "User-Agent: Mozilla/5.0 Codex menu importer\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    foreach ($candidates as $url) {
        $bytes = @file_get_contents($url, false, $context);
        if ($bytes === false || strlen($bytes) < 64) {
            continue;
        }
        file_put_contents($localPath, $bytes);
        $stats['images_downloaded']++;

        return $publicPath;
    }

    $stats['images_failed']++;

    return '';
}

function insert_menu_item(PDO $pdo, ?int $parentId, string $type, string $title, string $description, string $price, string $imagePath, int $sortOrder): int
{
    $stmt = $pdo->prepare('
        INSERT INTO menu_items
            (parent_id, item_type, title, description, price, image_path, sort_order, is_active)
        VALUES
            (:parent_id, :item_type, :title, :description, :price, :image_path, :sort_order, 1)
    ');
    $stmt->execute([
        'parent_id' => $parentId,
        'item_type' => $type,
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'image_path' => $imagePath,
        'sort_order' => $sortOrder,
    ]);

    return (int) $pdo->lastInsertId();
}

$backupPath = $root . '/storage/database.before-kamiqr-import-' . date('Ymd-His') . '.sqlite';
copy($dbPath, $backupPath);

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->beginTransaction();

try {
    $pdo->exec('DELETE FROM menu_items');
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'menu_items'");

    foreach (($data['tree']['sections'] ?? []) as $sectionIndex => $sectionNode) {
        $sectionId = (string) ($sectionNode['sectionId'] ?? '');
        $section = $data['sections'][$sectionId] ?? null;
        if (!is_array($section) || !empty($section['isHidden'])) {
            continue;
        }

        $sectionTitle = clean_inline_text(text_value($section['name'] ?? ''));
        if ($sectionTitle === '') {
            continue;
        }

        $sectionImage = local_asset_path((string) ($section['img'] ?? ''), 'sections', $assetsUrl, $uploadRoot, $publicUploadRoot, $stats);
        $sectionDbId = insert_menu_item(
            $pdo,
            null,
            'section',
            $sectionTitle,
            clean_description(text_value($section['desc'] ?? '')),
            '',
            $sectionImage,
            ($sectionIndex + 1) * 10
        );
        $stats['sections']++;

        foreach (($sectionNode['categories'] ?? []) as $categoryIndex => $categoryNode) {
            $categoryId = (string) ($categoryNode['categoryId'] ?? '');
            $category = $data['categories'][$categoryId] ?? null;
            if (!is_array($category) || !empty($category['isHidden'])) {
                continue;
            }

            $categoryTitle = clean_inline_text(text_value($category['name'] ?? ''));
            if ($categoryTitle === '') {
                continue;
            }

            $categoryItems = is_array($categoryNode['items'] ?? null) ? $categoryNode['items'] : [];
            if (!$categoryItems) {
                continue;
            }

            $categoryDbId = insert_menu_item(
                $pdo,
                $sectionDbId,
                'section',
                $categoryTitle,
                clean_description(text_value($category['desc'] ?? '')),
                '',
                '',
                ($categoryIndex + 1) * 10
            );
            $stats['categories']++;

            foreach ($categoryItems as $itemIndex => $itemRef) {
                $itemId = is_array($itemRef) ? (string) ($itemRef['itemId'] ?? '') : (string) $itemRef;
                $item = $data['items'][$itemId] ?? null;
                if (!is_array($item) || !empty($item['isHidden']) || !empty($item['isOff'])) {
                    continue;
                }

                $title = clean_inline_text(text_value($item['name'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $specs = is_array($item['specs'] ?? null) ? $item['specs'] : [];
                $prices = is_array($item['prices'] ?? null) ? $item['prices'] : [];
                $descriptionParts = [];
                $shortDescription = clean_description(text_value($specs['shortDesc'] ?? ''));
                $fullDescription = clean_description(text_value($specs['fullDesc'] ?? ''));
                if ($shortDescription !== '') {
                    $descriptionParts[] = $shortDescription;
                }
                if ($fullDescription !== '' && $fullDescription !== $shortDescription) {
                    $descriptionParts[] = $fullDescription;
                }

                $labels = variant_labels($prices);
                if (count($labels) > 1) {
                    $descriptionParts[] = 'Варианты: ' . implode(', ', $labels);
                }

                $imagePath = local_asset_path((string) ($item['mainImg'] ?? ''), 'items', $assetsUrl, $uploadRoot, $publicUploadRoot, $stats);
                insert_menu_item(
                    $pdo,
                    $categoryDbId,
                    'item',
                    $title,
                    implode("\n", array_filter($descriptionParts)),
                    price_string($prices),
                    $imagePath,
                    ($itemIndex + 1) * 10
                );
                $stats['items']++;
            }
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    fwrite(STDERR, "Database backup is at: {$backupPath}\n");
    exit(1);
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();

echo json_encode([
    'ok' => true,
    'backup' => $backupPath,
    'total_menu_items' => $total,
    'stats' => $stats,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
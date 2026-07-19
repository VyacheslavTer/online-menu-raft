<?php

declare(strict_types=1);

final class MenuRepository
{
    public const MAX_DEPTH = 4;

    public function __construct(private PDO $pdo, private string $locale = 'ru')
    {
        $this->locale = normalize_locale($locale);
    }

    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM menu_items';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY parent_id IS NOT NULL, parent_id, sort_order, title';

        return $this->localizeRows($this->pdo->query($sql)->fetchAll());
    }

    public function children(?int $parentId, bool $activeOnly = true): array
    {
        if ($parentId === null) {
            $sql = 'SELECT * FROM menu_items WHERE parent_id IS NULL';
            $params = [];
        } else {
            $sql = 'SELECT * FROM menu_items WHERE parent_id = :parent_id';
            $params = ['parent_id' => $parentId];
        }

        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }

        $sql .= ' ORDER BY sort_order, title';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $this->localizeRows($stmt->fetchAll());
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM menu_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $item = $stmt->fetch();

        return $item ? $this->localizeItem($item) : null;
    }

    public function breadcrumbs(?int $id): array
    {
        $trail = [];
        while ($id !== null) {
            $item = $this->find($id);
            if (!$item) {
                break;
            }
            $trail[] = $item;
            $id = $item['parent_id'] !== null ? (int) $item['parent_id'] : null;
        }

        return array_reverse($trail);
    }

    public function tree(bool $activeOnly = false): array
    {
        $items = $this->all($activeOnly);
        $byParent = [];
        foreach ($items as $item) {
            $key = $item['parent_id'] === null ? 'root' : (string) $item['parent_id'];
            $byParent[$key][] = $item;
        }

        return $this->buildTree($byParent, 'root', 1);
    }

    public function parentOptions(?int $excludeId = null): array
    {
        $tree = $this->tree(false);
        $options = [];
        $this->flattenOptions($tree, $options, $excludeId);

        return $options;
    }

    public function save(array $data): int
    {
        $id = !empty($data['id']) ? (int) $data['id'] : null;
        $parentId = !empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($id !== null && $parentId !== null && $this->isDescendant($parentId, $id)) {
            throw new InvalidArgumentException('Нельзя вложить раздел внутрь самого себя.');
        }

        $parentDepth = $parentId !== null ? $this->depth($parentId) : 0;
        $subtreeHeight = $id !== null ? $this->subtreeHeight($id) : 1;
        if ($parentDepth + $subtreeHeight > self::MAX_DEPTH) {
            throw new InvalidArgumentException('Максимальная вложенность меню - 4 уровня.');
        }

        $payload = [
            'parent_id' => $parentId,
            'item_type' => $data['item_type'] ?? 'item',
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'title_kz' => trim((string) ($data['title_kz'] ?? '')),
            'description_kz' => trim((string) ($data['description_kz'] ?? '')),
            'title_en' => trim((string) ($data['title_en'] ?? '')),
            'description_en' => trim((string) ($data['description_en'] ?? '')),
            'price' => trim((string) ($data['price'] ?? '')),
            'image_path' => trim((string) ($data['image_path'] ?? '')),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
        ];

        if ($payload['title'] === '') {
            throw new InvalidArgumentException('Название обязательно.');
        }

        if ($id === null) {
            $stmt = $this->pdo->prepare('
                INSERT INTO menu_items
                    (parent_id, item_type, title, description, title_kz, description_kz, title_en, description_en, price, image_path, sort_order, is_active)
                VALUES
                    (:parent_id, :item_type, :title, :description, :title_kz, :description_kz, :title_en, :description_en, :price, :image_path, :sort_order, :is_active)
            ');
            $stmt->execute($payload);

            return (int) $this->pdo->lastInsertId();
        }

        $payload['id'] = $id;
        $stmt = $this->pdo->prepare('
            UPDATE menu_items
            SET parent_id = :parent_id,
                item_type = :item_type,
                title = :title,
                description = :description,
                title_kz = :title_kz,
                description_kz = :description_kz,
                title_en = :title_en,
                description_en = :description_en,
                price = :price,
                image_path = :image_path,
                sort_order = :sort_order,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ');
        $stmt->execute($payload);

        return $id;
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function depth(int $id): int
    {
        $depth = 0;
        $seen = [];
        while ($id > 0 && !isset($seen[$id])) {
            $seen[$id] = true;
            $item = $this->find($id);
            if (!$item) {
                break;
            }
            $depth++;
            $id = $item['parent_id'] !== null ? (int) $item['parent_id'] : 0;
        }

        return $depth;
    }

    private function localizeRows(array $rows): array
    {
        return array_map(fn (array $item): array => $this->localizeItem($item), $rows);
    }

    private function localizeItem(array $item): array
    {
        if ($this->locale === 'ru') {
            return $item;
        }

        foreach (['title', 'description'] as $field) {
            $localized = trim((string) ($item[$field . '_' . $this->locale] ?? ''));
            if ($localized !== '') {
                $item[$field] = $localized;
            }
        }

        return $item;
    }

    private function buildTree(array $byParent, string $parentKey, int $depth): array
    {
        $items = $byParent[$parentKey] ?? [];
        foreach ($items as &$item) {
            $item['depth'] = $depth;
            $item['children'] = $this->buildTree($byParent, (string) $item['id'], $depth + 1);
        }

        return $items;
    }

    private function flattenOptions(array $nodes, array &$options, ?int $excludeId): void
    {
        foreach ($nodes as $node) {
            if ($excludeId !== null && (int) $node['id'] === $excludeId) {
                continue;
            }

            if ((int) $node['depth'] < self::MAX_DEPTH) {
                $options[] = [
                    'id' => (int) $node['id'],
                    'title' => str_repeat('— ', max(0, (int) $node['depth'] - 1)) . $node['title'],
                ];
            }

            if (!empty($node['children'])) {
                $this->flattenOptions($node['children'], $options, $excludeId);
            }
        }
    }

    private function subtreeHeight(int $id): int
    {
        $height = 1;
        foreach ($this->children($id, false) as $child) {
            $height = max($height, 1 + $this->subtreeHeight((int) $child['id']));
        }

        return $height;
    }

    private function isDescendant(int $candidateId, int $parentId): bool
    {
        while ($candidateId > 0) {
            if ($candidateId === $parentId) {
                return true;
            }
            $item = $this->find($candidateId);
            if (!$item || $item['parent_id'] === null) {
                return false;
            }
            $candidateId = (int) $item['parent_id'];
        }

        return false;
    }
}


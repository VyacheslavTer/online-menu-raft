<?php

declare(strict_types=1);

function config(?string $path = null, mixed $default = null): mixed
{
    $value = $GLOBALS['config'] ?? [];

    if ($path === null || $path === '') {
        return $value;
    }

    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function supported_locales(): array
{
    return [
        'ru' => ['label' => 'RU', 'html' => 'ru', 'name' => 'Русский'],
        'kz' => ['label' => 'KZ', 'html' => 'kk', 'name' => 'Қазақша'],
        'en' => ['label' => 'EN', 'html' => 'en', 'name' => 'English'],
    ];
}

function normalize_locale(?string $locale): string
{
    $locale = strtolower(trim((string) $locale));
    if ($locale === 'kk') {
        $locale = 'kz';
    }

    return array_key_exists($locale, supported_locales()) ? $locale : 'ru';
}

function current_locale(): string
{
    return normalize_locale($_GET['lang'] ?? 'ru');
}

function localized_setting(Settings $settings, string $key, string $locale, string $default = ''): string
{
    $locale = normalize_locale($locale);
    if ($locale !== 'ru') {
        $localized = trim($settings->get($key . '_' . $locale));
        if ($localized !== '') {
            return $localized;
        }
    }

    return $settings->get($key, $default);
}

function menu_url(?int $id, string $locale, string $fragment = '', bool $forceLocale = false): string
{
    $params = [];
    if ($id !== null && $id > 0) {
        $params['id'] = $id;
    }

    $locale = normalize_locale($locale);
    if ($locale !== 'ru' || $forceLocale) {
        $params['lang'] = $locale;
    }

    return '/' . ($params ? '?' . http_build_query($params) : '') . $fragment;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $given = $_POST['_csrf'] ?? '';
    $known = $_SESSION['_csrf'] ?? '';

    if (!is_string($given) || !is_string($known) || !hash_equals($known, $given)) {
        http_response_code(419);
        exit('Сессия устарела. Обновите страницу и повторите действие.');
    }
}

function asset_url(?string $path): string
{
    if (!$path) {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $base = rtrim((string) config('app.base_url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

function external_url(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }

    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return '';
    }

    return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : '';
}

function money(?string $price): string
{
    $price = trim((string) $price);
    if ($price === '') {
        return '';
    }

    return $price;
}

function slugify(string $text): string
{
    $text = trim($text);
    $map = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];
    $text = strtr(mb_strtolower($text), $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'item';
}

function selected(mixed $a, mixed $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function checked(mixed $value): string
{
    return !empty($value) ? ' checked' : '';
}


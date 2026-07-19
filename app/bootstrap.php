<?php

declare(strict_types=1);

$configFile = __DIR__ . '/config.local.php';
if (!is_file($configFile)) {
    $configFile = __DIR__ . '/config.example.php';
}

$GLOBALS['config'] = require $configFile;

date_default_timezone_set((string) ($GLOBALS['config']['app']['timezone'] ?? 'UTC'));

if (!empty($GLOBALS['config']['app']['dev_mode'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Schema.php';
require_once __DIR__ . '/SeedData.php';
require_once __DIR__ . '/Settings.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/MenuRepository.php';
require_once __DIR__ . '/Upload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = Database::connect(config('db'));
    Schema::ensure($pdo, (string) config('db.driver', 'sqlite'), $GLOBALS['config']);

    return $pdo;
}


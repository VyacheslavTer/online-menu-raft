<?php

declare(strict_types=1);

final class Database
{
    public static function connect(array $config): PDO
    {
        $driver = (string) ($config['driver'] ?? 'sqlite');

        if ($driver === 'sqlite') {
            $path = (string) ($config['sqlite_path'] ?? __DIR__ . '/../storage/database.sqlite');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } elseif ($driver === 'mysql') {
            $mysql = $config['mysql'] ?? [];
            $charset = (string) ($mysql['charset'] ?? 'utf8mb4');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $mysql['host'] ?? 'localhost',
                (int) ($mysql['port'] ?? 3306),
                $mysql['database'] ?? '',
                $charset
            );
            $pdo = new PDO($dsn, (string) ($mysql['username'] ?? ''), (string) ($mysql['password'] ?? ''));
            $pdo->exec('SET NAMES ' . $charset);
        } else {
            throw new RuntimeException('Unsupported DB driver: ' . $driver);
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        return $pdo;
    }
}


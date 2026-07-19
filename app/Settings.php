<?php

declare(strict_types=1);

final class Settings
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $rows = $this->pdo->query('SELECT `key`, `value` FROM settings')->fetchAll();
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return $settings;
    }

    public function get(string $key, string $default = ''): string
    {
        $stmt = $this->pdo->prepare('SELECT `value` FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    public function setMany(array $values): void
    {
        if ((string) config('db.driver', 'sqlite') === 'sqlite') {
            $sql = '
                INSERT INTO settings (`key`, `value`)
                VALUES (:key, :value)
                ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.`value`
            ';
        } else {
            $sql = '
                INSERT INTO settings (`key`, `value`)
                VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
            ';
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($values as $key => $value) {
            $stmt->execute(['key' => $key, 'value' => (string) $value]);
        }
    }
}
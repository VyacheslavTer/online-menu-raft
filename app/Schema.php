<?php

declare(strict_types=1);

final class Schema
{
    public static function ensure(PDO $pdo, string $driver, array $config): void
    {
        if ($driver === 'mysql') {
            self::mysql($pdo);
        } else {
            self::sqlite($pdo);
        }

        self::ensureMenuTranslationColumns($pdo, $driver);
        self::seedSettings($pdo, $config);
        self::seedAdmin($pdo, $config);
        self::seedMenu($pdo);
    }

    private static function mysql(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                `key` VARCHAR(80) NOT NULL PRIMARY KEY,
                `value` TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL UNIQUE,
                name VARCHAR(120) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL,
                ip_address VARCHAR(64) NOT NULL,
                attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_login_attempts_lookup (email, ip_address, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_password_reset_token (token_hash),
                INDEX idx_password_reset_user (user_id),
                CONSTRAINT fk_password_reset_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS menu_items (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                parent_id INT UNSIGNED NULL,
                item_type VARCHAR(24) NOT NULL DEFAULT 'item',
                title VARCHAR(190) NOT NULL,
                description TEXT NULL,
                title_kz VARCHAR(190) NULL,
                description_kz TEXT NULL,
                title_en VARCHAR(190) NULL,
                description_en TEXT NULL,
                price VARCHAR(80) NULL,
                image_path VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_menu_parent (parent_id),
                CONSTRAINT fk_menu_parent
                    FOREIGN KEY (parent_id) REFERENCES menu_items(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private static function sqlite(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS settings (
                key TEXT NOT NULL PRIMARY KEY,
                value TEXT NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_lookup ON login_attempts(email, ip_address, attempted_at)');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                token_hash TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                used_at TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_token ON password_reset_tokens(token_hash)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_password_reset_user ON password_reset_tokens(user_id)');

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS menu_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                parent_id INTEGER NULL,
                item_type TEXT NOT NULL DEFAULT 'item',
                title TEXT NOT NULL,
                description TEXT NULL,
                title_kz TEXT NULL,
                description_kz TEXT NULL,
                title_en TEXT NULL,
                description_en TEXT NULL,
                price TEXT NULL,
                image_path TEXT NULL,
                sort_order INTEGER NOT NULL DEFAULT 0,
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (parent_id) REFERENCES menu_items(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_menu_parent ON menu_items(parent_id)');
    }

    private static function seedSettings(PDO $pdo, array $config): void
    {
        $settings = [
            'site_name' => (string) ($config['app']['name'] ?? 'raft'),
            'site_name_kz' => (string) ($config['app']['name'] ?? 'raft'),
            'site_name_en' => (string) ($config['app']['name'] ?? 'raft'),
            'site_subtitle' => (string) ($config['app']['subtitle'] ?? 'Онлайн меню'),
            'site_subtitle_kz' => 'Онлайн мәзір',
            'site_subtitle_en' => 'Online menu',
            'contacts' => '',
            'contacts_kz' => '',
            'contacts_en' => '',
            'working_hours' => '',
            'working_hours_kz' => '',
            'working_hours_en' => '',
            'instagram_url' => '',
            'telegram_url' => '',
            'whatsapp_url' => '',
            'favicon_path' => '',
        ];

        $stmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (:key, :value)');
        foreach ($settings as $key => $value) {
            if (self::settingExists($pdo, $key)) {
                continue;
            }
            $stmt->execute(['key' => $key, 'value' => $value]);
        }
    }

    private static function seedAdmin(PDO $pdo, array $config): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
            return;
        }

        $admin = $config['admin'] ?? [];
        $email = (string) ($admin['email'] ?? 'admin@example.kz');
        $password = (string) ($admin['password'] ?? '');
        $name = (string) ($admin['name'] ?? 'Администратор');

        if ($email === '' || $password === '') {
            return;
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (email, name, password_hash, is_active)
            VALUES (:email, :name, :hash, 1)
        ');
        $stmt->execute([
            'email' => $email,
            'name' => $name,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }

    private static function seedMenu(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM menu_items')->fetchColumn() > 0) {
            return;
        }

        self::insertSeedItems($pdo, SeedData::items(), null);
    }

    private static function insertSeedItems(PDO $pdo, array $items, ?int $parentId): void
    {
        $stmt = $pdo->prepare('
            INSERT INTO menu_items
                (parent_id, item_type, title, description, price, image_path, sort_order, is_active)
            VALUES
                (:parent_id, :item_type, :title, :description, :price, :image_path, :sort_order, :is_active)
        ');

        foreach ($items as $index => $item) {
            $stmt->execute([
                'parent_id' => $parentId,
                'item_type' => $item['type'] ?? 'item',
                'title' => $item['title'],
                'description' => $item['description'] ?? '',
                'price' => $item['price'] ?? '',
                'image_path' => $item['image'] ?? '',
                'sort_order' => $item['sort'] ?? (($index + 1) * 10),
                'is_active' => isset($item['active']) ? (int) (bool) $item['active'] : 1,
            ]);

            $id = (int) $pdo->lastInsertId();
            if (!empty($item['children']) && is_array($item['children'])) {
                self::insertSeedItems($pdo, $item['children'], $id);
            }
        }
    }

    private static function ensureMenuTranslationColumns(PDO $pdo, string $driver): void
    {
        $columns = [
            'title_kz' => $driver === 'mysql' ? 'VARCHAR(190) NULL' : 'TEXT NULL',
            'description_kz' => 'TEXT NULL',
            'title_en' => $driver === 'mysql' ? 'VARCHAR(190) NULL' : 'TEXT NULL',
            'description_en' => 'TEXT NULL',
        ];

        if ($driver === 'mysql') {
            $existing = [];
            foreach ($pdo->query('SHOW COLUMNS FROM menu_items')->fetchAll() as $row) {
                $existing[(string) $row['Field']] = true;
            }
        } else {
            $existing = [];
            foreach ($pdo->query('PRAGMA table_info(menu_items)')->fetchAll() as $row) {
                $existing[(string) $row['name']] = true;
            }
        }

        foreach ($columns as $column => $definition) {
            if (isset($existing[$column])) {
                continue;
            }
            $pdo->exec('ALTER TABLE menu_items ADD COLUMN ' . $column . ' ' . $definition);
        }
    }

    private static function settingExists(PDO $pdo, string $key): bool
    {
        $stmt = $pdo->prepare('SELECT 1 FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute(['key' => $key]);

        return (bool) $stmt->fetchColumn();
    }
}


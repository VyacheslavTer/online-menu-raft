# raft online menu

PHP/MySQL QR-menu for PSKZ shared hosting.

## Local run

Install PHP 8.x with `pdo_sqlite`, then run:

```powershell
.\scripts\start-dev.ps1
```

Open:

- menu: `http://127.0.0.1:8765`
- admin: `http://127.0.0.1:8765/manage-raft/`

Default demo login comes from `app/config.example.php`:

- email: `admin@example.kz`
- password: `change-me-now`

## Production setup on PSKZ

1. Create a subdomain in Plesk and point its document root to `public`.
2. Create a MySQL/MariaDB database and user.
3. Copy `app/config.local.example.php` to `app/config.local.php`.
4. Fill in database credentials and a strong admin password.
5. Upload files to hosting.
6. Open `/manage-raft/`. On first request the app creates tables, admin user, and draft menu data.

You can also import `sql/schema.mysql.sql` manually in phpMyAdmin, but it is not required.

## Deploy

Copy `deploy.config.example.ps1` to `deploy.config.local.ps1`, fill FTP/FTPS credentials, then:

```powershell
.\scripts\deploy.ps1 -DryRun
.\scripts\deploy.ps1
```

The deploy script skips local secrets, SQLite storage, and live uploaded dish photos.

## Content workflow

- Dishes, prices, categories, descriptions, visibility, sorting, and photos are changed in `/manage-raft/`.
- Code/design changes are uploaded with `scripts/deploy.ps1`.
- Menu supports up to 4 levels through the `parent_id` tree.

## SQLite on Windows

The local config in `app/config.local.php` stores SQLite in `storage/database.sqlite`.

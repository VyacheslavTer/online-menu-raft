# Handoff: raft online menu

Project was started in `C:\repa\Онлайн меню` and should be continued after renaming/moving the folder to an ASCII path, for example `C:\repa\online-menu`.

## Current state

- PHP 8.3 installed through winget.
- PHP extensions enabled in the installed `php.ini`: `pdo_sqlite`, `sqlite3`, `mbstring`, `fileinfo`, `gd`, `openssl`, `mysqli`, `pdo_mysql`.
- App is plain PHP for PSKZ shared hosting: public menu + admin panel.
- Public menu: `public/index.php`.
- Admin panel: `public/manage-raft/index.php`.
- Admin login in local config:
  - `admin@example.kz`
  - `change-me-now`
- Seed menu data is in `app/SeedData.php`, drafted from the provided raft menu photos.
- MySQL schema for PSKZ: `sql/schema.mysql.sql`.
- FTP deploy draft: `scripts/deploy.ps1`.
- Local start script: `scripts/start-dev.ps1`.

## Important

`app/config.local.php` stores SQLite in `storage/database.sqlite`. The previous temporary `%TEMP%` database was copied back into `storage/database.sqlite` after moving the project to the ASCII path.

Then run:

```powershell
.\scripts\start-dev.ps1
```

Expected local URLs:

- `http://127.0.0.1:8765/`
- `http://127.0.0.1:8765/manage-raft/`

## Last verified before move

- PHP server was running successfully on `127.0.0.1:8765`.
- Public menu returned HTTP 200 and showed `Основное меню` / `Барное меню`.
- Admin login worked and showed `Структура меню`.
- PHP server was stopped before folder rename.
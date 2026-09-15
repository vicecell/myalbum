# Talent Database Mobile Admin

Mobile-first PHP-native admin app for managing a private talent database. See `CLAUDE_Talent_Database_PHP_Native_Blueprint.md` for full spec.

## Status

All 6 MVP milestones complete: project foundation, authentication (session-based, CSRF, password hashing), city CRUD, talent CRUD (search + city filter + rate), multi-photo upload (primary photo, delete, manual crop), dashboard summary + settings page. Deletes are soft (`deleted_at`), not physical row removal.

Storage/DB history (in order): ImgBB → Supabase Storage → Cloudflare R2 (never deployed) → ImgBB again → **Cloudinary** (current) for photos; local MySQL → Supabase Postgres (via PostgREST, since production's CentOS 7 `libpq` was too old for Supabase's required SCRAM auth) → **local MySQL/MariaDB via plain PDO** (current) for the database, after Supabase's account got billing-restricted. The app now has zero dependency on Supabase. `app/config/database.php` exposes a plain `db(): PDO` singleton; `app/helpers/upload.php` talks to Cloudinary's signed upload API + builds on-demand transform URLs (`cloudinary_transform_url()`) for thumbnails/crop-source — no separate thumb upload needed.

Seed the admin account once with:
```bash
php scripts/setup_admin.php <username> <password>
```

## Requirements

- PHP 8+
- `pdo_mysql`, `curl`, `gd` (freetype + webp) extensions
- A local/self-hosted MySQL or MariaDB server
- A Cloudinary account (free tier, no card required) for photo storage

## Setup

1. Copy `.env.example` to `.env` and fill in:
   - `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` — your MySQL/MariaDB connection.
   - `CLOUDINARY_URL` — from the Cloudinary dashboard home (`cloudinary://API_KEY:API_SECRET@CLOUD_NAME`).
   - `WATERMARK_TEXT` — text burned into the bottom-right corner of every uploaded photo's full-size copy (GD + bundled font at `app/assets/fonts/watermark.ttf`); thumbnails are never watermarked.
2. Create the database and apply the schema:
   ```bash
   mysql -u root -p -e "CREATE DATABASE talent_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p talent_database < database/schema.sql
   ```
3. Run the app with PHP's built-in server (from project root):
   ```bash
   php -S localhost:8000 -t public
   ```
4. Open `http://localhost:8000/` in a browser (redirects to the login page).
5. Visit `http://localhost:8000/_dbcheck.php` to verify the database connection (only works when `APP_ENV=local`).

`admin/` and `api/` live under `public/` (`public/admin/`, `public/api/`) specifically so a real Apache/Nginx vhost with its document root pointed at `public/` serves them with zero extra rewrite rules — no `router.php`/custom routing needed anywhere.

## Schema changes / migrations

`database/schema.sql` is the full, current schema — always used as-is for a brand new install (step 2 above).

For an **existing** install that needs to catch up to a schema change, numbered incremental scripts live in `database/migrations/` (e.g. `0001_add_photo_file_size_bytes.sql`) and `database/schema.sql` is always kept in sync with the same change (new installs never need to run migrations — they get everything from `schema.sql` directly). Apply pending migrations with:
```bash
php scripts/migrate.php
```
This tracks what's already been applied in a `schema_migrations` table, so it's safe to run repeatedly (already-applied migrations are skipped). Run it after every `git pull` that touches `database/schema.sql` or `database/migrations/`.

Each migration file must contain exactly one SQL statement (the runner executes files as single `PDO::exec()` calls — no multi-statement support).

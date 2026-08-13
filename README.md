# Talent Database Mobile Admin

Mobile-first PHP-native admin app for managing a private talent database. See `CLAUDE_Talent_Database_PHP_Native_Blueprint.md` for full spec.

## Status

All 6 MVP milestones complete: project foundation, authentication (session-based, CSRF, password hashing), city CRUD, talent CRUD (search + city filter + rate), multi-photo upload via Supabase Storage (primary photo, delete), dashboard summary + settings page. Deletes are soft (`deleted_at`), not physical row removal.

Photo hosting was switched from ImgBB to Supabase Storage, and the database was switched from local MySQL to Supabase Postgres (both deviate from the original blueprint spec, done at user request). Needs a public storage bucket and a Postgres connection — see Setup below.

Seed the admin account once with:
```bash
php scripts/setup_admin.php <username> <password>
```
Then import `database/seed.sql` for the default city list (optional).

## Requirements

- PHP 8+
- `pdo_pgsql`, `gd` (freetype + webp), `curl` extensions
- A Supabase project (Postgres database + Storage)

## Setup

1. Copy `.env.example` to `.env` and fill in:
   - `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASS` — Supabase Postgres **connection pooler** credentials (Project → Connect → Direct connection tab has a pooler toggle; direct/IPv6-only connections often aren't reachable from IPv4-only networks, so the pooler — `aws-0-<region>.pooler.supabase.com:6543`, user `postgres.<project-ref>` — is the reliable choice).
   - `SUPABASE_URL` / `SUPABASE_SERVICE_KEY` / `SUPABASE_BUCKET` — Storage, from Project Settings → API (`service_role` key — server-side only, never expose to frontend).
   - `WATERMARK_TEXT` — text burned into the bottom-right corner of every uploaded photo (GD + bundled font at `app/assets/fonts/watermark.ttf`).

   The storage bucket must exist and be public; create it once with:
   ```bash
   curl -X POST "$SUPABASE_URL/storage/v1/bucket" \
     -H "Authorization: Bearer $SUPABASE_SERVICE_KEY" \
     -H "apikey: $SUPABASE_SERVICE_KEY" \
     -H "Content-Type: application/json" \
     -d '{"id":"talent-photos","name":"talent-photos","public":true}'
   ```
2. Apply the schema (`database/schema.sql` is PostgreSQL DDL) against the Supabase database — e.g. via the SQL Editor in the Supabase dashboard, or `psql "$DATABASE_URL" -f database/schema.sql`.
3. Run the app with PHP's built-in server (from project root), using `router.php` so `/admin/*` and `/api/*` (which live outside `public/`, per the folder structure) resolve correctly:
   ```bash
   php -S localhost:8000 -t public router.php
   ```
4. Open `http://localhost:8000/` in a browser (redirects to the login page).
5. Visit `http://localhost:8000/_dbcheck.php` to verify the database connection (only works when `APP_ENV=local`).

Note: this project assumes `public/` is served as the web root via the command above, with `router.php` handling `/admin/*` and `/api/*`. For a real Apache/Nginx deployment, either point the vhost's document root at `public/` and add rewrite rules for `/admin` and `/api` to the project root, or move those directories under `public/`.

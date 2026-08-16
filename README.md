# Talent Database Mobile Admin

Mobile-first PHP-native admin app for managing a private talent database. See `CLAUDE_Talent_Database_PHP_Native_Blueprint.md` for full spec.

## Status

All 6 MVP milestones complete: project foundation, authentication (session-based, CSRF, password hashing), city CRUD, talent CRUD (search + city filter + rate), multi-photo upload via Supabase Storage (primary photo, delete), dashboard summary + settings page. Deletes are soft (`deleted_at`), not physical row removal.

Photo hosting was switched from ImgBB to Supabase Storage, the database was switched from local MySQL to Supabase Postgres, and — after production (CentOS 7) turned out to have a system `libpq` too old for Supabase's required SCRAM auth, with no upgrade path left for that OS — all database access was rewritten to go through Supabase's PostgREST HTTP API instead of a native Postgres connection (`app/config/database.php`'s `supabase_rest()`/`supabase_rest_count()`). All of this deviates from the original blueprint spec, done at user request. Needs a public storage bucket — see Setup below. No native Postgres client (`pdo_pgsql`, `psql`) is needed anywhere anymore, only `curl`.

Seed the admin account once with:
```bash
php scripts/setup_admin.php <username> <password>
```
Then import `database/seed.sql` for the default city list (optional).

## Requirements

- PHP 8+
- `curl`, `gd` (freetype + webp) extensions
- A Supabase project (Postgres database + Storage)

## Setup

1. Copy `.env.example` to `.env` and fill in:
   - `SUPABASE_URL` / `SUPABASE_SERVICE_KEY` / `SUPABASE_BUCKET` — from Project Settings → API (`service_role` key — server-side only, never expose to frontend; used for both Storage uploads and the PostgREST Data API).
   - `WATERMARK_TEXT` — text burned into the bottom-right corner of every uploaded photo (GD + bundled font at `app/assets/fonts/watermark.ttf`).

   The storage bucket must exist and be public; create it once with:
   ```bash
   curl -X POST "$SUPABASE_URL/storage/v1/bucket" \
     -H "Authorization: Bearer $SUPABASE_SERVICE_KEY" \
     -H "apikey: $SUPABASE_SERVICE_KEY" \
     -H "Content-Type: application/json" \
     -d '{"id":"talent-photos","name":"talent-photos","public":true}'
   ```
2. Apply the schema (`database/schema.sql` is PostgreSQL DDL, includes the `set_primary_photo` RPC function) against the Supabase database via the SQL Editor in the Supabase dashboard (no local Postgres client needed).
3. Run the app with PHP's built-in server (from project root), using `router.php` so `/admin/*` and `/api/*` (which live outside `public/`, per the folder structure) resolve correctly:
   ```bash
   php -S localhost:8000 -t public router.php
   ```
4. Open `http://localhost:8000/` in a browser (redirects to the login page).
5. Visit `http://localhost:8000/_dbcheck.php` to verify the database connection (only works when `APP_ENV=local`).

Note: this project assumes `public/` is served as the web root via the command above, with `router.php` handling `/admin/*` and `/api/*`. For a real Apache/Nginx deployment, either point the vhost's document root at `public/` and add rewrite rules for `/admin` and `/api` to the project root, or move those directories under `public/`.

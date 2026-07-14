# Plan — 09 Deployment & Infrastructure

## Local development (owner's machine)
- **Stack:** PHP 8.2+, Composer, Node 20+, MariaDB via phpMyAdmin (XAMPP/Laragon-style).
- **DB:** create empty `pluto_league` database in phpMyAdmin; Laravel migrations build all tables.
- **.env:** `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=pluto_league`, `DB_USERNAME=root`, `DB_PASSWORD=` (local default).
- **Run:** `php artisan serve` + `npm run dev` (Vite HMR). Migrate + seed: `php artisan migrate --seed`.
- **MariaDB safeguard:** `Schema::defaultStringLength(191)` in `AppServiceProvider`; `utf8mb4`.

## Build
- `npm run build` produces production Vite assets; `php artisan optimize` for prod caches.

## Deployment (when ready)
- Standard Laravel deploy to a VPS or shared host with PHP 8.2 + MariaDB.
- Steps: pull, `composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`, cache config/routes/views, set storage symlink.
- Aligns with the owner's existing Laravel deployment experience (Forge-style or manual). Coolify/pull-based CI/CD is an option consistent with prior projects.

## Environments
- `local` (dev) and `production`. A `staging` is optional.
- Secrets only in server `.env`; never committed.

## Backups
- Regular MariaDB dump of `pluto_league` (cron/mysqldump). Cadence is an owner ops decision (flagged in NFR).

## Open decisions / revisit later
- Final hosting target (VPS vs shared) and whether to wire pull-based CI/CD now or post-v1.
- Queue driver for Phase-2 notifications (database queue likely).

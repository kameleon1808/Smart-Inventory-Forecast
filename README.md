# Smart Inventory + Forecast

Laravel 12 scaffolding with Breeze (Livewire), Vite, and a modular-ready folder layout (`app/Domain`, `app/Services`, `app/Jobs`, `app/Policies`, `app/Http`).

## Requirements
- PHP 8.3+, Composer, and Node.js 20+ with npm
- SQLite (default) or another database supported by Laravel

## Quick start
1) `cp .env.example .env` and set any overrides (defaults use `database/database.sqlite`).
2) `composer install`  
3) `php artisan key:generate`  
4) `touch database/database.sqlite` (if it does not exist)  
5) `php artisan migrate --seed`  
6) `npm install` and then `npm run dev` (for HMR) or `npm run build` (for production assets).  
7) `php artisan serve` and visit `http://localhost:8000`.

## Auth
- Breeze (Livewire) provides login, registration, password reset, email verification, profile update, and logout flows.
- Seeded user: `demo@example.com` / `password` (already email-verified).
- Mail is logged by default (`MAIL_MAILER=log`). For new signups, grab verification links from `storage/logs/laravel.log` or point mailer to your provider.

## Tooling
- Format: `vendor/bin/pint`
- Static analysis: `vendor/bin/phpstan analyse` (baseline: `phpstan-baseline.neon`)
- Tests: `php artisan test`

## Notes
- Default DB is SQLite. To use MySQL/PostgreSQL, set the `DB_*` variables in `.env` and rerun `php artisan migrate --seed`.
- Vite assets are built into `public/build`. Rebuild with `npm run build` after frontend changes.
- Queue, cache, and session drivers default to database for easy local use. Jobs/policies/services folders are ready for future modules.

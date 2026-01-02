# Smart Inventory + Forecast

Inventory, procurement, recipes, and analytics hub built with Laravel 12 (Livewire Breeze) plus a Python FastAPI forecast microservice. Supports multi-location context, RBAC, stock ledger, counts, recipes/normatives, menu usage, expected/variance reporting, procurement (suggestions, POs, receiving), forecasts, anomalies/alerts, CSV import/export, audit log, and period locks.

## Highlights
- Inventory & stock: items, units/conversions, stock ledger (receipts, waste, internal use, adjustments), stock counts.
- Menu & consumption: recipes/normatives with versions, menu usage entry, expected consumption, variance reports.
- Procurement: suggestions, purchase orders with approval, receiving into stock.
- Forecasting: baseline FastAPI service, scheduled train/predict jobs, UI to generate/view forecasts.
- Governance: anomalies/alerts, audit log, period lock, RBAC by location, CSV import/export.

## Tech stack
- PHP 8.3+, Laravel 12 (Livewire Breeze), Vite/Tailwind
- SQLite by default (Laravel DB compatible)
- Queue: database
- Python 3.11+ FastAPI forecast-service (separate)

## Local setup
Prereqs: PHP 8.3+, Composer, Node 20+, npm, Python 3.11+, sqlite3.

1) Clone & install  
```bash
cp .env.example .env
composer install
npm install
```
2) App key & DB  
```bash
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```
3) Frontend dev server  
```bash
npm run dev
```
4) Laravel app  
```bash
php artisan serve
```
5) Queue worker (for imports, forecasts, anomaly scans, etc.)  
```bash
php artisan queue:work
```
6) Scheduler (cron)  
Add to crontab: `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Forecast-service
Located in `forecast-service/`.
```bash
cd forecast-service
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python -m pytest   # optional
uvicorn forecast_service.main:app --reload --port 9000
```
Laravel reaches it via `FORECAST_SERVICE_URL` (.env, default `http://127.0.0.1:9000`).

## Demo data / logins
Seeded users (password: `password`):
- Admin: `admin@example.com`
- Manager: `manager@example.com`
- Waiter: `waiter@example.com`

Seeded org/location/warehouses via `RbacSeeder` and inventory units/categories via `InventorySeeder`.

## Quick demo (≈5–10 min)
1) Log in as admin/manager. Pick active location from top nav.  
2) Inventory → Items: create an item.  
3) Stock → Receipt: post a receipt for that item.  
4) Stock → Waste/Internal use: post a reduction.  
5) Stock Count: create and post a count; ledger shows adjustments.  
6) Recipes: open a menu item, add a recipe version with ingredients.  
7) Menu usage: enter usage for a menu item.  
8) Reports → Variance: view expected vs actual.  
9) Procurement → Suggestions: create a PO draft; approve; Receive goods on PO detail.  
10) Forecasts: run “Generate forecast” and view results.  
11) Alerts: check Anomalies/Alerts, add comments/status.  
12) Data → Import/Export: dry-run an items CSV, export balances/ledger.  
13) Audit Logs / Period Lock: browse logs, set lock date.

## Testing
- PHP: `vendor/bin/phpunit`
- Python (forecast-service): `cd forecast-service && . .venv/bin/activate && python -m pytest`

## Screenshots
Place images in `docs/images/` and reference here. (TODO: add screenshots.)

## Docs
- Detailed user guide: `docs/USER_GUIDE.md`
- Architecture/API: (TODO: add `docs/ARCHITECTURE.md` / `docs/API.md` links when available)

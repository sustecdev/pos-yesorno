# pos-yesorno

# TeboOS — Restaurant Point of Sale

A full-featured restaurant POS built with Laravel, Livewire, and Laravel Reverb for real-time kitchen notifications.

## Features

- **Waiter POS** — floor plan, order builder, rush/allergy flags, fire course
- **Kitchen KDS** — multi-sensory alerts, station routing, ack flow, escalation
- **Cashier** — payments, split bills, discounts, receipts
- **Host** — reservations with table assignment and seating
- **Admin** — menu, inventory, kitchen broadcast, reports dashboard

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL via XAMPP (default) or SQLite

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database (MySQL — create `teboos` in phpMyAdmin or via mysql CLI)
php artisan migrate --seed

# Build assets
npm run build

# Start services (3 terminals)
php artisan serve
php artisan queue:work
php artisan reverb:start
```

Open http://localhost:8000

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@teboos.com | password |
| Manager | manager@teboos.com | password |
| Waiter | waiter@teboos.com | password |
| Kitchen | kitchen@teboos.com | password |
| Cashier | cashier@teboos.com | password |
| Host | host@teboos.com | password |

## Shared hosting deploy

Full guide: **[deploy/SHARED_HOSTING.md](deploy/SHARED_HOSTING.md)**

Quick steps for `yesorno.plateos.site` on Hostinger shared hosting:

1. hPanel → PHP **8.3**, document root `public_html/yesorno`
2. `git pull` into `~/domains/plateos.site/public_html/yesorno`
3. `composer install --no-dev --optimize-autoloader` (PHP 8.2+)
4. Copy `deploy/env.production.example` → `.env` (set DB + `APP_URL=https://yesorno.plateos.site`)
5. `cp deploy/apache-public_html.htaccess .htaccess`
6. `php artisan migrate --force --seed && php artisan storage:link && php artisan config:cache`

No npm on the server — CSS/JS are in `public/build/`. Use `https://yesorno.plateos.site/login` (not `/public/login`).

## XAMPP + MySQL

`.env` is preconfigured for XAMPP MySQL (`teboos` database, user `root`, no password).

1. Start **MySQL** in the XAMPP Control Panel
2. Create the database if needed: `CREATE DATABASE teboos;` (or use phpMyAdmin)
3. Run `php artisan migrate --seed`
4. Optionally point Apache to `public/` instead of `php artisan serve`

## Real-Time Kitchen

Kitchen alerts use Laravel Reverb WebSockets. Ensure these run alongside the app:

- `php artisan reverb:start` — WebSocket server (port 8080)
- `php artisan queue:work` — escalation & printer fallback jobs

If Reverb is unavailable, the KDS falls back to 3-second polling.

## License

MIT
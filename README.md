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

- PHP 8.3+
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

## VPS deploy (no `/public` in URL)

When the web root is `public_html` and you want `https://yesorno.plateos.site/login` (not `/public/login`):

```bash
cd ~/domains/plateos.site/public_html/yesorno

# After git pull / composer install (build assets are committed — no npm required on server)
cp deploy/apache-public_html.htaccess .htaccess

php8.3 artisan storage:link
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache
```

This rewrites requests internally to `public/` and blocks web access to `app/`, `vendor/`, `.env`, etc.

For **nginx**, use `deploy/nginx-public_html.conf` as a starting point.

**Requirements:** PHP 8.3+ (Laravel 13). Set `APP_URL=https://yesorno.plateos.site` in `.env`.

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
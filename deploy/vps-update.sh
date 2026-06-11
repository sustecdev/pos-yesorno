#!/bin/bash
set -euo pipefail

cd /var/www/teboos

echo "==> Pulling latest code..."
git pull origin main

echo "==> Running migrations (safe — does not wipe data)..."
php artisan migrate --force

if [[ "${RESET_DATA:-0}" == "1" ]]; then
    echo "==> RESET_DATA=1 — backing up before reseed..."
    bash deploy/backup-db.sh
    echo "==> Reseeding users and menus (clears orders/inventory)..."
    php artisan db:seed --class=TeboOSSeeder --force
fi

echo "==> Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache public/build

echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done: https://yesorno.plateos.site/login"

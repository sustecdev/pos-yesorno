#!/bin/bash
set -euo pipefail

cd /var/www/teboos

echo "==> Pulling latest code..."
git pull origin main

echo "==> Updating users and menus (clears orders/inventory)..."
php artisan db:seed --class=TeboOSSeeder --force

echo "==> Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache public/build

echo "==> Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done. Site: https://yesorno.plateos.site/login"

# Deploy on Hetzner — pos.yesorno.bar

Guide for **Hetzner Cloud VPS** (Ubuntu/Debian). On a VPS you point nginx at Laravel’s `public/` folder — no `/public` in URLs, no `.htaccess`.

---

## 1. Server packages (SSH as root)

```bash
apt update && apt upgrade -y
apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath \
  php8.2-gd php8.2-intl git unzip curl

curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

PHP **8.2** matches this project. Use `php8.3-fpm` instead if you prefer 8.3.

---

## 2. Clone the app

```bash
mkdir -p /var/www/pos.yesorno.bar
cd /var/www/pos.yesorno.bar

git clone https://github.com/sustecdev/pos-yesorno.git .

composer install --no-dev --optimize-autoloader
```

---

## 3. Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE pos_yesorno CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pos'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL ON pos_yesorno.* TO 'pos'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Environment

```bash
cp .env.example .env
nano .env
```

```env
APP_NAME="Yes or No Restaurant"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.yesorno.bar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_yesorno
DB_USERNAME=pos
DB_PASSWORD=strong_password_here

QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
```

```bash
php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Frontend assets are already in `public/build/` — no npm on the server.

---

## 5. Permissions

```bash
chown -R www-data:www-data /var/www/pos.yesorno.bar
chmod -R 775 storage bootstrap/cache
```

---

## 6. nginx (document root = `public/`)

```bash
cp deploy/hetzner-nginx.conf /etc/nginx/sites-available/pos.yesorno.bar
ln -sf /etc/nginx/sites-available/pos.yesorno.bar /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

**Important:** `root` is `/var/www/pos.yesorno.bar/public` — login URL is:

```text
https://pos.yesorno.bar/login
```

Do **not** use `/public/login`.

---

## 7. SSL (Let’s Encrypt)

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d pos.yesorno.bar
```

---

## 8. DNS (Hetzner Console)

Add an **A record** for `pos.yesorno.bar` → your VPS IPv4 address.

---

## 9. Queue worker (optional, recommended)

```bash
nano /etc/supervisor/conf.d/pos-queue.conf
```

```ini
[program:pos-queue]
process_name=%(program_name)s
command=php /var/www/pos.yesorno.bar/artisan queue:work --sleep=3 --tries=3
directory=/var/www/pos.yesorno.bar
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/pos.yesorno.bar/storage/logs/queue.log
```

```bash
apt install -y supervisor
supervisorctl reread && supervisorctl update && supervisorctl start pos-queue
```

---

## 10. Updates

```bash
cd /var/www/pos.yesorno.bar
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart pos-queue
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| 404 on `/login` | nginx `root` must end with `/public` |
| 404 on `/public/login` | Wrong URL — use `/login` |
| 502 Bad Gateway | `systemctl status php8.2-fpm`, check socket path in nginx |
| 500 error | `tail storage/logs/laravel.log` |
| Permission denied | `chown -R www-data:www-data storage bootstrap/cache` |

---

## Hetzner Web Hosting (shared)

If you use **Hetzner Web Hosting** (not Cloud VPS), set the document root to the `public/` folder in the hosting panel — same idea as [DOCROOT-PUBLIC.md](DOCROOT-PUBLIC.md). Use Apache `.htaccess` only if you cannot point the domain at `public/`.

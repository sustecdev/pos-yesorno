# Shared hosting deploy — yesorno.plateos.site

For Hostinger (or similar) **shared hosting**: no root/sudo, no Node, no long-running processes.

## Your layout

```text
/home/u232000820/domains/plateos.site/public_html/yesorno/
├── .htaccess          ← copy from deploy/apache-public_html.htaccess
├── .env
├── app/
├── public/
│   └── build/         ← committed in Git (no npm on server)
├── vendor/
└── ...
```

**Domain:** `https://yesorno.plateos.site`  
**Do not** use `/public` in the URL.

---

## 1. hPanel settings

| Setting | Value |
|---------|--------|
| PHP version | **8.2** or **8.3** |
| Document root | `public_html/yesorno` (Laravel root, **not** `yesorno/public`) |
| SSL | Enable Let's Encrypt for `yesorno.plateos.site` |

---

## 2. Database (hPanel → Databases)

1. Create MySQL database + user.
2. Note host (usually `127.0.0.1`), database name, username, password.

---

## 3. Upload / Git (SSH or File Manager)

**SSH (recommended):**

```bash
cd ~/domains/plateos.site/public_html/yesorno
git pull

composer install --no-dev --optimize-autoloader
```

Verify PHP version:

```bash
php -v
# Must be 8.2.0 or higher (8.2.30 works).
```

**No SSH:** upload/sync the project via FTP/File Manager into `public_html/yesorno/`.  
Frontend assets are already in `public/build/` — **you do not need npm on the server**.

---

## 4. Environment file

Copy `deploy/env.production.example` to `.env` and edit:

```env
APP_URL=https://yesorno.plateos.site
APP_ENV=production
APP_DEBUG=false

DB_HOST=127.0.0.1
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...

# Shared hosting: no WebSocket server
BROADCAST_CONNECTION=log
QUEUE_CONNECTION=sync
```

Kitchen display still works — it **polls every 5 seconds** without Reverb.

---

## 5. Apache rewrite (no `/public` in URL)

```bash
cd ~/domains/plateos.site/public_html/yesorno
cp deploy/apache-public_html.htaccess .htaccess
```

Or paste `deploy/apache-public_html.htaccess` into `.htaccess` via File Manager.

---

## 6. Laravel setup (SSH)

```bash
cd ~/domains/plateos.site/public_html/yesorno

php artisan key:generate
php artisan migrate --force --seed
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Permissions (File Manager or SSH)

Folders must be writable:

- `storage/` → **775**
- `bootstrap/cache/` → **775**

---

## 8. Open the site

- ✅ `https://yesorno.plateos.site/login`
- ❌ `https://yesorno.plateos.site/public/login`

Login: `admin@teboos.com` / `password` — change after first login.

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| Vite manifest not found | `git pull` — `public/build/manifest.json` is in the repo |
| 500 error | Check `storage/logs/laravel.log`; fix folder permissions |
| `/public` in URL | Install `.htaccess`; set docroot to `yesorno` not `yesorno/public` |
| Composer PHP version error | Set PHP **8.2+** in hPanel for the domain |
| White page | `APP_DEBUG=true` briefly, read error; then set back to `false` |

---

## What shared hosting cannot run

These need a VPS or background processes:

- `php artisan reverb:start` (WebSockets) — **optional**, KDS polls instead
- `php artisan queue:work` — use `QUEUE_CONNECTION=sync` on shared hosting

Everything else (waiter, cashier, kitchen, admin, reports) works on shared hosting.

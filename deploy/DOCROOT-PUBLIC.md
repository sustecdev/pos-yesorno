# Document root = `public/` folder (shared hosting)

Works on Hostinger, Hetzner Web Hosting, and similar panels.

If you get **404 Not Found** on `/login` and `/public/login`, use this setup.

## Best method: point domain at the `public` folder

In **hPanel → Websites → pos.yesorno.bar → Document root**, set:

```text
public_html/public
```

(or `public_html/yesorno/public` if the app is in a `yesorno` subfolder)

### Then on the server

```bash
cd ~/domains/pos.yesorno.bar/public_html
# OR: cd ~/domains/plateos.site/public_html/yesorno

git pull

# DELETE the root .htaccess if you added one earlier (it breaks this setup)
rm -f .htaccess

composer install --no-dev --optimize-autoloader
php artisan config:cache
```

### `.env`

```env
APP_URL=https://pos.yesorno.bar
```

### Correct URLs

| URL | Result |
|-----|--------|
| https://pos.yesorno.bar/login | Works |
| https://pos.yesorno.bar/public/login | 404 (expected — do not use) |

---

## Alternative: document root = project folder

Only if you cannot change the document root in hPanel.

```bash
cp deploy/apache-laravel-root.htaccess .htaccess
```

If the app is in a subfolder (e.g. `/yesorno/`), edit `.htaccess` and uncomment:

```apache
RewriteBase /yesorno/
```

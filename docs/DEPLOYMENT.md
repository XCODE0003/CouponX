# Deployment guide

## 1. Server requirements

- PHP **8.3+** with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `gd` (with WebP support, for logo optimization), `redis` (or `phpredis`).
- Composer 2.
- Node 20+ / npm (build step only — not required at runtime).
- A database: **MySQL 8 / MariaDB 10.6+ / PostgreSQL 14+**.
- **Redis** (cache, queue, sessions).
- A web server (Nginx + PHP-FPM recommended) with the document root at `public/`.
- A process manager (systemd / Supervisor) for the queue worker and scheduler.

## 2. Environment

Copy `.env.example` to `.env` and set at minimum:

```dotenv
APP_NAME="CouponX Deals"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=couponx
DB_USERNAME=couponx
DB_PASSWORD=change-me

# Redis-backed cache / queue / sessions
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Mail (newsletter, admin alerts)
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hello@your-domain.com"

# SEO / analytics
GOOGLE_ANALYTICS_ID=G-XXXXXXX
GOOGLE_SITE_VERIFICATION=...

# Operational alerts (import / API / cron failures)
TELEGRAM_BOT_TOKEN=...
TELEGRAM_CHAT_ID=...
NOTIFY_EMAIL=ops@your-domain.com

# Production cookie hardening
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

## 3. Build & install

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate          # only if APP_KEY is empty
php artisan migrate --force
php artisan db:seed --class=AdminSeeder --force   # creates admin/editor — CHANGE PASSWORDS
php artisan storage:link          # serve uploaded logos/images from storage
php artisan optimize              # config/route/view cache
php artisan filament:optimize
```

> Re-run `php artisan optimize` and `filament:optimize` on every deploy after code changes.

## 4. Queue worker (Supervisor example)

Click logging and outgoing mail run on the queue.

```ini
[program:couponx-worker]
command=php /var/www/couponx/artisan queue:work --sleep=1 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/couponx-worker.log
```

## 5. Scheduler (cron)

The hourly coupon import is registered via the Laravel scheduler. Add **one** cron entry:

```cron
* * * * * cd /var/www/couponx && php artisan schedule:run >> /dev/null 2>&1
```

This drives `coupons:import` hourly (`php artisan schedule:list` to verify). Failures are reported to Telegram + `NOTIFY_EMAIL`.

## 6. Nginx (sketch)

```nginx
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/couponx/public;

    index index.php;
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
```

## 7. CDN / caching

- Front the site with **Cloudflare**. Cache static assets aggressively; bypass cache for `/admin`, `/go/`, `/out/`, and `POST` routes.
- `nav:categories:*` and other hot reads are cached in Redis (1h TTL).
- Images: prefer WebP and `loading="lazy"` (already applied to logos and blog covers).

## 8. Post-deploy smoke test

```bash
curl -I https://your-domain.com/            # 302 → /en
curl -s https://your-domain.com/sitemap.xml | head
curl -s https://your-domain.com/robots.txt
# Log in to /admin, confirm dashboard widgets load.
```

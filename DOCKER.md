# CouponX — Docker deployment

Production stack, all containerized:

| Service     | Image / build           | Role                                              |
|-------------|-------------------------|---------------------------------------------------|
| `nginx`     | `nginx` target          | TLS termination, static files, reverse proxy      |
| `app`       | `app` target (php-fpm)  | Laravel application (PHP 8.3)                      |
| `queue`     | `app` image             | `php artisan queue:work` (Redis queue)            |
| `scheduler` | `app` image             | `php artisan schedule:work` (hourly coupon import)|
| `mysql`     | `mysql:8.0`             | Primary database                                  |
| `redis`     | `redis:7-alpine`        | Cache, sessions, queue                            |
| `certbot`   | `certbot/certbot`       | Let's Encrypt issuance + auto-renewal             |

The image is built in three stages (`Dockerfile`):
1. **builder** — PHP + Composer + Node together (the Vite build calls `php artisan
   wayfinder:generate`, and Filament publishes assets), produces `vendor/` and
   compiled `public/`.
2. **app** — slim `php:8.3-fpm-alpine` runtime with the built artifacts.
3. **nginx** — `nginx:alpine` with the compiled `public/` baked in.

User uploads (store logos, blog covers) live on the `public` disk
(`storage/app/public`) and are shared between `app` and `nginx` via the
`app-storage` volume, served through the `public/storage` symlink.

## First deploy

```bash
# On the server, in the project root:
cp .env.docker .env            # or: ./deploy/bootstrap-env.sh  (generates secrets)
# Review .env (APP_URL, mail, analytics, Telegram notifications)

docker compose build
docker compose up -d mysql redis app queue scheduler
docker compose exec -T app php artisan migrate --force --seed   # seed demo data + admin
./deploy/init-letsencrypt.sh   # obtain the TLS certificate and start nginx
```

`./deploy/bootstrap-env.sh` then `SEED=1 ./deploy/deploy.sh` does the same in two
commands.

## Redeploy (after `git pull`)

```bash
git pull
./deploy/deploy.sh             # build + migrate + optimize + reload nginx
```

## Operations

```bash
docker compose ps                                   # status
docker compose logs -f app                          # application logs
docker compose exec app php artisan <command>       # artisan
docker compose exec app php artisan coupons:import  # manual import
docker compose restart app queue scheduler          # restart workers
```

## Seeded admin

- `admin@couponx.test` / `password` (Admin)
- `editor@couponx.test` / `password` (Editor)

**Change these immediately in production** via the `/admin` panel or:

```bash
docker compose exec app php artisan tinker
>>> \App\Models\User::where('email','admin@couponx.test')->update(['password'=>bcrypt('NEW_STRONG_PASSWORD')]);
```

## Notes

- TLS certificates auto-renew (the `certbot` container runs `certbot renew` every
  12h; `nginx` reloads every 6h to pick up renewed certs).
- `DB_*` / `APP_KEY` secrets live in `.env` only — never committed.
- To use real email, set `MAIL_*` in `.env` and `docker compose restart app queue`.

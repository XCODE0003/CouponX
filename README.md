# CouponX Deals

Multilingual coupons, discounts and affiliate-deals aggregator (EN / RU, extensible).
Public site for shoppers + Filament admin panel + cloaked affiliate tracking + modular import system.

Reference UX: Promokodi.net.

---

## Tech stack

| Layer            | Technology                                                        |
| ---------------- | ----------------------------------------------------------------- |
| Backend          | PHP 8.4, Laravel 13                                               |
| Frontend         | Inertia.js v3 + Vue 3 + TypeScript + Tailwind CSS v4              |
| Admin panel      | Filament v4 (`/admin`)                                            |
| Auth             | Laravel Fortify (admin only; no public registration)             |
| i18n content     | `spatie/laravel-translatable` (JSON columns)                     |
| Slugs            | `spatie/laravel-sluggable`                                        |
| Admin audit log  | `spatie/laravel-activitylog`                                     |
| DB               | SQLite (dev) · MySQL/PostgreSQL (prod-ready)                      |
| Cache / Queue    | Redis (prod), database (dev)                                     |

## Features

- **Public site** (`/{locale}/…`): home, store pages, category pages, blog, search, newsletter.
- **Multilingual**: every content field is translatable; UI strings via Laravel lang files; locale switcher; locale-prefixed URLs.
- **Cloaked affiliate links**: `/go/{store}` and `/out/{coupon}` redirects — no raw affiliate URLs in HTML. Geo-aware destination resolution, UTM injection (`utm_source`, `utm_campaign`, `coupon_id`), per-network tracking templates.
- **Click tracking**: queued logging with masked & hashed IP, user-agent, referer, UTM, locale, country.
- **Coupon interaction**: "Show code" copies the code, reveals it, and opens the cloaked redirect in a new tab.
- **Admin** (`/admin`): manage stores, coupons, categories, affiliate networks, blog, newsletter subscribers, users. Translatable forms (EN/RU tabs), bulk actions (delete / change status / set categories), activity logging, Admin & Editor roles, dashboard analytics widgets.
- **SEO**: hreflang + canonical (server-rendered), localized meta, JSON-LD (Organization, BreadcrumbList, Store, Offer), `sitemap.xml` with hreflang alternates, `robots.txt`, GA4 + Search Console hooks.
- **Import system**: pluggable per-network adapters, store deduplication via aliases, coupon dedup via hash + source/external-id, `coupons:import` command, hourly cron, Telegram/email failure alerts.

---

## Quick start (local)

Requirements: PHP 8.3+, Composer, Node 20+, npm.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite          # SQLite dev DB
php artisan migrate --seed               # schema + demo data
npm run build                            # or: npm run dev (hot reload)
composer run dev                         # serves app + queue + vite + logs
```

Visit:

- Public site: <http://localhost:8000> → redirects to `/en`
- Admin panel: <http://localhost:8000/admin>

### Seeded admin accounts

| Role   | Email                  | Password   |
| ------ | ---------------------- | ---------- |
| Admin  | `admin@couponx.test`   | `password` |
| Editor | `editor@couponx.test`  | `password` |

> **Change these before going live.** Admins have full access; Editors can only manage coupons and the blog.

---

## Common commands

```bash
php artisan coupons:import [network-slug]   # import coupons (all active networks if omitted)
php artisan schedule:list                   # show scheduled jobs (hourly import)
php artisan queue:work                       # process click-logging & mail jobs
composer test                                # Pint + PHPStan + PHPUnit
npm run lint && npm run types:check          # ESLint + vue-tsc
```

---

## Documentation

- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — production deployment & environment.
- [docs/DATABASE.md](docs/DATABASE.md) — database schema reference.
- [docs/ADMIN_GUIDE.md](docs/ADMIN_GUIDE.md) — using the admin panel.
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — code layout & extension points (adding a network, a locale).

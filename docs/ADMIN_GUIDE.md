# Admin panel guide

The admin panel is at **`/admin`**. Sign in with an Admin or Editor account.

| Role       | Access                                                                 |
| ---------- | ---------------------------------------------------------------------- |
| **Admin**  | Everything (stores, coupons, categories, networks, blog, subscribers, users). |
| **Editor** | Coupons and Blog only.                                                  |

Navigation is grouped: **Catalog** (Stores, Coupons, Categories), **Content** (Blog),
**Marketing** (Affiliate networks, Newsletter), **System** (Users).

## Translatable fields

Content forms have an **English / Русский** tab block. Fill each language; the default
locale (English) is required, others are optional and fall back to it.

## Stores

- `slug` is the URL segment (`/store/nike`) and is generated from the name on create.
- **Logo**: upload a square PNG / JPEG / WebP — it's auto-converted to optimized WebP (and downscaled) on upload for fast loads. SVG is not accepted (security).
- **Cashback**: free-text fields (`Average cashback`, `Payout terms`) shown on the store page.
- **Default affiliate network** + **Categories** drive redirects and listings.
- Toggle **Active** to publish; **Featured** to surface on the homepage.
- **Affiliate links** tab (on the store edit page): add/edit geo-aware affiliate destinations — set the network, an optional country (ISO-2, blank = global default), the affiliate URL, cashback and priority. This is how `/go/{store}` resolves its target per visitor country.

## Coupons

- **Type**: `Promo code` (requires a code), `Deal`, or `Sale`.
- **Destination URL** is optional — if blank, the store's affiliate link is used.
- Set **expiry**, **categories**, and **status** (`active` to publish).
- **Bulk actions** (select rows → toolbar): delete, **Change status**, **Set categories**.

## Categories

- Drag-to-reorder (the `position` column). `icon` is a [lucide](https://lucide.dev) name
  (`laptop`, `shirt`, `home`, `sparkles`, `plane`, `car`, …).

## Affiliate networks

- **Integration** selects the import adapter: `Manual`, `JSON feed`, `Admitad`, `CJ Affiliate`, `Awin`.
- Choosing an API network reveals an **API credentials** panel (stored **encrypted at rest**); secret fields are masked with a reveal toggle:
  - **Admitad** — Client ID, Client secret, Website ID (optional), Scope (`coupons`).
  - **CJ Affiliate** — Personal access token, Website ID (PID), Advertiser IDs (`joined`).
  - **Awin** — API token, Publisher ID.
  - **JSON feed** — Feed URL, items path, field mapping (for any network with a JSON export).
- `tracking_template`: outbound cloaking URL with a `{target}` placeholder.
- For networks with a registered adapter, an **Import now** row action appears; the hourly cron imports them all. Missing credentials = safe no-op.

## Blog

- Rich-text body per locale. `status = published` + a past `published_at` makes it live.

## Importing coupons

- Automatic: the scheduler runs `coupons:import` hourly for active networks that have an adapter.
- Manual: **Affiliate networks → Import now**, or CLI: `php artisan coupons:import [slug]`.
- Imported **stores are created inactive** (pending your review) and merged by alias so
  "AliExpress WW/RU/Global" collapse into one store.
- Failures are sent to Telegram + the ops email automatically.

## Audit log

Every create/update/delete on catalog/content records is recorded (who, what, when) via the
activity log.

## Analytics

The dashboard shows clicks (today / all-time), active coupons, stores and subscribers, plus a
**Top stores by clicks** table. Site-wide traffic is tracked in Google Analytics (GA4).

## Newsletter subscribers

Captured from the public newsletter form. Manage/export from **Marketing → Newsletter**.

## First-run checklist

1. Change the seeded admin/editor passwords (or create new users and delete the demo ones).
2. Create your affiliate networks and set tracking templates.
3. Add/curate stores, upload logos, set affiliate links per country.
4. Configure GA4 / Search Console and Telegram/email alert env vars.

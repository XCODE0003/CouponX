# Architecture & extension points

## Code layout

```
app/
├── Console/Commands/ImportCouponsCommand.php   coupons:import
├── DataObjects/ClickContext.php                immutable click snapshot
├── Enums/                                       UserRole, CouponType, CouponStatus, DiscountType, BlogPostStatus
├── Filament/                                    admin panel
│   ├── Concerns/AdminOnly.php                   resource gate (admin-only)
│   ├── Resources/<Entity>/                      Resource + Schemas (form) + Tables + Pages
│   ├── Support/Translatable.php                 EN/RU tab builder for forms
│   └── Widgets/                                 dashboard analytics
├── Http/
│   ├── Controllers/Public/                      Inertia controllers (Home, Store, Category, Blog, Search, Newsletter, Sitemap)
│   ├── Controllers/RedirectController.php       /go & /out cloaked redirects
│   ├── Middleware/SetLocale.php                 locale resolution + URL defaults
│   ├── Middleware/HandleInertiaRequests.php     shared props (locale, translations, seo, nav, flash)
│   └── Presenters/                              model → localized array for the frontend
├── Jobs/LogClick.php                            queued click logging
├── Models/                                      Eloquent models (+ Concerns/RecordsActivity)
├── Providers/ImportServiceProvider.php          binds the adapter registry
├── Services/
│   ├── Affiliate/{AffiliateLinkResolver,UtmBuilder}.php
│   ├── Import/{AdapterRegistry,CouponImporter,StoreResolver}, Adapters/, Contracts/, DTO/
│   └── Notifications/AdminNotifier.php          Telegram + email + log
└── Support/{Locales,Seo,IpMasker}.php

resources/js/
├── pages/public/        Home, Store(s), Categor(y|ies), Blog, BlogPost, Search
├── components/public/   StoreCard, CouponCard, CategoryCard, Newsletter, LocaleSwitcher, SearchBar, …
├── layouts/PublicLayout.vue
└── composables/useI18n.ts

lang/{en,ru}/messages.php   UI strings
```

## Request flow (public page)

```
HTTP → SetLocale (web group) sets app locale + URL::defaults('locale')
     → Public controller builds props via Presenters (localized) + Seo helper
     → Inertia renders pages/public/* inside PublicLayout
     → app.blade.php renders <title>, meta, hreflang, canonical, JSON-LD server-side
```

## Affiliate redirect flow

```
/out/{coupon}  → RedirectController::coupon
   → AffiliateLinkResolver: destination → geo store link → UTM → network tracking template
   → LogClick (queued): masked IP hash, UA, referer, UTM; bump counters
   → 302 to merchant
```

## Extension points

### Add a new locale (e.g. German)

1. `app/Support/Locales.php` → add `'de' => ['label' => 'German', 'native' => 'Deutsch', 'region' => 'DE']`.
2. Create `lang/de/messages.php` (copy `en`, translate).
3. Add the `de` tab automatically appears in admin forms (driven by `Locales::SUPPORTED`).
4. Existing content rows fall back to the default locale until translated.

Routing, hreflang, sitemap, the locale switcher and admin translation tabs all read
`Locales::SUPPORTED`, so no other code changes are required.

### Affiliate network adapters

Built-in (registered in `ImportServiceProvider`): `manual`, `json_feed`, `admitad`, `cj`, `awin`.
Credentials live in the network's **encrypted** `config` (`AffiliateNetwork::$casts` →
`encrypted:array`) and are edited in the admin. Each adapter reads its own keys
(`client_id`/`client_secret`, `cj_token`/`cj_website_id`, `awin_token`/`awin_publisher_id`, …)
and is a safe no-op until configured. Shared mapping helpers live in
`Services\Import\Concerns\NormalizesDrafts`.

### Add a new affiliate network integration

1. Implement `App\Services\Import\Contracts\ImportAdapter` — return `key()` and `fetch()`
   yielding `CouponDraft`s (call the network API / parse a feed using `$network->config`).
2. Register it in `App\Providers\ImportServiceProvider::register()`:
   `$registry->register(new YourAdapter(...));`
3. In the admin, create an Affiliate Network whose `integration` equals your `key()`.
4. `coupons:import` (manual or hourly) will use it. The importer handles store
   deduplication (aliases) and coupon dedup (`source`+`external_id`, `dedupe_hash`) for you.

No changes to `CouponImporter`, controllers, or the schema are needed.

**No-code option — JSON feed.** Many networks expose a JSON export. Instead of writing an
adapter, set a network's `integration` to `json_feed` and point its `config` at the feed:

```jsonc
{
  "feed_url":   "https://network.example/feed.json",
  "items_path": "data.coupons",        // optional dot-path to the array
  "fields": {                           // optional remapping (defaults shown by key)
    "store": "merchant", "external_id": "id", "title": "name",
    "code": "voucher", "url": "tracking_url", "type": "kind",
    "discount_type": "discount.kind", "discount_value": "discount.value",
    "expires_at": "valid_until", "store_url": "merchant_url", "description": "desc"
  }
}
```

A non-2xx feed response throws and is reported to admins via Telegram/email.

### Caching

Hot public reads go through `App\Support\CatalogCache` (versioned keys, Redis in prod).
Saving/deleting any Store, Category or Coupon bumps the version (via the
`BustsCatalogCache` model trait), instantly invalidating derived caches — no manual flush.

### Image optimization

`App\Support\ImageOptimizer` converts uploaded store logos / blog covers to downscaled
WebP on the fly (GD), with a graceful fallback to the original when WebP is unavailable.

### Add a new admin resource

`php artisan make:filament-resource <Model>` then move form fields into `Schemas/*Form.php`
and columns into `Tables/*Table.php`. Use `App\Filament\Support\Translatable::tabs()` for
translatable fields and the `AdminOnly` trait to restrict to admins.

## Quality gates

```bash
composer test        # Pint (format) + PHPStan (level per phpstan.neon) + PHPUnit
npm run lint:check   # ESLint
npm run types:check  # vue-tsc
npm run format:check # Prettier
```

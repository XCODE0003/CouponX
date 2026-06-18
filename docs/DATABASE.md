# Database schema

Translatable text fields are stored as JSON columns (`{"en": "...", "ru": "..."}`) via
`spatie/laravel-translatable`. Slugs are single, locale-agnostic strings.

## Entities

### `affiliate_networks`
Partner networks (Admitad, CJ, Awin, …) and their integration config.
`slug`, `name`, `integration` (adapter key), `is_active`, `tracking_template` (`{target}` placeholder),
`default_utm` (json), `config` (json — credentials/import settings), `last_imported_at`.

### `stores`
Canonical merchant entity.
`slug` (unique), `name`, `description`*, `about`*, `cashback_terms`*, `logo`, `website_url`,
`rating`, `rating_count`, `cashback_type`, `cashback_value`, `cashback_payout_terms`,
`default_affiliate_network_id` → `affiliate_networks`, `countries` (json), `is_featured`, `is_active`,
`position`, `clicks_count`, `meta_title`*, `meta_description`*. *(\* = translatable)*

### `store_aliases`
Maps many imported names ("AliExpress WW/RU/Global") to one store.
`store_id`, `name`, `normalized` (lowercased, for matching), `source`, `external_id`.
Unique on `(normalized, source)`.

### `store_affiliate_links`
Geo-aware affiliate destinations.
`store_id`, `affiliate_network_id`, `country_code` (null = default), `affiliate_url`, `cashback_value`,
`priority` (higher wins), `is_active`.

### `coupons`
`store_id`, `affiliate_network_id`, `type` (`code`/`deal`/`sale`), `title`*, `description`*, `terms`*,
`code`, `discount_type`, `discount_value`, `destination_url`, `starts_at`, `expires_at`,
`used_count`, `success_rate`, `clicks_count`, `is_exclusive`, `is_featured`, `is_verified`,
`status` (`active`/`expired`/`draft`/`archived`), `position`,
`source`, `external_id`, `dedupe_hash`. Unique on `(source, external_id)`.

### `categories`
Self-referencing tree.
`parent_id`, `slug` (unique), `name`*, `description`*, `icon` (lucide name), `image`, `position`,
`is_featured`, `is_active`, `meta_title`*, `meta_description`*.

### `category_store`, `category_coupon`
Many-to-many pivots.

### `clicks`
Affiliate redirect log.
`coupon_id`, `store_id`, `affiliate_network_id`, `country_code`, `locale`, `ip_hash` (sha256 of masked IP),
`user_agent`, `referer`, `utm` (json), `created_at`. No raw IPs are stored.

### `blog_posts`
`author_id` → `users`, `slug` (unique), `title`*, `excerpt`*, `body`*, `cover_image`,
`status` (`draft`/`published`), `published_at`, `meta_title`*, `meta_description`*.

### `newsletter_subscribers`
`email` (unique), `locale`, `country_code`, `status` (`subscribed`/`unsubscribed`), `confirmed_at`.

### `users`
Standard Laravel users + `role` (`admin`/`editor`/`user`) + `is_active`. Only admin/editor reach `/admin`.

### `activity_log`
`spatie/laravel-activitylog` — who changed what and when (admin audit log).

## Relationships (summary)

```
AffiliateNetwork 1─* Store (default network)
AffiliateNetwork 1─* Coupon
AffiliateNetwork 1─* StoreAffiliateLink
Store 1─* StoreAlias
Store 1─* StoreAffiliateLink
Store 1─* Coupon
Store *─* Category           (category_store)
Coupon *─* Category          (category_coupon)
Coupon 1─* Click
Category 1─* Category        (parent/children)
User 1─* BlogPost            (author)
```

## Enums (`app/Enums`)

`UserRole`, `CouponType`, `CouponStatus`, `DiscountType`, `BlogPostStatus`.

## Re-generating the schema

```bash
php artisan migrate:fresh --seed   # WARNING: drops all data
```

<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Models\AffiliateNetwork;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\DTO\CouponDraft;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Generic adapter that pulls coupons from a JSON feed. Most affiliate networks
 * expose a JSON/CSV export — point a network's `config` at the feed and map its
 * fields; no code changes needed per network.
 *
 * network.config = {
 *   "feed_url":   "https://network.example/feed.json",
 *   "items_path": "data.coupons",          // optional dot-path to the array
 *   "fields": {                              // optional field remapping
 *     "store": "merchant", "external_id": "id", "title": "name",
 *     "code": "voucher", "url": "tracking_url", "type": "kind",
 *     "discount_type": "discount.kind", "discount_value": "discount.value",
 *     "expires_at": "valid_until", "store_url": "merchant_url", "description": "desc"
 *   }
 * }
 */
class JsonFeedAdapter implements ImportAdapter
{
    public function key(): string
    {
        return 'json_feed';
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $url = $config['feed_url'] ?? null;

        if (! is_string($url) || $url === '') {
            return [];
        }

        // A non-2xx response throws and bubbles up to the importer/command,
        // which notifies admins (ТЗ: «падение API партнёрских сетей»).
        $response = Http::timeout(30)->acceptJson()->get($url)->throw();

        $itemsPath = $config['items_path'] ?? null;
        $rows = is_string($itemsPath) && $itemsPath !== ''
            ? data_get($response->json(), $itemsPath)
            : $response->json();

        if (! is_array($rows)) {
            return [];
        }

        /** @var array<string, string> $map */
        $map = is_array($config['fields'] ?? null) ? $config['fields'] : [];

        $drafts = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $drafts[] = $this->toDraft($row, $map);
            }
        }

        return $drafts;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $map
     */
    private function toDraft(array $row, array $map): CouponDraft
    {
        $get = fn (string $key, mixed $default = null): mixed => data_get($row, $map[$key] ?? $key, $default);

        $code = $get('code');
        $title = (string) ($get('title') ?? '');

        return new CouponDraft(
            storeName: (string) ($get('store') ?? 'Unknown store'),
            storeWebsite: $this->stringOrNull($get('store_url')),
            externalId: (string) ($get('external_id') ?? md5((string) json_encode($row))),
            type: $this->couponType($get('type'), $code),
            title: $this->localized($title),
            description: $this->localizedNullable($this->stringOrNull($get('description'))),
            code: $this->stringOrNull($code),
            affiliateUrl: $this->stringOrNull($get('url')),
            discountType: $this->discountType($this->stringOrNull($get('discount_type'))),
            discountValue: is_numeric($get('discount_value')) ? (float) $get('discount_value') : null,
            expiresAt: $this->parseDate($get('expires_at')),
        );
    }

    private function couponType(mixed $value, mixed $code): CouponType
    {
        return match (strtolower((string) $value)) {
            'sale' => CouponType::Sale,
            'deal' => CouponType::Deal,
            'code', 'promo' => CouponType::Code,
            default => $this->stringOrNull($code) !== null ? CouponType::Code : CouponType::Deal,
        };
    }

    private function discountType(?string $value): ?DiscountType
    {
        return match (strtolower((string) $value)) {
            'percentage', 'percent' => DiscountType::Percentage,
            'fixed', 'amount' => DiscountType::Fixed,
            'free_shipping', 'shipping' => DiscountType::FreeShipping,
            'bogo' => DiscountType::Bogo,
            '' => null,
            default => DiscountType::Other,
        };
    }

    /**
     * @return array<string, string>
     */
    private function localized(string $value): array
    {
        return [(string) config('app.fallback_locale', 'en') => $value];
    }

    /**
     * @return array<string, string>|null
     */
    private function localizedNullable(?string $value): ?array
    {
        return $value === null ? null : $this->localized($value);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}

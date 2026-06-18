<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\CouponStatus;
use App\Models\AffiliateNetwork;
use App\Models\Category;
use App\Models\Coupon;
use App\Services\Import\DTO\CouponDraft;
use App\Services\Import\DTO\ImportResult;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class CouponImporter
{
    public function __construct(
        private readonly AdapterRegistry $registry,
        private readonly StoreResolver $resolver,
    ) {}

    public function import(AffiliateNetwork $network): ImportResult
    {
        $adapter = $this->registry->for((string) $network->integration);

        if ($adapter === null) {
            throw new RuntimeException("No import adapter registered for [{$network->integration}].");
        }

        $result = new ImportResult;

        foreach ($adapter->fetch($network) as $draft) {
            try {
                $this->importDraft($network, $draft, $result);
            } catch (Throwable $e) {
                $result->addError($draft->externalId.': '.$e->getMessage());
            }
        }

        $network->forceFill(['last_imported_at' => now()])->save();

        return $result;
    }

    private function importDraft(AffiliateNetwork $network, CouponDraft $draft, ImportResult $result): void
    {
        $store = $this->resolver->resolve($draft->storeName, $draft->storeWebsite, $network->slug);
        $hash = $this->dedupeHash($store->id, $draft);

        $existing = Coupon::query()
            ->where('source', $network->slug)
            ->where('external_id', $draft->externalId)
            ->first()
            ?? Coupon::query()->where('dedupe_hash', $hash)->first();

        $attributes = [
            'store_id' => $store->id,
            'affiliate_network_id' => $network->id,
            'type' => $draft->type,
            'title' => $draft->title,
            'description' => $draft->description,
            'code' => $draft->code,
            'discount_type' => $draft->discountType,
            'discount_value' => $draft->discountValue,
            'destination_url' => $draft->affiliateUrl,
            'expires_at' => $draft->expiresAt,
            'source' => $network->slug,
            'external_id' => $draft->externalId,
            'dedupe_hash' => $hash,
            'status' => CouponStatus::Active,
        ];

        if ($existing !== null) {
            $existing->fill($attributes)->save();
            $result->updated++;
        } else {
            $existing = Coupon::query()->create($attributes);
            $result->created++;
        }

        if ($draft->categorySlugs !== []) {
            $categoryIds = Category::query()->whereIn('slug', $draft->categorySlugs)->pluck('id')->all();
            if ($categoryIds !== []) {
                $existing->categories()->syncWithoutDetaching($categoryIds);
            }
        }
    }

    private function dedupeHash(int $storeId, CouponDraft $draft): string
    {
        $fallback = (string) config('app.fallback_locale', 'en');
        $titles = $draft->title;
        $title = $titles[$fallback] ?? (string) (reset($titles) ?: '');

        return hash('sha256', $storeId.'|'.Str::lower((string) $draft->code).'|'.Str::lower($title));
    }
}

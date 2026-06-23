<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Enums\CouponStatus;
use App\Models\AffiliateNetwork;
use App\Models\Category;
use App\Models\Coupon;
use App\Services\Import\Contracts\ProvidesPrograms;
use App\Services\Import\DTO\CouponDraft;
use App\Services\Import\DTO\ImportResult;
use App\Services\Import\DTO\ProgramDraft;
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

        // Accepted programs first: ensures every merchant (even coupon-less ones
        // like Aviasales) has a store + affiliate link, and that coupons below
        // attach to the same domain-resolved store.
        if ($adapter instanceof ProvidesPrograms) {
            foreach ($adapter->programs($network) as $program) {
                try {
                    $this->importProgram($network, $program);
                } catch (Throwable $e) {
                    $result->addError('program '.$program->externalId.': '.$e->getMessage());
                }
            }
        }

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

    private function importProgram(AffiliateNetwork $network, ProgramDraft $program): void
    {
        $store = $this->resolver->resolve($program->name, $program->website, $network->slug);

        if ($store->default_affiliate_network_id === null) {
            $store->forceFill(['default_affiliate_network_id' => $network->id])->save();
        }

        // Import-owned default affiliate link (no country) — keeps /go/{store} and
        // coupon-less merchants monetized. Manual store fields are untouched.
        if ($program->affiliateUrl !== null) {
            $store->affiliateLinks()->updateOrCreate(
                ['affiliate_network_id' => $network->id, 'country_code' => null],
                ['affiliate_url' => $program->affiliateUrl, 'is_active' => true],
            );
        }
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

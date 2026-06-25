<?php

declare(strict_types=1);

namespace App\Services\Stores;

use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAffiliateLink;
use App\Models\StoreAlias;
use Illuminate\Support\Facades\DB;

/**
 * Merges duplicate stores (different domains, same merchant) into one canonical
 * store: moves coupons, affiliate links, categories, clicks and aliases, then
 * records each merged store's name + domain as aliases so future imports route
 * back to the canonical store instead of re-creating a duplicate.
 */
class StoreMerger
{
    /**
     * @param  iterable<int, Store>  $sources
     * @return int number of stores merged in
     */
    public function merge(Store $target, iterable $sources): int
    {
        $merged = 0;

        DB::transaction(function () use ($target, $sources, &$merged): void {
            foreach ($sources as $source) {
                if ($source->getKey() === $target->getKey()) {
                    continue;
                }

                // Coupons: (source, external_id) is globally unique, so re-pointing
                // store_id can never collide.
                Coupon::query()->where('store_id', $source->id)->update(['store_id' => $target->id]);

                // Affiliate links: move, dropping a duplicate (network + country) pair.
                foreach ($source->affiliateLinks()->get() as $link) {
                    $duplicate = StoreAffiliateLink::query()
                        ->where('store_id', $target->id)
                        ->where('affiliate_network_id', $link->affiliate_network_id)
                        ->where(fn ($q) => $link->country_code === null
                            ? $q->whereNull('country_code')
                            : $q->where('country_code', $link->country_code))
                        ->exists();

                    $duplicate
                        ? $link->delete()
                        : $link->forceFill(['store_id' => $target->id])->save();
                }

                // Categories.
                $categoryIds = $source->categories()->pluck('categories.id')->all();
                if ($categoryIds !== []) {
                    $target->categories()->syncWithoutDetaching($categoryIds);
                }

                // Click history.
                DB::table('clicks')->where('store_id', $source->id)->update(['store_id' => $target->id]);

                // Aliases are globally unique on (normalized, source) → safe bulk move.
                StoreAlias::query()->where('store_id', $source->id)->update(['store_id' => $target->id]);

                // Route future imports of this merchant's name/domain to the target.
                $this->rememberAlias($target, $source->name);
                if ($source->domain !== null) {
                    $this->rememberAlias($target, $source->domain);
                }

                $target->forceFill([
                    'clicks_count' => $target->clicks_count + (int) $source->clicks_count,
                ])->save();

                $source->delete();
                $merged++;
            }
        });

        return $merged;
    }

    private function rememberAlias(Store $target, string $value): void
    {
        $normalized = StoreAlias::normalize($value);
        if ($normalized === '') {
            return;
        }

        StoreAlias::query()->updateOrCreate(
            ['normalized' => $normalized, 'source' => 'merge'],
            ['store_id' => $target->id, 'name' => $value],
        );
    }
}

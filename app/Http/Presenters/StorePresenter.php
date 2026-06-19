<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\Store;

final class StorePresenter
{
    /**
     * Compact shape for grids/carousels.
     *
     * @return array<string, mixed>
     */
    public static function card(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'slug' => $store->slug,
            'logo' => $store->logo ? asset('storage/'.$store->logo) : null,
            'description' => $store->description,
            'rating' => $store->rating,
            'coupons_count' => $store->relationLoaded('coupons')
                ? $store->coupons->count()
                : ($store->coupons_count ?? null),
            'url' => route('stores.show', $store->slug),
            'go_url' => route('go.store', $store->slug),
        ];
    }

    /**
     * Full shape for the store detail page.
     *
     * @return array<string, mixed>
     */
    public static function full(Store $store): array
    {
        return array_merge(self::card($store), [
            'about' => $store->about,
            'rating_count' => $store->rating_count,
            'countries' => $store->countries,
            'meta_title' => $store->meta_title,
            'meta_description' => $store->meta_description,
            'categories' => $store->relationLoaded('categories')
                ? CategoryPresenter::collection($store->categories)
                : [],
        ]);
    }

    /**
     * @param  iterable<int, Store>  $stores
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $stores): array
    {
        $out = [];
        foreach ($stores as $store) {
            $out[] = self::card($store);
        }

        return $out;
    }
}

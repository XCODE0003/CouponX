<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Store;
use App\Models\StoreAlias;
use App\Support\Domain;
use App\Support\StoreName;

/**
 * Maps an incoming store onto a single canonical Store entity.
 *
 * Matching order:
 *   1. by domain (host of the website URL) — the same merchant across networks
 *      (Admitad, Indoleads, …) collapses into one store card;
 *   2. by name alias / canonical name (fallback for missing/unknown domains).
 *
 * Manually edited fields (name, slug, SEO, description, logos) are NEVER
 * overwritten by an import — only NULL fields (domain, website) are backfilled.
 */
class StoreResolver
{
    /**
     * @param  array<int, string>  $countries  ISO-2 geo from the network, applied
     *                                         only when the store has none yet.
     */
    public function resolve(string $name, ?string $website, string $source, array $countries = []): Store
    {
        $domain = Domain::fromUrl($website);

        $store = $domain !== null
            ? Store::query()->where('domain', $domain)->first()
            : null;

        // A merged store records its old domain as an alias → route back to canonical
        // instead of re-creating the duplicate on the next import.
        if ($store === null && $domain !== null) {
            $domainAliasId = StoreAlias::query()->where('normalized', $domain)->value('store_id');
            if ($domainAliasId !== null) {
                $store = Store::query()->find((int) $domainAliasId);
            }
        }

        if ($store === null) {
            $normalized = StoreAlias::normalize($name);
            $aliasStoreId = StoreAlias::query()->where('normalized', $normalized)->value('store_id');
            $store = $aliasStoreId !== null
                ? Store::query()->find((int) $aliasStoreId)
                : Store::query()->whereRaw('lower(name) = ?', [$normalized])->first();
        }

        if ($store === null) {
            $clean = StoreName::clean($name);
            $store = Store::query()->create([
                'name' => $clean,
                'slug' => $this->uniqueSlug($clean),
                'website_url' => $website,
                'domain' => $domain,
                'countries' => $countries !== [] ? $countries : null,
                // Imported stores stay inactive until reviewed (product decision 2026-06-25).
                'is_active' => false,
            ]);
        } else {
            // Backfill only empty fields — never clobber manually edited data.
            $fill = [];
            if ($domain !== null && $store->domain === null) {
                $fill['domain'] = $domain;
            }
            if ($website !== null && ($store->website_url === null || $store->website_url === '')) {
                $fill['website_url'] = $website;
            }
            if ($countries !== [] && ($store->countries === null || $store->countries === [])) {
                $fill['countries'] = $countries;
            }
            if ($fill !== []) {
                $store->forceFill($fill)->save();
            }
        }

        // Remember the raw incoming name so name-based matching keeps working.
        $store->aliases()->updateOrCreate(
            ['normalized' => StoreAlias::normalize($name), 'source' => $source],
            ['name' => $name],
        );

        return $store;
    }

    private function uniqueSlug(string $name): string
    {
        $base = StoreName::slug($name);
        $slug = $base;
        $i = 2;

        while (Store::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

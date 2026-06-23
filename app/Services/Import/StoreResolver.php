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
    public function resolve(string $name, ?string $website, string $source): Store
    {
        $domain = Domain::fromUrl($website);

        $store = $domain !== null
            ? Store::query()->where('domain', $domain)->first()
            : null;

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
                // Imported stores are visible immediately (product decision 2026-06-22).
                'is_active' => true,
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

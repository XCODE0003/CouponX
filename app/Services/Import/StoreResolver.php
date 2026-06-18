<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Models\Store;
use App\Models\StoreAlias;
use Illuminate\Support\Str;

/**
 * Maps an incoming store name onto a single canonical Store entity, so that
 * e.g. "AliExpress WW", "AliExpress RU" and "AliExpress Global" all resolve to
 * one store. Unknown stores are created inactive, pending admin review.
 */
class StoreResolver
{
    public function resolve(string $name, ?string $website, string $source): Store
    {
        $normalized = StoreAlias::normalize($name);

        $alias = StoreAlias::query()->where('normalized', $normalized)->first();
        if ($alias !== null) {
            return $alias->store;
        }

        // Match an existing store by its canonical name before creating a new one.
        $store = Store::query()->whereRaw('lower(name) = ?', [$normalized])->first();

        if ($store === null) {
            $store = Store::query()->create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'website_url' => $website,
                'is_active' => false, // imported stores stay hidden until reviewed
            ]);
        }

        $store->aliases()->updateOrCreate(
            ['normalized' => $normalized, 'source' => $source],
            ['name' => $name],
        );

        return $store;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'store';
        $slug = $base;
        $i = 2;

        while (Store::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}

<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\CatalogCache;

/**
 * Bumps the catalog cache version whenever a public-facing catalog model
 * (store, category, coupon) is created, updated or deleted.
 */
trait BustsCatalogCache
{
    public static function bootBustsCatalogCache(): void
    {
        static::saved(static function (): void {
            CatalogCache::bump();
        });

        static::deleted(static function (): void {
            CatalogCache::bump();
        });
    }
}

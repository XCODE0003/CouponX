<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_version_bumps_when_a_store_changes(): void
    {
        $before = CatalogCache::version();

        Store::factory()->create();

        $this->assertGreaterThan($before, CatalogCache::version());
    }

    public function test_version_bumps_when_a_coupon_or_category_changes(): void
    {
        $afterStore = CatalogCache::version();

        Category::factory()->create();
        $afterCategory = CatalogCache::version();
        $this->assertGreaterThan($afterStore, $afterCategory);

        Coupon::factory()->for(Store::factory())->create();
        $this->assertGreaterThan($afterCategory, CatalogCache::version());
    }

    public function test_remember_caches_until_version_changes(): void
    {
        $calls = 0;
        $build = function () use (&$calls): string {
            $calls++;

            return 'value';
        };

        CatalogCache::remember('demo', 60, $build);
        CatalogCache::remember('demo', 60, $build);
        $this->assertSame(1, $calls); // second call served from cache

        CatalogCache::bump();
        CatalogCache::remember('demo', 60, $build);
        $this->assertSame(2, $calls); // version changed → recomputed
    }
}

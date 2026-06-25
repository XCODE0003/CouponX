<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AffiliateNetwork;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAffiliateLink;
use App\Models\StoreAlias;
use App\Services\Import\StoreResolver;
use App\Services\Stores\StoreMerger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StoreMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_merge_moves_data_records_aliases_and_deletes_source(): void
    {
        $target = Store::factory()->create(['name' => 'AliExpress', 'slug' => 'aliexpress', 'domain' => 'aliexpress.com', 'clicks_count' => 5]);
        $source = Store::factory()->create(['name' => 'AliExpress RU', 'slug' => 'aliexpress-ru', 'domain' => 'aliexpress.ru', 'clicks_count' => 3]);
        $network = AffiliateNetwork::factory()->create();

        $coupon = Coupon::factory()->create(['store_id' => $source->id]);
        StoreAffiliateLink::query()->create(['store_id' => $source->id, 'affiliate_network_id' => $network->id, 'affiliate_url' => 'https://aff.example/ru', 'is_active' => true]);
        StoreAlias::query()->create(['store_id' => $source->id, 'name' => 'AliExpress WW', 'source' => 'admitad']);

        $merged = app(StoreMerger::class)->merge($target, new Collection([$source]));

        $this->assertSame(1, $merged);
        $this->assertDatabaseMissing('stores', ['id' => $source->id]);
        $this->assertSame($target->id, $coupon->fresh()->store_id);
        $this->assertDatabaseHas('store_affiliate_links', ['store_id' => $target->id, 'affiliate_url' => 'https://aff.example/ru']);
        // Imported alias moved to the canonical store.
        $this->assertDatabaseHas('store_aliases', ['store_id' => $target->id, 'normalized' => 'aliexpress ww']);
        // Source name + domain remembered so future imports route back to the target.
        $this->assertDatabaseHas('store_aliases', ['store_id' => $target->id, 'normalized' => 'aliexpress ru', 'source' => 'merge']);
        $this->assertDatabaseHas('store_aliases', ['store_id' => $target->id, 'normalized' => 'aliexpress.ru', 'source' => 'merge']);
        $this->assertSame(8, (int) $target->fresh()->clicks_count);
        $this->assertSame('AliExpress', $target->fresh()->name); // manual name preserved
    }

    public function test_import_routes_merged_domain_back_to_canonical(): void
    {
        $target = Store::factory()->create(['name' => 'AliExpress', 'slug' => 'aliexpress', 'domain' => 'aliexpress.com']);
        $source = Store::factory()->create(['name' => 'AliExpress RU', 'slug' => 'aliexpress-ru', 'domain' => 'aliexpress.ru']);

        app(StoreMerger::class)->merge($target, new Collection([$source]));

        // A later import of the merged domain must reuse the canonical store, not duplicate.
        $resolved = app(StoreResolver::class)->resolve('AliExpress Russia', 'https://aliexpress.ru/deals', 'admitad');

        $this->assertSame($target->id, $resolved->id);
        $this->assertSame(1, Store::query()->count());
    }
}

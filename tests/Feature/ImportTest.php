<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Models\AffiliateNetwork;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAlias;
use App\Services\Import\AdapterRegistry;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\CouponImporter;
use App\Services\Import\DTO\CouponDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, CouponDraft>  $drafts
     */
    private function registerAdapter(array $drafts): void
    {
        $adapter = new class($drafts) implements ImportAdapter
        {
            /** @param array<int, CouponDraft> $drafts */
            public function __construct(private array $drafts) {}

            public function key(): string
            {
                return 'fake';
            }

            public function fetch(AffiliateNetwork $network): iterable
            {
                return $this->drafts;
            }
        };

        app(AdapterRegistry::class)->register($adapter);
    }

    private function fakeNetwork(): AffiliateNetwork
    {
        return AffiliateNetwork::factory()->create(['integration' => 'fake', 'is_active' => true]);
    }

    public function test_manual_adapter_is_registered_by_default(): void
    {
        $this->assertTrue(app(AdapterRegistry::class)->has('manual'));
    }

    public function test_import_creates_store_and_coupon(): void
    {
        $this->registerAdapter([
            new CouponDraft(
                storeName: 'Brand New Store',
                storeWebsite: 'https://brand.example.com',
                externalId: 'ext-1',
                type: CouponType::Code,
                title: ['en' => '10% Off', 'ru' => 'Скидка 10%'],
                code: 'SAVE10',
            ),
        ]);
        $network = $this->fakeNetwork();

        $result = app(CouponImporter::class)->import($network);

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('coupons', ['source' => $network->slug, 'external_id' => 'ext-1', 'code' => 'SAVE10']);
        // New stores stay inactive until reviewed (product decision 2026-06-25).
        $store = Store::query()->where('name', 'Brand New Store')->first();
        $this->assertNotNull($store);
        $this->assertFalse($store->is_active);
        $this->assertDatabaseHas('store_aliases', ['store_id' => $store->id, 'normalized' => 'brand new store']);
        $this->assertNotNull($network->fresh()->last_imported_at);
    }

    public function test_reimport_updates_instead_of_duplicating(): void
    {
        $draft = new CouponDraft(
            storeName: 'Repeat Store',
            storeWebsite: null,
            externalId: 'ext-9',
            type: CouponType::Code,
            title: ['en' => 'First'],
            code: 'CODE',
        );
        $this->registerAdapter([$draft]);
        $network = $this->fakeNetwork();

        app(CouponImporter::class)->import($network);
        $second = app(CouponImporter::class)->import($network);

        $this->assertSame(0, $second->created);
        $this->assertSame(1, $second->updated);
        $this->assertSame(1, Coupon::query()->where('external_id', 'ext-9')->count());
    }

    public function test_aliases_resolve_multiple_names_to_one_store(): void
    {
        // Admin set up a single AliExpress store with two aliases.
        $store = Store::factory()->create(['name' => 'AliExpress', 'slug' => 'aliexpress']);
        StoreAlias::query()->create(['store_id' => $store->id, 'name' => 'AliExpress WW', 'source' => 'fake']);
        StoreAlias::query()->create(['store_id' => $store->id, 'name' => 'AliExpress RU', 'source' => 'fake']);

        $this->registerAdapter([
            new CouponDraft('AliExpress WW', null, 'a-1', CouponType::Deal, ['en' => 'Deal A']),
            new CouponDraft('AliExpress RU', null, 'a-2', CouponType::Deal, ['en' => 'Deal B']),
        ]);
        $network = $this->fakeNetwork();

        app(CouponImporter::class)->import($network);

        $this->assertSame(1, Store::query()->where('slug', 'like', 'aliexpress%')->count());
        $this->assertSame(2, $store->coupons()->count());
    }

    public function test_import_command_runs_for_manual_networks_as_noop(): void
    {
        AffiliateNetwork::factory()->create(['integration' => 'manual', 'is_active' => true]);

        $this->artisan('coupons:import')->assertSuccessful();

        $this->assertSame(0, Coupon::query()->count());
    }

    public function test_json_feed_adapter_imports_and_maps_fields(): void
    {
        Http::fake([
            '*' => Http::response([
                'coupons' => [
                    [
                        'merchant' => 'FeedStore',
                        'id' => 'f1',
                        'name' => '20% Off Everything',
                        'voucher' => 'FEED20',
                        'tracking_url' => 'https://feed.example/go',
                        'kind' => 'code',
                        'discount' => ['kind' => 'percentage', 'value' => 20],
                    ],
                ],
            ]),
        ]);

        $network = AffiliateNetwork::factory()->create([
            'integration' => 'json_feed',
            'is_active' => true,
            'config' => [
                'feed_url' => 'https://feed.example/feed.json',
                'items_path' => 'coupons',
                'fields' => [
                    'store' => 'merchant',
                    'external_id' => 'id',
                    'title' => 'name',
                    'code' => 'voucher',
                    'url' => 'tracking_url',
                    'type' => 'kind',
                    'discount_type' => 'discount.kind',
                    'discount_value' => 'discount.value',
                ],
            ],
        ]);

        $result = app(CouponImporter::class)->import($network);

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('coupons', [
            'source' => $network->slug,
            'external_id' => 'f1',
            'code' => 'FEED20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
        ]);
        $this->assertDatabaseHas('stores', ['name' => 'FeedStore']);
    }

    public function test_json_feed_api_failure_bubbles_up_for_alerting(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $network = AffiliateNetwork::factory()->create([
            'integration' => 'json_feed',
            'is_active' => true,
            'config' => ['feed_url' => 'https://feed.example/down.json'],
        ]);

        $this->expectException(RequestException::class);
        app(CouponImporter::class)->import($network);
    }
}

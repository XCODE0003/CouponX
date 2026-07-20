<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Jobs\LogClick;
use App\Models\AffiliateNetwork;
use App\Models\Click;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAffiliateLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use RefreshDatabase;

    private function store(array $attributes = []): Store
    {
        return Store::factory()->create(array_merge([
            'slug' => 'nike',
            'website_url' => 'https://www.nike.com',
            'is_active' => true,
        ], $attributes));
    }

    public function test_store_redirect_resolves_affiliate_link_and_injects_utm(): void
    {
        $network = AffiliateNetwork::factory()->create([
            'tracking_template' => 'https://track.net/c?url={target}',
            'default_utm' => ['utm_source' => 'couponx'],
        ]);
        $store = $this->store(['default_affiliate_network_id' => $network->id]);
        StoreAffiliateLink::factory()->create([
            'store_id' => $store->id,
            'affiliate_network_id' => $network->id,
            'country_code' => null,
            'affiliate_url' => 'https://www.nike.com/deals',
            'is_active' => true,
        ]);

        $response = $this->get('/go/nike');

        $response->assertRedirect();
        $target = $response->headers->get('Location');
        $this->assertStringStartsWith('https://track.net/c?url=', $target);

        $decoded = urldecode($target);
        $this->assertStringContainsString('utm_source=couponx', $decoded);
        $this->assertStringContainsString('utm_campaign=nike', $decoded);
        $this->assertStringContainsString('nike.com/deals', $decoded);
    }

    public function test_store_redirect_falls_back_to_website_when_no_link(): void
    {
        $this->store();

        $response = $this->get('/go/nike');

        $response->assertRedirect();
        $this->assertStringContainsString('nike.com', (string) $response->headers->get('Location'));
        $this->assertStringContainsString('utm_campaign=nike', urldecode((string) $response->headers->get('Location')));
    }

    public function test_geo_aware_link_is_chosen_by_country(): void
    {
        $store = $this->store();
        StoreAffiliateLink::factory()->create([
            'store_id' => $store->id,
            'country_code' => null,
            'affiliate_url' => 'https://global.example.com',
            'is_active' => true,
        ]);
        StoreAffiliateLink::factory()->create([
            'store_id' => $store->id,
            'country_code' => 'RU',
            'affiliate_url' => 'https://ru.example.com',
            'is_active' => true,
        ]);

        $response = $this->get('/go/nike?country=RU');
        $this->assertStringContainsString('ru.example.com', urldecode((string) $response->headers->get('Location')));

        $response = $this->get('/go/nike?country=US');
        $this->assertStringContainsString('global.example.com', urldecode((string) $response->headers->get('Location')));
    }

    public function test_coupon_redirect_adds_coupon_id_and_logs_click(): void
    {
        $store = $this->store();
        $coupon = Coupon::factory()->create([
            'store_id' => $store->id,
            'type' => CouponType::Code,
            'status' => CouponStatus::Active,
            'destination_url' => 'https://www.nike.com/sale',
            'used_count' => 5,
        ]);

        $response = $this->get('/out/'.$coupon->id);

        $response->assertRedirect();
        $decoded = urldecode((string) $response->headers->get('Location'));
        $this->assertStringContainsString('coupon_id='.$coupon->id, $decoded);
        $this->assertStringContainsString('nike.com/sale', $decoded);

        $this->assertSame(1, Click::query()->where('coupon_id', $coupon->id)->count());
        $this->assertSame(1, $store->fresh()->clicks_count);
        $this->assertSame(1, $coupon->fresh()->clicks_count);

        // "Использовали N раз" must track real redirects, on top of any manual
        // baseline entered in the admin.
        $this->assertSame(6, $coupon->fresh()->used_count);
    }

    public function test_click_stores_hashed_ip_not_raw(): void
    {
        $store = $this->store();

        $this->get('/go/nike');

        $click = Click::query()->latest('id')->first();
        $this->assertNotNull($click);
        $this->assertNotNull($click->ip_hash);
        $this->assertSame(64, strlen((string) $click->ip_hash)); // sha256 hex
        $this->assertStringNotContainsString('127.0.0', (string) $click->ip_hash);
    }

    public function test_inactive_store_returns_404(): void
    {
        $this->store(['is_active' => false]);

        $this->get('/go/nike')->assertNotFound();
    }

    public function test_inactive_coupon_returns_404(): void
    {
        $store = $this->store();
        $coupon = Coupon::factory()->create([
            'store_id' => $store->id,
            'status' => CouponStatus::Draft,
        ]);

        $this->get('/out/'.$coupon->id)->assertNotFound();
    }

    public function test_redirect_dispatches_logging_job(): void
    {
        Queue::fake();
        $this->store();

        $this->get('/go/nike')->assertRedirect();

        Queue::assertPushed(LogClick::class);
    }
}

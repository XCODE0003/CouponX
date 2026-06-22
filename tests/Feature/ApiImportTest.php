<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AffiliateNetwork;
use App\Models\Coupon;
use App\Services\Import\CouponImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admitad_adapter_authenticates_and_imports(): void
    {
        Http::fake([
            'api.admitad.com/token/' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'api.admitad.com/advcampaigns/*' => Http::response([
                'results' => [[
                    'id' => 50,
                    'name' => 'AdmStore',
                    'site_url' => 'https://admstore.com',
                    'connection_status' => 'active',
                    'connected' => true,
                ]],
                '_meta' => ['count' => 1, 'limit' => 100, 'offset' => 0],
            ]),
            'api.admitad.com/coupons/*' => Http::response([
                'results' => [[
                    'id' => 901,
                    'name' => '20% off everything',
                    'promocode' => 'ADM20',
                    'description' => 'Site-wide discount',
                    'discount' => '20%',
                    'date_end' => '2030-01-01',
                    'goto_link' => 'https://ad.admitad.com/g/abc',
                    'campaign' => ['id' => 50, 'name' => 'AdmStore', 'site_url' => 'https://admstore.com'],
                ]],
                '_meta' => ['count' => 1, 'limit' => 100, 'offset' => 0],
            ]),
        ]);

        $network = AffiliateNetwork::factory()->create([
            'integration' => 'admitad',
            'is_active' => true,
            'config' => ['client_id' => 'id', 'client_secret' => 'sec', 'website_id' => 123],
        ]);

        $result = app(CouponImporter::class)->import($network);

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('coupons', [
            'source' => $network->slug,
            'external_id' => '901',
            'code' => 'ADM20',
            'type' => 'code',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'destination_url' => 'https://ad.admitad.com/g/abc',
        ]);
        $this->assertDatabaseHas('stores', ['name' => 'AdmStore']);
    }

    public function test_awin_adapter_imports_vouchers(): void
    {
        Http::fake([
            'api.awin.com/*' => Http::response([
                'data' => [[
                    'promotionId' => 555,
                    'title' => 'Extra 10% off',
                    'description' => 'Members only',
                    'type' => 'voucher',
                    'voucher' => ['code' => 'AWIN10'],
                    'urlTracking' => 'https://www.awin1.com/cread.php?x',
                    'endDate' => '2030-02-02',
                    'advertiser' => ['id' => 1, 'name' => 'AwinStore'],
                ]],
                'pagination' => ['total' => 1],
            ]),
        ]);

        $network = AffiliateNetwork::factory()->create([
            'integration' => 'awin',
            'is_active' => true,
            'config' => ['awin_token' => 'tok', 'awin_publisher_id' => 123456],
        ]);

        $result = app(CouponImporter::class)->import($network);

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('coupons', ['source' => $network->slug, 'external_id' => '555', 'code' => 'AWIN10']);
        $this->assertDatabaseHas('stores', ['name' => 'AwinStore']);
    }

    public function test_cj_adapter_parses_xml_links(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<cj-api>
  <links total-matched="1" records-returned="1" page-number="1">
    <link>
      <advertiser-name>CjStore</advertiser-name>
      <link-id>777</link-id>
      <link-name>15% off shoes</link-name>
      <description>Seasonal sale</description>
      <coupon-code>CJ15</coupon-code>
      <promotion-end-date>2030-03-03</promotion-end-date>
      <clickUrl>https://www.anrdoezrs.net/click-777</clickUrl>
    </link>
  </links>
</cj-api>
XML;

        Http::fake([
            'link-search.api.cj.com/*' => Http::response($xml, 200, ['Content-Type' => 'application/xml']),
        ]);

        $network = AffiliateNetwork::factory()->create([
            'integration' => 'cj',
            'is_active' => true,
            'config' => ['cj_token' => 'tok', 'cj_website_id' => 999],
        ]);

        $result = app(CouponImporter::class)->import($network);

        $this->assertSame(1, $result->created);
        $this->assertDatabaseHas('coupons', ['source' => $network->slug, 'external_id' => '777', 'code' => 'CJ15']);
        $this->assertDatabaseHas('stores', ['name' => 'CjStore']);
    }

    public function test_adapters_are_a_safe_noop_without_credentials(): void
    {
        Http::fake(); // any call would be recorded; we assert none happen

        foreach (['admitad', 'cj', 'awin'] as $integration) {
            $network = AffiliateNetwork::factory()->create([
                'integration' => $integration,
                'is_active' => true,
                'config' => [],
            ]);

            $result = app(CouponImporter::class)->import($network);
            $this->assertSame(0, $result->total());
        }

        Http::assertNothingSent();
        $this->assertSame(0, Coupon::query()->count());
    }

    public function test_network_config_is_encrypted_at_rest(): void
    {
        $network = AffiliateNetwork::factory()->create([
            'config' => ['client_secret' => 'super-secret-value'],
        ]);

        $raw = (string) DB::table('affiliate_networks')->where('id', $network->id)->value('config');

        $this->assertStringNotContainsString('super-secret-value', $raw); // ciphertext in DB
        $this->assertSame('super-secret-value', $network->fresh()->config['client_secret']); // decrypts via model
    }
}

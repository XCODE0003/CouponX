<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Store;
use App\Services\Affiliate\UtmBuilder;
use App\Support\IpMasker;
use App\Support\Locales;
use Tests\TestCase;

class SupportTest extends TestCase
{
    public function test_ipv4_is_masked_to_zero_last_octet(): void
    {
        $this->assertSame('192.168.1.0', IpMasker::mask('192.168.1.42'));
    }

    public function test_ipv6_is_truncated(): void
    {
        $this->assertStringEndsWith('::', IpMasker::mask('2001:db8:85a3:8d3:1319:8a2e:370:7348'));
    }

    public function test_invalid_ip_is_marked_unknown(): void
    {
        $this->assertSame('unknown', IpMasker::mask('not-an-ip'));
    }

    public function test_locales_helpers(): void
    {
        $this->assertTrue(Locales::isSupported('en'));
        $this->assertTrue(Locales::isSupported('ru'));
        $this->assertFalse(Locales::isSupported('fr'));
        $this->assertFalse(Locales::isSupported(null));
        $this->assertSame('en|ru', Locales::pattern());
        $this->assertSame('ru-RU', Locales::hreflang('ru'));
        $this->assertSame('en-US', Locales::hreflang('en'));
    }

    public function test_utm_builder_merges_query_without_clobbering_existing(): void
    {
        $store = new Store(['name' => 'Nike', 'slug' => 'nike']);
        $result = (new UtmBuilder)->apply('https://nike.com/sale?ref=abc', null, $store, null);

        $this->assertStringContainsString('ref=abc', $result);
        $this->assertStringContainsString('utm_source=couponx', $result);
        $this->assertStringContainsString('utm_campaign=nike', $result);
        $this->assertStringStartsWith('https://nike.com/sale?', $result);
    }

    public function test_utm_builder_preserves_fragment(): void
    {
        $store = new Store(['name' => 'Nike', 'slug' => 'nike']);
        $result = (new UtmBuilder)->apply('https://nike.com/p#section', null, $store, null);

        $this->assertStringEndsWith('#section', $result);
    }
}

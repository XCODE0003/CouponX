<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\AdminSeeder;
use Database\Seeders\BlogSeeder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_store_page_renders_hreflang_canonical_and_jsonld(): void
    {
        $html = $this->get('/en/store/aliexpress')->getContent();

        // hreflang alternates for both locales + x-default
        $this->assertStringContainsString('hreflang="en-US"', (string) $html);
        $this->assertStringContainsString('hreflang="ru-RU"', (string) $html);
        $this->assertStringContainsString('hreflang="x-default"', (string) $html);
        $this->assertStringContainsString('rel="alternate"', (string) $html);

        // canonical points at the current locale URL
        $this->assertMatchesRegularExpression('#<link rel="canonical" href="[^"]+/en/store/aliexpress">#', (string) $html);

        // schema.org JSON-LD
        $this->assertStringContainsString('application/ld+json', (string) $html);
        $this->assertStringContainsString('"@type":"Organization"', (string) $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', (string) $html);
        $this->assertStringContainsString('"@type":"Store"', (string) $html);
        $this->assertStringContainsString('"@type":"Offer"', (string) $html);
    }

    public function test_meta_title_and_description_are_localized(): void
    {
        $en = (string) $this->get('/en')->getContent();
        $this->assertStringContainsString('The Best Promo Codes', $en);

        $ru = (string) $this->get('/ru')->getContent();
        $this->assertStringContainsString('Лучшие промокоды', $ru);
    }

    public function test_sitemap_lists_localized_urls_with_alternates(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $xml = (string) $response->getContent();
        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString('/en/store/aliexpress', $xml);
        $this->assertStringContainsString('/ru/store/aliexpress', $xml);
        $this->assertStringContainsString('xhtml:link', $xml);
        $this->assertStringContainsString('hreflang="ru-RU"', $xml);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $body = (string) $response->getContent();
        $this->assertStringContainsString('Sitemap: ', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /go/', $body);
    }

    public function test_blog_post_has_breadcrumb_schema(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(BlogSeeder::class);

        $html = (string) $this->get('/en/blog/best-laptop-deals')->getContent();
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
    }
}

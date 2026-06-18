<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\AdminSeeder;
use Database\Seeders\BlogSeeder;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_root_redirects_to_localized_home(): void
    {
        $this->get('/')->assertRedirect('/en');
    }

    public function test_home_renders_with_stores_and_coupons(): void
    {
        $this->get('/en')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Home')
            ->where('locale', 'en')
            ->has('topStores')
            ->has('topCoupons')
            ->has('categories')
            ->has('translations.hero.title_1')
        );
    }

    public function test_store_page_shows_localized_content_and_cloaked_urls(): void
    {
        $this->get('/ru/store/aliexpress')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Store')
            ->where('locale', 'ru')
            ->where('store.name', 'AliExpress')
            ->where('store.description', 'Один из крупнейших интернет-магазинов с товарами из Китая по оптовым ценам.')
            ->has('coupons', 5)
            ->where('coupons.0.out_url', fn (string $url) => str_contains($url, '/out/'))
            ->whereNot('store', fn ($store) => isset($store['website_url']))
        );
    }

    public function test_invalid_locale_is_not_matched(): void
    {
        $this->get('/fr')->assertNotFound();
    }

    public function test_stores_index_filters_by_category(): void
    {
        $this->get('/en/stores?category=fashion')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Stores')
            ->where('activeCategory', 'fashion')
            ->has('stores')
        );
    }

    public function test_category_page_renders(): void
    {
        $this->get('/en/category/electronics')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Category')
            ->where('category.slug', 'electronics')
            ->has('stores')
            ->has('coupons')
        );
    }

    public function test_blog_index_and_post(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(BlogSeeder::class);

        $this->get('/en/blog')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Blog')->has('posts')
        );

        $this->get('/en/blog/best-laptop-deals')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/BlogPost')->where('post.slug', 'best-laptop-deals')
        );
    }

    public function test_search_returns_matches(): void
    {
        $this->get('/en/search?q=AliExpress')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Search')
            ->where('term', 'AliExpress')
            ->has('stores', 1)
        );
    }

    public function test_newsletter_subscription(): void
    {
        $this->post('/en/newsletter', ['email' => 'shopper@example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'shopper@example.com',
            'locale' => 'en',
            'status' => 'subscribed',
        ]);
    }

    public function test_alternates_are_shared_for_hreflang(): void
    {
        $this->get('/en/store/nike')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('alternates.en', fn (string $url) => str_contains($url, '/en/store/nike'))
            ->where('alternates.ru', fn (string $url) => str_contains($url, '/ru/store/nike'))
        );
    }
}

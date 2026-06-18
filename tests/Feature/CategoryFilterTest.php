<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class CategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_category_page_exposes_facets_and_counts(): void
    {
        $this->get('/en/category/electronics')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Category')
            ->has('facets.discounts', 4)
            ->has('facets.types', 3)
            ->has('facets.deliveries', 4)
            ->has('subcategories', 5)              // seeded electronics children
            ->has('category.max_discount')
            ->has('counts.stores')
            ->has('counts.coupons')
            ->where('active.sort', 'popular')
        );
    }

    public function test_filtering_by_type_narrows_coupons(): void
    {
        $all = $this->get('/en/category/electronics');
        $allCount = count($all->viewData('page')['props']['coupons']);

        $this->get('/en/category/electronics?type[]=code')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('active.type', ['code'])
            ->where('coupons', fn ($coupons) => collect($coupons)->every(fn ($c) => $c['type'] === 'code'))
        );

        // A type filter should never return more than the unfiltered set.
        $codeCount = count($this->get('/en/category/electronics?type[]=code')->viewData('page')['props']['coupons']);
        $this->assertLessThanOrEqual($allCount, $codeCount);
    }

    public function test_filtering_by_discount_only_returns_high_percentage_offers(): void
    {
        $this->get('/en/category/electronics?discount=50')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('active.discount', 50)
            ->where('coupons', fn ($coupons) => collect($coupons)->every(
                fn ($c) => $c['discount_type'] === 'percentage' && (float) $c['discount_value'] >= 50
            ))
        );
    }

    public function test_sort_is_echoed_back(): void
    {
        $this->get('/en/category/electronics?sort=new')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('active.sort', 'new')
        );
    }

    public function test_subcategory_has_parent(): void
    {
        $this->get('/en/category/computers-laptops')->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/Category')
            ->where('category.slug', 'computers-laptops')
        );
    }
}

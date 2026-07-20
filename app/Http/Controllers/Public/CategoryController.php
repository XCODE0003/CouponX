<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Http\Presenters\BlogPostPresenter;
use App\Http\Presenters\CategoryPresenter;
use App\Http\Presenters\CouponPresenter;
use App\Http\Presenters\StorePresenter;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\Countries;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /** Discount thresholds offered as filters. */
    private const DISCOUNT_BUCKETS = [10, 20, 30, 50];

    /** Cap on how many delivery-country options the filter sidebar offers. */
    private const MAX_DELIVERY_FACETS = 8;

    public function index(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['stores', 'coupons'])
            ->orderBy('position')
            ->get();

        return Inertia::render('public/Categories', [
            'categories' => CategoryPresenter::collection($categories),
            'meta' => Seo::meta((string) __('messages.categories.title'), (string) __('messages.categories.subtitle')),
        ]);
    }

    public function show(string $locale, Category $category, Request $request): Response
    {
        abort_unless($category->is_active, 404);

        // Active filter state.
        $sort = (string) $request->query('sort', 'popular');
        $minDiscount = (int) $request->query('discount', 0);
        $types = $this->stringList($request->query('type'));
        $deliveries = array_map('strtoupper', $this->stringList($request->query('delivery')));

        /** @var Collection<int, Coupon> $allCoupons */
        $allCoupons = $category->coupons()->public()->with('store')->get();

        /** @var Collection<int, Store> $catStores */
        $catStores = $category->stores()
            ->where('is_active', true)
            ->withCount(['coupons' => fn ($q) => $q->public()])
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->get();

        // Apply filters (in-memory — fine for category-sized sets).
        $filtered = $allCoupons
            ->when($minDiscount > 0, fn (Collection $c): Collection => $c->filter(
                fn (Coupon $x): bool => $x->discount_type === DiscountType::Percentage && (float) $x->discount_value >= $minDiscount
            ))
            ->when($types !== [], fn (Collection $c): Collection => $c->filter(
                fn (Coupon $x): bool => in_array($x->type->value, $types, true)
            ))
            ->when($deliveries !== [], fn (Collection $c): Collection => $c->filter(
                fn (Coupon $x): bool => $x->store !== null
                    && array_intersect($deliveries, array_map('strtoupper', (array) ($x->store->countries ?? []))) !== []
            ));

        $sorted = match ($sort) {
            'new' => $filtered->sortByDesc('created_at'),
            'expiring' => $filtered->sortBy(fn (Coupon $c): int => $c->expires_at?->getTimestamp() ?? PHP_INT_MAX),
            default => $filtered->sortByDesc('used_count'),
        };

        $coupons = CouponPresenter::collection($sorted->values(), withStore: true);

        $children = Category::query()
            ->where('parent_id', $category->id)
            ->where('is_active', true)
            ->withCount(['stores', 'coupons' => fn ($q) => $q->public()])
            ->orderBy('position')
            ->get();

        $posts = BlogPost::query()->published()->with('author')->orderByDesc('published_at')->limit(6)->get();

        $maxDiscount = (int) $allCoupons
            ->where('discount_type', DiscountType::Percentage)
            ->max('discount_value');

        return Inertia::render('public/Category', [
            'category' => array_merge(CategoryPresenter::card($category), [
                'description' => $category->description,
                'stores_count' => $catStores->count(),
                'coupons_count' => $allCoupons->count(),
                'max_discount' => $maxDiscount ?: null,
            ]),
            'stores' => $this->presentStores($catStores, $allCoupons),
            'coupons' => $coupons,
            'subcategories' => CategoryPresenter::collection($children),
            'posts' => BlogPostPresenter::collection($posts),
            'facets' => [
                'discounts' => $this->discountFacet($allCoupons),
                'types' => $this->typeFacet($allCoupons),
                'deliveries' => $this->deliveryFacet($catStores),
            ],
            'active' => [
                'sort' => $sort,
                'discount' => $minDiscount,
                'type' => $types,
                'delivery' => $deliveries,
            ],
            'counts' => [
                'stores' => $catStores->count(),
                'coupons' => $allCoupons->count(),
                'articles' => $posts->count(),
            ],
            'meta' => Seo::meta($category->name, $category->description),
            'jsonLd' => [
                Seo::breadcrumbs([
                    ['name' => __('messages.breadcrumb_home'), 'url' => route('home')],
                    ['name' => __('messages.nav.categories'), 'url' => route('categories.index')],
                    ['name' => $category->name, 'url' => route('categories.show', $category->slug)],
                ]),
            ],
        ]);
    }

    /**
     * @param  Collection<int, Store>  $stores
     * @param  Collection<int, Coupon>  $allCoupons
     * @return array<int, array<string, mixed>>
     */
    private function presentStores(Collection $stores, Collection $allCoupons): array
    {
        return $stores->map(function ($store) use ($allCoupons): array {
            $storeCoupons = $allCoupons->where('store_id', $store->id);
            $maxDiscount = (int) $storeCoupons->where('discount_type', DiscountType::Percentage)->max('discount_value');

            return array_merge(StorePresenter::card($store), [
                // Coupons linked to THIS category via the pivot, falling back to
                // the store-wide public count — the pivot is empty for most
                // categories, which made every card read "0 промокодов".
                'coupons_count' => $storeCoupons->count() ?: (int) $store->coupons_count,
                'max_discount' => $maxDiscount ?: null,
            ]);
        })->all();
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     * @return array<int, array{value: int, count: int}>
     */
    private function discountFacet(Collection $coupons): array
    {
        return array_map(fn (int $min): array => [
            'value' => $min,
            'count' => $coupons->filter(
                fn (Coupon $c): bool => $c->discount_type === DiscountType::Percentage && (float) $c->discount_value >= $min
            )->count(),
        ], self::DISCOUNT_BUCKETS);
    }

    /**
     * @param  Collection<int, Coupon>  $coupons
     * @return array<int, array{key: string, label: string, count: int}>
     */
    private function typeFacet(Collection $coupons): array
    {
        return array_map(fn (CouponType $type): array => [
            'key' => $type->value,
            'label' => (string) __('messages.cat.types.'.$type->value),
            'count' => $coupons->where('type', $type)->count(),
        ], CouponType::cases());
    }

    /**
     * @param  Collection<int, Store>  $stores
     * @return array<int, array{code: string, label: string, count: int}>
     */
    private function deliveryFacet(Collection $stores): array
    {
        // Built from the geo actually present in the result set, so the filter can
        // never advertise countries nobody ships to. A hardcoded list showed four
        // permanently-empty "(0)" options while the column was unpopulated.
        $counts = [];

        foreach ($stores as $store) {
            foreach (array_unique(array_map('strtoupper', (array) ($store->countries ?? []))) as $code) {
                $counts[$code] = ($counts[$code] ?? 0) + 1;
            }
        }

        arsort($counts);

        return array_map(fn (string $code): array => [
            'code' => $code,
            'label' => Countries::label($code),
            'count' => $counts[$code],
        ], array_slice(array_keys($counts), 0, self::MAX_DELIVERY_FACETS));
    }

    /**
     * @return array<int, string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }

        return [];
    }
}

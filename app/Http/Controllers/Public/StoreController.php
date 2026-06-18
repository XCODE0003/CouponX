<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Presenters\CategoryPresenter;
use App\Http\Presenters\CouponPresenter;
use App\Http\Presenters\StorePresenter;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        $categorySlug = $request->query('category');

        $stores = Store::query()
            ->where('is_active', true)
            ->withCount(['coupons' => fn ($q) => $q->public()])
            ->when(is_string($categorySlug) && $categorySlug !== '', function ($q) use ($categorySlug): void {
                $q->whereHas('categories', fn ($c) => $c->where('slug', $categorySlug));
            })
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('public/Stores', [
            'stores' => StorePresenter::collection($stores->items()),
            'pagination' => [
                'current' => $stores->currentPage(),
                'last' => $stores->lastPage(),
                'total' => $stores->total(),
            ],
            'categories' => CategoryPresenter::collection(
                Category::query()->where('is_active', true)->orderBy('position')->get()
            ),
            'activeCategory' => is_string($categorySlug) ? $categorySlug : null,
            'meta' => Seo::meta((string) __('messages.stores.title'), (string) __('messages.stores.subtitle')),
        ]);
    }

    public function show(string $locale, Store $store): Response
    {
        abort_unless($store->is_active, 404);

        $store->loadCount(['coupons' => fn ($q) => $q->public()]);

        $coupons = $store->coupons()
            ->public()
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->orderByDesc('used_count')
            ->get();

        $store->load('categories');

        $similar = Store::query()
            ->where('is_active', true)
            ->where('id', '!=', $store->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $store->categories->pluck('id')))
            ->withCount(['coupons' => fn ($q) => $q->public()])
            ->limit(4)
            ->get();

        $byType = $coupons->groupBy(fn (Coupon $c): string => $c->type->value);

        $offers = $coupons->take(10)->map(fn (Coupon $c): array => Seo::offer(
            $c->title,
            $c->description,
            route('out.coupon', $c->id),
            $c->expires_at?->toIso8601String(),
            $c->code,
        ))->all();

        return Inertia::render('public/Store', [
            'store' => StorePresenter::full($store),
            'coupons' => CouponPresenter::collection($coupons),
            'counts' => [
                'all' => $coupons->count(),
                'code' => $byType->get('code')?->count() ?? 0,
                'deal' => $byType->get('deal')?->count() ?? 0,
                'sale' => $byType->get('sale')?->count() ?? 0,
            ],
            'similar' => StorePresenter::collection($similar),
            'storeCategories' => CategoryPresenter::collection($store->categories),
            'meta' => Seo::meta($store->meta_title ?: $store->name, $store->meta_description ?: $store->description),
            'jsonLd' => [
                Seo::breadcrumbs([
                    ['name' => __('messages.breadcrumb_home'), 'url' => route('home')],
                    ['name' => __('messages.nav.stores'), 'url' => route('stores.index')],
                    ['name' => $store->name, 'url' => route('stores.show', $store->slug)],
                ]),
                Seo::storeWithOffers(
                    $store->name,
                    route('stores.show', $store->slug),
                    $store->logo ? asset('storage/'.$store->logo) : null,
                    $store->rating,
                    (int) $store->rating_count,
                    $offers,
                ),
            ],
        ]);
    }
}

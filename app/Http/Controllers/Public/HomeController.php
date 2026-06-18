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
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $topStores = Store::query()
            ->where('is_active', true)
            ->withCount(['coupons' => fn ($q) => $q->public()])
            ->orderByDesc('is_featured')
            ->orderBy('position')
            ->limit(12)
            ->get();

        $topCoupons = Coupon::query()
            ->public()
            ->with('store')
            ->orderByDesc('is_featured')
            ->orderByDesc('used_count')
            ->limit(8)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->withCount('stores')
            ->orderBy('position')
            ->limit(8)
            ->get();

        return Inertia::render('public/Home', [
            'topStores' => StorePresenter::collection($topStores),
            'topCoupons' => CouponPresenter::collection($topCoupons, withStore: true),
            'categories' => CategoryPresenter::collection($categories),
            'meta' => Seo::meta(
                (string) __('messages.hero.title_1').' '.(string) __('messages.hero.title_2'),
                (string) __('messages.hero.subtitle'),
            ),
        ]);
    }
}

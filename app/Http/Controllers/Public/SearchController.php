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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));
        $locale = app()->getLocale();

        $stores = [];
        $coupons = [];
        $categories = [];

        if ($term !== '') {
            $like = '%'.$term.'%';

            $stores = StorePresenter::collection(
                Store::query()
                    ->where('is_active', true)
                    ->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('slug', 'like', $like))
                    ->withCount(['coupons' => fn ($q) => $q->public()])
                    ->limit(12)
                    ->get()
            );

            $coupons = CouponPresenter::collection(
                Coupon::query()
                    ->public()
                    ->with('store')
                    ->where(fn ($q) => $q
                        ->where('title->'.$locale, 'like', $like)
                        ->orWhere('code', 'like', $like))
                    ->limit(12)
                    ->get(),
                withStore: true
            );

            $categories = CategoryPresenter::collection(
                Category::query()
                    ->where('is_active', true)
                    ->where(fn ($q) => $q
                        ->where('name->'.$locale, 'like', $like)
                        ->orWhere('slug', 'like', $like))
                    ->withCount('stores')
                    ->limit(12)
                    ->get()
            );
        }

        return Inertia::render('public/Search', [
            'term' => $term,
            'stores' => $stores,
            'coupons' => $coupons,
            'categories' => $categories,
        ]);
    }
}

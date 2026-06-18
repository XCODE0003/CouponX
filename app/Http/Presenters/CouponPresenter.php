<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\Coupon;

final class CouponPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Coupon $coupon, bool $withStore = false): array
    {
        $data = [
            'id' => $coupon->id,
            'type' => $coupon->type->value,
            'title' => $coupon->title,
            'description' => $coupon->description,
            'terms' => $coupon->terms,
            'code' => $coupon->code,
            'has_code' => $coupon->code !== null && $coupon->code !== '',
            'discount_type' => $coupon->discount_type?->value,
            'discount_value' => $coupon->discount_value,
            'used_count' => $coupon->used_count,
            'is_featured' => $coupon->is_featured,
            'is_exclusive' => $coupon->is_exclusive,
            'is_verified' => $coupon->is_verified,
            'expires_at' => $coupon->expires_at?->toIso8601String(),
            // Cloaked outbound URL — opened in a new tab by the frontend.
            'out_url' => route('out.coupon', $coupon->id),
        ];

        if ($withStore && $coupon->relationLoaded('store') && $coupon->store !== null) {
            $data['store'] = [
                'name' => $coupon->store->name,
                'slug' => $coupon->store->slug,
                'logo' => $coupon->store->logo ? asset('storage/'.$coupon->store->logo) : null,
                'url' => route('stores.show', $coupon->store->slug),
            ];
        }

        return $data;
    }

    /**
     * @param  iterable<int, Coupon>  $coupons
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $coupons, bool $withStore = false): array
    {
        $out = [];
        foreach ($coupons as $coupon) {
            $out[] = self::card($coupon, $withStore);
        }

        return $out;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataObjects\ClickContext;
use App\Enums\CouponStatus;
use App\Jobs\LogClick;
use App\Models\Coupon;
use App\Models\Store;
use App\Services\Affiliate\AffiliateLinkResolver;
use App\Support\IpMasker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cloaked affiliate redirects:
 *   /go/{store}   → store's default (geo-aware) affiliate destination
 *   /out/{coupon} → coupon-specific destination
 *
 * Each hit logs a click and bumps counters, then 302-redirects to the
 * resolved merchant URL. The browser only ever sees the cloaked path.
 */
class RedirectController extends Controller
{
    public function __construct(private readonly AffiliateLinkResolver $resolver) {}

    public function store(Request $request, Store $store): RedirectResponse
    {
        abort_unless($store->is_active, 404);

        $country = $this->country($request);
        $url = $this->resolver->forStore($store, $country);
        $this->track($request, $store, null, $country);

        return redirect()->away($url);
    }

    public function coupon(Request $request, Coupon $coupon): RedirectResponse
    {
        abort_unless($coupon->status === CouponStatus::Active, 404);

        $coupon->loadMissing(['store', 'network']);
        abort_unless($coupon->store->is_active, 404);

        $country = $this->country($request);
        $url = $this->resolver->forCoupon($coupon, $country);
        $this->track($request, $coupon->store, $coupon, $country);

        return redirect()->away($url);
    }

    private function track(Request $request, Store $store, ?Coupon $coupon, ?string $country): void
    {
        $networkId = $coupon->affiliate_network_id ?? $store->default_affiliate_network_id;

        LogClick::dispatch(new ClickContext(
            couponId: $coupon?->id,
            storeId: $store->id,
            networkId: $networkId,
            countryCode: $country,
            locale: app()->getLocale(),
            ipHash: IpMasker::maskAndHash($request->ip()),
            userAgent: $this->truncate($request->userAgent(), 1000),
            referer: $this->truncate($request->headers->get('referer'), 2000),
            utm: $this->incomingUtm($request),
        ));
    }

    private function country(Request $request): ?string
    {
        // Cloudflare sets CF-IPCountry; fall back to a query override for testing.
        $country = $request->headers->get('CF-IPCountry') ?? $request->query('country');

        if (! is_string($country) || strlen($country) !== 2) {
            return null;
        }

        return strtoupper($country);
    }

    /**
     * @return array<string, string>|null
     */
    private function incomingUtm(Request $request): ?array
    {
        $utm = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
            $value = $request->query($key);
            if (is_string($value) && $value !== '') {
                $utm[$key] = $value;
            }
        }

        return $utm === [] ? null : $utm;
    }

    private function truncate(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, $length);
    }
}

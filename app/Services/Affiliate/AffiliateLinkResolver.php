<?php

declare(strict_types=1);

namespace App\Services\Affiliate;

use App\Models\AffiliateNetwork;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAffiliateLink;
use Illuminate\Support\Collection;

/**
 * Resolves the final outbound URL for a store or coupon:
 *   destination → geo-aware affiliate link → UTM injection → network cloaking template.
 *
 * Raw affiliate URLs never reach the browser; only /go/{store} and /out/{coupon}
 * are exposed, and this builds the real target server-side.
 */
final class AffiliateLinkResolver
{
    public function __construct(private readonly UtmBuilder $utm) {}

    public function forStore(Store $store, ?string $country = null): string
    {
        $link = $this->resolveLink($store, $country);
        $network = $link->network ?? $store->defaultNetwork;
        $target = $link?->affiliate_url ?: ($store->website_url ?? '');

        return $this->build($target, $network, $store, null);
    }

    public function forCoupon(Coupon $coupon, ?string $country = null): string
    {
        $store = $coupon->store;
        $link = $this->resolveLink($store, $country);
        $network = $coupon->network ?? $link->network ?? $store->defaultNetwork;
        $target = $coupon->destination_url ?: ($link?->affiliate_url ?: ($store->website_url ?? ''));

        return $this->build($target, $network, $store, $coupon);
    }

    private function resolveLink(Store $store, ?string $country): ?StoreAffiliateLink
    {
        /** @var Collection<int, StoreAffiliateLink> $links */
        $links = $store->affiliateLinks()->where('is_active', true)->get();

        if ($country !== null) {
            $match = $links
                ->where('country_code', strtoupper($country))
                ->sortByDesc('priority')
                ->first();

            if ($match !== null) {
                return $match;
            }
        }

        return $links->whereNull('country_code')->sortByDesc('priority')->first()
            ?? $links->sortByDesc('priority')->first();
    }

    private function build(string $target, ?AffiliateNetwork $network, Store $store, ?Coupon $coupon): string
    {
        if ($target === '') {
            return $store->website_url ?? url('/');
        }

        $target = $this->utm->apply($target, $network, $store, $coupon);

        $template = $network?->tracking_template;
        if (is_string($template) && str_contains($template, '{target}')) {
            return str_replace('{target}', rawurlencode($target), $template);
        }

        return $target;
    }
}

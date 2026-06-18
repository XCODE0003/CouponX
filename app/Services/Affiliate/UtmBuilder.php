<?php

declare(strict_types=1);

namespace App\Services\Affiliate;

use App\Models\AffiliateNetwork;
use App\Models\Coupon;
use App\Models\Store;

/**
 * Appends UTM tracking parameters to the merchant destination URL
 * (utm_source, utm_campaign, coupon_id, plus any network defaults).
 */
final class UtmBuilder
{
    public function apply(string $url, ?AffiliateNetwork $network, Store $store, ?Coupon $coupon): string
    {
        $params = array_merge(
            [
                'utm_source' => 'couponx',
                'utm_medium' => 'affiliate',
                'utm_campaign' => $store->slug,
            ],
            $this->stringMap($network?->default_utm),
        );

        if ($coupon !== null) {
            $params['coupon_id'] = (string) $coupon->id;
        }

        return $this->mergeQuery($url, $params);
    }

    /**
     * @param  array<string, string>  $params
     */
    public function mergeQuery(string $url, array $params): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return $url;
        }

        parse_str($parts['query'] ?? '', $existing);
        $merged = array_merge($existing, $params);

        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';
        $query = $merged === [] ? '' : '?'.http_build_query($merged);

        return $scheme.$host.$port.$path.$query.$fragment;
    }

    /**
     * @param  array<string, mixed>|null  $map
     * @return array<string, string>
     */
    private function stringMap(?array $map): array
    {
        if ($map === null) {
            return [];
        }

        $result = [];
        foreach ($map as $key => $value) {
            if (is_scalar($value)) {
                $result[(string) $key] = (string) $value;
            }
        }

        return $result;
    }
}

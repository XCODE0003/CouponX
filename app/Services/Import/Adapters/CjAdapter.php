<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Models\AffiliateNetwork;
use App\Services\Import\Concerns\NormalizesDrafts;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\DTO\CouponDraft;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;
use Throwable;

/**
 * CJ Affiliate (Commission Junction) coupon links import via the Link Search API.
 * Docs: https://developers.cj.com/ (Link Search Service returns XML).
 *
 * network.config = {
 *   "cj_token": "...",             // CJ personal access token (Authorization header)
 *   "cj_website_id": 1234567,      // your PID / website id
 *   "cj_advertiser_ids": "joined"  // optional, default "joined"
 * }
 */
class CjAdapter implements ImportAdapter
{
    use NormalizesDrafts;

    private const BASE = 'https://link-search.api.cj.com/v2/link-search';

    private const PAGE = 100;

    private const MAX_PAGES = 50;

    public function key(): string
    {
        return 'cj';
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $token = $this->stringOrNull($config['cj_token'] ?? null);
        $websiteId = $config['cj_website_id'] ?? null;

        if ($token === null || ! is_scalar($websiteId) || (string) $websiteId === '') {
            return;
        }

        $base = [
            'website-id' => $websiteId,
            'advertiser-ids' => $this->stringOrNull($config['cj_advertiser_ids'] ?? null) ?? 'joined',
            'promotion-type' => 'coupon',
            'records-per-page' => self::PAGE,
        ];

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::withToken($token)
                ->get(self::BASE, $base + ['page-number' => $page])
                ->throw();

            $xml = $this->parse($response->body());
            if ($xml === null || ! isset($xml->links->link)) {
                return;
            }

            foreach ($xml->links->link as $link) {
                yield $this->toDraft($link);
            }

            $returned = (int) ($xml->links['records-returned'] ?? 0);
            if ($returned < self::PAGE) {
                return;
            }
        }
    }

    private function parse(string $body): ?SimpleXMLElement
    {
        if ($body === '') {
            return null;
        }

        try {
            $xml = simplexml_load_string($body);

            return $xml === false ? null : $xml;
        } catch (Throwable) {
            return null;
        }
    }

    private function toDraft(SimpleXMLElement $link): CouponDraft
    {
        $code = $this->stringOrNull((string) $link->{'coupon-code'});
        $title = $this->stringOrNull((string) $link->{'link-name'})
            ?? $this->stringOrNull((string) $link->description)
            ?? 'Coupon';

        return new CouponDraft(
            storeName: $this->stringOrNull((string) $link->{'advertiser-name'}) ?? 'CJ advertiser',
            storeWebsite: null,
            externalId: (string) ($this->stringOrNull((string) $link->{'link-id'}) ?? md5($link->asXML() ?: $title)),
            type: $this->typeFromCode($code),
            title: $this->localized($title),
            description: $this->localizedNullable($this->stringOrNull((string) $link->description)),
            code: $code,
            affiliateUrl: $this->stringOrNull((string) $link->clickUrl),
            discountType: null,
            discountValue: null,
            expiresAt: $this->parseDate((string) $link->{'promotion-end-date'}),
        );
    }
}

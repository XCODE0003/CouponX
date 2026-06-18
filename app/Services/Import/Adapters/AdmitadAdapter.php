<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Models\AffiliateNetwork;
use App\Services\Import\Concerns\NormalizesDrafts;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\DTO\CouponDraft;
use Illuminate\Support\Facades\Http;

/**
 * Admitad coupons import.
 * Docs: https://www.admitad.com/en/webmaster/api/ (OAuth2 client_credentials + /coupons/).
 *
 * network.config = {
 *   "client_id": "...", "client_secret": "...",
 *   "scope": "coupons",            // optional, default "coupons"
 *   "website_id": 123              // optional filter
 * }
 */
class AdmitadAdapter implements ImportAdapter
{
    use NormalizesDrafts;

    private const BASE = 'https://api.admitad.com';

    private const PAGE = 100;

    private const MAX_PAGES = 50;

    public function key(): string
    {
        return 'admitad';
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $clientId = $this->stringOrNull($config['client_id'] ?? null);
        $clientSecret = $this->stringOrNull($config['client_secret'] ?? null);

        if ($clientId === null || $clientSecret === null) {
            return; // not configured yet → safe no-op
        }

        $token = $this->accessToken($clientId, $clientSecret, $this->stringOrNull($config['scope'] ?? null) ?? 'coupons');

        $query = [];
        if (isset($config['website_id'])) {
            $query['website'] = $config['website_id'];
        }

        for ($offset = 0, $page = 0; $page < self::MAX_PAGES; $offset += self::PAGE, $page++) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->get(self::BASE.'/coupons/', $query + ['limit' => self::PAGE, 'offset' => $offset])
                ->throw();

            $rows = $response->json('results');
            if (! is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                if (is_array($row)) {
                    yield $this->toDraft($row);
                }
            }

            $total = (int) $response->json('_meta.count', 0);
            if ($offset + self::PAGE >= $total) {
                return;
            }
        }
    }

    private function accessToken(string $clientId, string $clientSecret, string $scope): string
    {
        $response = Http::asForm()
            ->withBasicAuth($clientId, $clientSecret)
            ->acceptJson()
            ->post(self::BASE.'/token/', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'scope' => $scope,
            ])
            ->throw();

        return (string) $response->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function toDraft(array $row): CouponDraft
    {
        $code = $this->stringOrNull($row['promocode'] ?? null);

        return new CouponDraft(
            storeName: $this->stringOrNull(data_get($row, 'advcampaign.name')) ?? 'Admitad merchant',
            storeWebsite: $this->stringOrNull(data_get($row, 'advcampaign.site_url')),
            externalId: (string) ($row['id'] ?? md5((string) json_encode($row))),
            type: $this->typeFromCode($code, $this->stringOrNull(data_get($row, 'types.0.name'))),
            title: $this->localized($this->stringOrNull($row['name'] ?? null) ?? 'Coupon'),
            description: $this->localizedNullable($this->stringOrNull($row['description'] ?? null)),
            code: $code,
            affiliateUrl: $this->stringOrNull($row['goto_link'] ?? $row['short_link'] ?? null),
            discountType: $this->discountType($this->discountKind($row)),
            discountValue: $this->numberFrom($row['discount'] ?? null),
            expiresAt: $this->parseDate($row['date_end'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function discountKind(array $row): ?string
    {
        $discount = $row['discount'] ?? null;

        return is_string($discount) && str_contains($discount, '%') ? 'percentage' : null;
    }
}

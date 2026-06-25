<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Enums\DiscountType;
use App\Models\AffiliateNetwork;
use App\Services\Import\Concerns\NormalizesDrafts;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\Contracts\ProvidesPrograms;
use App\Services\Import\DTO\CouponDraft;
use App\Services\Import\DTO\ProgramDraft;
use Illuminate\Support\Facades\Http;

/**
 * Admitad coupons import.
 * Docs: https://www.admitad.com/en/webmaster/api/
 *
 * Imports only the publisher's ACCEPTED programs ("My affiliate programs"):
 *   1. /advcampaigns/website/{website_id}/ → campaigns, kept where connection_status = active.
 *   2. /coupons/website/{website_id}/      → coupons (with promocode + goto_link),
 *      grouped onto a store per campaign.
 *
 * network.config = {
 *   "client_id": "...", "client_secret": "...",
 *   "website_id": 123   // REQUIRED — the website-scoped endpoints need it.
 * }
 */
class AdmitadAdapter implements ImportAdapter, ProvidesPrograms
{
    use NormalizesDrafts;

    private const BASE = 'https://api.admitad.com';

    private const SCOPE = 'coupons_for_website advcampaigns_for_website';

    private const PAGE = 100;

    private const MAX_PAGES = 200;

    /** @var array<string, array<int, array{name: string, site_url: ?string, gotolink: ?string}>> */
    private array $campaignCache = [];

    public function key(): string
    {
        return 'admitad';
    }

    /**
     * Accepted programs become stores (with their affiliate link) even without
     * coupons — e.g. Aviasales.
     */
    public function programs(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $clientId = $this->stringOrNull($config['client_id'] ?? null);
        $clientSecret = $this->stringOrNull($config['client_secret'] ?? null);
        $websiteId = $this->stringOrNull($config['website_id'] ?? null);

        if ($clientId === null || $clientSecret === null || $websiteId === null) {
            return;
        }

        $token = $this->accessToken($clientId, $clientSecret);

        foreach ($this->activeCampaigns($token, $websiteId) as $id => $campaign) {
            yield new ProgramDraft(
                name: $campaign['name'],
                website: $campaign['site_url'],
                affiliateUrl: $campaign['gotolink'],
                externalId: (string) $id,
            );
        }
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $clientId = $this->stringOrNull($config['client_id'] ?? null);
        $clientSecret = $this->stringOrNull($config['client_secret'] ?? null);
        $websiteId = $this->stringOrNull($config['website_id'] ?? null);

        // website_id is required: the public /coupons/ endpoint exposes neither the
        // promo code nor the affiliate goto_link, so we must use website-scoped ones.
        if ($clientId === null || $clientSecret === null || $websiteId === null) {
            return;
        }

        $token = $this->accessToken($clientId, $clientSecret);

        // Only import coupons belonging to programs we are actually accepted into.
        $active = $this->activeCampaigns($token, $websiteId);
        if ($active === []) {
            return;
        }

        foreach ($this->paginate($token, self::BASE."/coupons/website/{$websiteId}/") as $row) {
            $campaignId = data_get($row, 'campaign.id');
            if ($campaignId === null || ! isset($active[(int) $campaignId])) {
                continue; // pending / not-accepted program → skip
            }

            yield $this->toDraft($row, $active[(int) $campaignId]);
        }
    }

    /**
     * Campaigns the publisher is accepted into, keyed by id. Memoised per website
     * so programs() and fetch() share a single advcampaigns scan within an import.
     *
     * @return array<int, array{name: string, site_url: ?string, gotolink: ?string}>
     */
    private function activeCampaigns(string $token, string $websiteId): array
    {
        if (isset($this->campaignCache[$websiteId])) {
            return $this->campaignCache[$websiteId];
        }

        $campaigns = [];

        foreach ($this->paginate($token, self::BASE."/advcampaigns/website/{$websiteId}/") as $row) {
            // Only "Joined" programs (connection_status = active). Pending / under
            // moderation / declined / not-joined-yet are skipped.
            if ($this->stringOrNull(data_get($row, 'connection_status')) !== 'active') {
                continue;
            }

            $id = data_get($row, 'id');
            $name = $this->stringOrNull(data_get($row, 'name'));
            if ($id === null || $name === null) {
                continue;
            }

            $campaigns[(int) $id] = [
                'name' => $name,
                'site_url' => $this->stringOrNull(data_get($row, 'site_url')),
                'gotolink' => $this->stringOrNull(data_get($row, 'gotolink')),
            ];
        }

        return $this->campaignCache[$websiteId] = $campaigns;
    }

    /**
     * Offset-paginate an Admitad list endpoint, yielding each result row.
     *
     * @return iterable<int, array<string, mixed>>
     */
    private function paginate(string $token, string $url): iterable
    {
        for ($offset = 0, $page = 0; $page < self::MAX_PAGES; $offset += self::PAGE, $page++) {
            $response = Http::withToken($token)
                ->timeout(120)            // advcampaigns pages can be large/slow
                ->retry(2, 3000)          // ride out transient API timeouts
                ->acceptJson()
                ->get($url, ['limit' => self::PAGE, 'offset' => $offset])
                ->throw();

            $rows = $response->json('results');
            if (! is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                if (is_array($row)) {
                    yield $row;
                }
            }

            $total = (int) $response->json('_meta.count', 0);
            if ($offset + self::PAGE >= $total) {
                return;
            }
        }
    }

    private function accessToken(string $clientId, string $clientSecret): string
    {
        $response = Http::asForm()
            ->timeout(30)
            ->retry(2, 2000)
            ->withBasicAuth($clientId, $clientSecret)
            ->acceptJson()
            ->post(self::BASE.'/token/', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'scope' => self::SCOPE,
            ])
            ->throw();

        return (string) $response->json('access_token');
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{name: string, site_url: ?string, gotolink: ?string}  $campaign
     */
    private function toDraft(array $row, array $campaign): CouponDraft
    {
        $code = $this->normalizeCode($this->stringOrNull($row['promocode'] ?? null));

        return new CouponDraft(
            storeName: $campaign['name'],
            storeWebsite: $campaign['site_url'],
            externalId: (string) ($row['id'] ?? md5((string) json_encode($row))),
            // A real promo code → Code; otherwise it's a no-code deal (redirect button).
            type: $this->typeFromCode($code),
            title: $this->localized($this->stringOrNull($row['name'] ?? null) ?? ($this->stringOrNull($row['short_name'] ?? null) ?? 'Coupon')),
            description: $this->localizedNullable($this->stringOrNull($row['description'] ?? null)),
            code: $code,
            affiliateUrl: $this->stringOrNull($row['goto_link'] ?? null),
            discountType: $this->discountKind($this->stringOrNull($row['discount'] ?? null)),
            discountValue: $this->numberFrom($row['discount'] ?? null),
            expiresAt: $this->parseDate($row['date_end'] ?? null),
        );
    }

    /**
     * Admitad uses placeholders like "НЕ НУЖЕН"/"no code needed" for deals without
     * a real code — treat those (and blanks) as no code.
     */
    private function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $code = trim($code);
        if ($code === '') {
            return null;
        }

        if (preg_match('/^(не\s*нужен|no\s*code|not\s*needed|без\s*кода)/iu', $code) === 1) {
            return null;
        }

        return $code;
    }

    private function discountKind(?string $discount): ?DiscountType
    {
        if ($discount === null) {
            return null;
        }

        if (str_contains($discount, '%')) {
            return $this->discountType('percentage');
        }

        return $this->numberFrom($discount) !== null
            ? $this->discountType('fixed')
            : null;
    }
}

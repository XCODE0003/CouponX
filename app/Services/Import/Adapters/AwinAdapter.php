<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Models\AffiliateNetwork;
use App\Services\Import\Concerns\NormalizesDrafts;
use App\Services\Import\Contracts\ImportAdapter;
use App\Services\Import\DTO\CouponDraft;
use Illuminate\Support\Facades\Http;

/**
 * Awin promotions (vouchers) import.
 * Docs: https://wiki.awin.com/index.php/Publisher_Promotions_API
 *
 * network.config = {
 *   "awin_token": "...",          // OAuth2 personal API token
 *   "awin_publisher_id": 123456
 * }
 */
class AwinAdapter implements ImportAdapter
{
    use NormalizesDrafts;

    private const BASE = 'https://api.awin.com';

    private const PAGE = 100;

    private const MAX_PAGES = 50;

    public function key(): string
    {
        return 'awin';
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        $config = $network->config ?? [];
        $token = $this->stringOrNull($config['awin_token'] ?? null);
        $publisherId = $config['awin_publisher_id'] ?? null;

        if ($token === null || ! is_scalar($publisherId) || (string) $publisherId === '') {
            return;
        }

        $url = self::BASE.'/publishers/'.$publisherId.'/promotions/';

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post($url, [
                    'filters' => ['membership' => 'joined', 'type' => 'voucher'],
                    'pagination' => ['page' => $page, 'pageSize' => self::PAGE],
                ])
                ->throw();

            $rows = $response->json('data');
            if (! is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                if (is_array($row)) {
                    yield $this->toDraft($row);
                }
            }

            $total = (int) $response->json('pagination.total', 0);
            if ($page * self::PAGE >= $total) {
                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function toDraft(array $row): CouponDraft
    {
        $code = $this->stringOrNull(data_get($row, 'voucher.code'));

        return new CouponDraft(
            storeName: $this->stringOrNull(data_get($row, 'advertiser.name')) ?? 'Awin advertiser',
            storeWebsite: $this->stringOrNull(data_get($row, 'advertiser.displayUrl')),
            externalId: (string) ($row['promotionId'] ?? md5((string) json_encode($row))),
            type: $this->typeFromCode($code, $this->stringOrNull($row['type'] ?? null)),
            title: $this->localized($this->stringOrNull($row['title'] ?? null) ?? 'Promotion'),
            description: $this->localizedNullable($this->stringOrNull($row['description'] ?? null)),
            code: $code,
            affiliateUrl: $this->stringOrNull($row['urlTracking'] ?? null),
            discountType: null,
            discountValue: $this->numberFrom($row['title'] ?? null),
            expiresAt: $this->parseDate($row['endDate'] ?? null),
        );
    }
}

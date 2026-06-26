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
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Indoleads import.
 * Docs: https://app.indoleads.com (Publisher API).
 *
 * Joined-only: /getOffers returns a real `tracking_link` only for offers the
 * publisher can actually run (open or approved); offers needing approval return
 * an error string instead — those are skipped. /getCoupons already returns only
 * coupons of approved offers.
 *
 * network.config = {
 *   "token": "...",      // API token (Account -> Api Settings)
 *   "source_id": 1234    // publisher source (4.2 /sources) — needed for tracking links
 * }
 */
class IndoleadsAdapter implements ImportAdapter, ProvidesPrograms
{
    use NormalizesDrafts;

    private const BASE = 'https://app.indoleads.com/api';

    private const PAGE = 100;

    private const MAX_PAGES = 200;

    public function key(): string
    {
        return 'indoleads';
    }

    public function programs(AffiliateNetwork $network): iterable
    {
        [$token, $sourceId] = $this->credentials($network);
        if ($token === null || $sourceId === null) {
            return;
        }

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = $this->get($token, '/getOffers', [
                'source_id' => $sourceId,
                'status' => 'active',
                'limit' => self::PAGE,
                'page' => $page,
            ]);

            $rows = $response->json('data');
            if (! is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                // A valid tracking link means we may run the offer (joined/open).
                $tracking = $this->validUrl($this->stringOrNull(data_get($row, 'tracking_link')));
                if ($tracking === null) {
                    continue;
                }

                yield new ProgramDraft(
                    name: $this->stringOrNull($row['title'] ?? null) ?? 'Indoleads offer',
                    website: $this->stringOrNull($row['website_url'] ?? null),
                    affiliateUrl: $tracking,
                    externalId: (string) ($row['id'] ?? ''),
                );
            }

            $totalPages = (int) $response->json('totalPages', 0);
            if ($totalPages > 0 && $page >= $totalPages) {
                return;
            }
        }
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        [$token, $sourceId] = $this->credentials($network);
        if ($token === null || $sourceId === null) {
            return;
        }

        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $response = $this->get($token, '/getCoupons', [
                'source_id' => $sourceId,
                'page' => $page,
            ]);

            $rows = $response->json('data');
            if (! is_array($rows) || $rows === []) {
                return;
            }

            foreach ($rows as $row) {
                if (is_array($row)) {
                    yield $this->toDraft($row);
                }
            }

            // /getCoupons has no page metadata — stop on the last (partial) page.
            if (count($rows) < self::PAGE) {
                return;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function toDraft(array $row): CouponDraft
    {
        $code = $this->normalizeCode($this->stringOrNull($row['code'] ?? null));

        $title = $this->stringOrNull($row['short_description'] ?? null)
            ?? $this->stringOrNull($row['description'] ?? null)
            ?? 'Coupon';

        return new CouponDraft(
            // Coupons carry only the offer title; the program pass already created
            // the store (with its domain), so this resolves to it by name.
            storeName: $this->stringOrNull(data_get($row, 'offer.title')) ?? 'Indoleads offer',
            storeWebsite: $this->stringOrNull(data_get($row, 'offer.preview_url')),
            externalId: (string) ($row['id'] ?? md5((string) json_encode($row))),
            type: $this->typeFromCode($code),
            title: $this->localized($title),
            description: $this->localizedNullable($this->stringOrNull($row['description'] ?? null)),
            code: $code,
            affiliateUrl: $this->validUrl($this->stringOrNull($row['tracking_link'] ?? null)),
            discountType: $this->discountKind($this->stringOrNull($row['discount_type'] ?? null)),
            discountValue: $this->numberFrom($row['discount'] ?? null),
            expiresAt: $this->parseDate($row['end_duration'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $token, string $path, array $query): Response
    {
        return Http::withToken($token)
            ->timeout(120)
            ->retry(2, 3000)
            ->acceptJson()
            ->get(self::BASE.$path, $query)
            ->throw();
    }

    /** @return array{0: ?string, 1: ?string} */
    private function credentials(AffiliateNetwork $network): array
    {
        $config = $network->config ?? [];

        return [
            $this->stringOrNull($config['token'] ?? null),
            $this->stringOrNull($config['source_id'] ?? null),
        ];
    }

    private function validUrl(?string $value): ?string
    {
        return $value !== null && preg_match('#^https?://#i', $value) === 1 ? $value : null;
    }

    private function normalizeCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $code = trim($code);
        if ($code === '') {
            return null;
        }

        // Placeholders that actually mean "no code" → treat as a deal.
        if (preg_match('/^(no\s*code|not\s*needed|не\s*нужен|без\s*кода|нет\s*кода)/iu', $code) === 1) {
            return null;
        }

        return $code;
    }

    private function discountKind(?string $type): ?DiscountType
    {
        return match (strtolower((string) $type)) {
            'percent' => DiscountType::Percentage,
            'usd' => DiscountType::Fixed,
            'free shipping' => DiscountType::FreeShipping,
            'gift' => DiscountType::Other,
            default => null,
        };
    }
}

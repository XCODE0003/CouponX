<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

use App\Enums\CouponType;
use App\Enums\DiscountType;
use Illuminate\Support\Carbon;

/**
 * Network-agnostic representation of a single incoming coupon produced by an
 * import adapter. The importer maps this onto Store/Coupon entities, resolving
 * the store via aliases and deduplicating.
 */
final readonly class CouponDraft
{
    /**
     * @param  array<string, string>  $title  locale => text (or ['en' => ...])
     * @param  array<string, string>|null  $description
     * @param  array<int, string>  $categorySlugs
     */
    public function __construct(
        public string $storeName,
        public ?string $storeWebsite,
        public string $externalId,
        public CouponType $type,
        public array $title,
        public ?array $description = null,
        public ?string $code = null,
        public ?string $affiliateUrl = null,
        public ?DiscountType $discountType = null,
        public ?float $discountValue = null,
        public ?Carbon $expiresAt = null,
        public array $categorySlugs = [],
    ) {}
}

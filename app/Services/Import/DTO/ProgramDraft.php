<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

/**
 * An accepted affiliate program (advertiser). Produces/updates a Store and its
 * default affiliate link even when the program has no coupons (e.g. Aviasales).
 */
final readonly class ProgramDraft
{
    public function __construct(
        public string $name,
        public ?string $website,
        public ?string $affiliateUrl,
        public string $externalId,
    ) {}
}

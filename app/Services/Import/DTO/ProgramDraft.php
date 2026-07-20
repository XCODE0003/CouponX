<?php

declare(strict_types=1);

namespace App\Services\Import\DTO;

/**
 * An accepted affiliate program (advertiser). Produces/updates a Store and its
 * default affiliate link even when the program has no coupons (e.g. Aviasales).
 */
final readonly class ProgramDraft
{
    /**
     * @param  array<int, string>  $countries  ISO-2 geo the program ships to.
     */
    public function __construct(
        public string $name,
        public ?string $website,
        public ?string $affiliateUrl,
        public string $externalId,
        public array $countries = [],
    ) {}
}

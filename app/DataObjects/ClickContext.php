<?php

declare(strict_types=1);

namespace App\DataObjects;

/**
 * Immutable snapshot of a click, captured in the request and handed to the
 * queued logger so the redirect itself stays fast.
 */
final readonly class ClickContext
{
    /**
     * @param  array<string, mixed>|null  $utm
     */
    public function __construct(
        public ?int $couponId,
        public ?int $storeId,
        public ?int $networkId,
        public ?string $countryCode,
        public ?string $locale,
        public ?string $ipHash,
        public ?string $userAgent,
        public ?string $referer,
        public ?array $utm,
    ) {}
}

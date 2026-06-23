<?php

declare(strict_types=1);

namespace App\Services\Import\Contracts;

use App\Models\AffiliateNetwork;
use App\Services\Import\DTO\ProgramDraft;

/**
 * Optional adapter capability: yield the publisher's accepted programs so the
 * importer can create a store (with its affiliate link) even when a program
 * has no coupons.
 */
interface ProvidesPrograms
{
    /**
     * @return iterable<int, ProgramDraft>
     */
    public function programs(AffiliateNetwork $network): iterable;
}

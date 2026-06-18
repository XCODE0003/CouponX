<?php

declare(strict_types=1);

namespace App\Services\Import\Contracts;

use App\Models\AffiliateNetwork;
use App\Services\Import\DTO\CouponDraft;

/**
 * Contract every affiliate-network integration implements. New networks are
 * added by implementing this interface and registering it with the
 * AdapterRegistry — no changes to the importer are required.
 */
interface ImportAdapter
{
    /**
     * Integration key, matched against AffiliateNetwork::$integration.
     */
    public function key(): string;

    /**
     * Yield coupon drafts for the given network. May call an HTTP API, parse a
     * feed file, etc. Should throw on hard failures so the run is reported.
     *
     * @return iterable<CouponDraft>
     */
    public function fetch(AffiliateNetwork $network): iterable;
}

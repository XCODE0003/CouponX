<?php

declare(strict_types=1);

namespace App\Services\Import\Adapters;

use App\Models\AffiliateNetwork;
use App\Services\Import\Contracts\ImportAdapter;

/**
 * Built-in adapter for networks whose coupons are entered by hand in the admin
 * panel. It yields nothing, so running an import is a safe no-op.
 */
class ManualAdapter implements ImportAdapter
{
    public function key(): string
    {
        return 'manual';
    }

    public function fetch(AffiliateNetwork $network): iterable
    {
        return [];
    }
}

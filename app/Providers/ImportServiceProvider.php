<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Import\AdapterRegistry;
use App\Services\Import\Adapters\AdmitadAdapter;
use App\Services\Import\Adapters\AwinAdapter;
use App\Services\Import\Adapters\CjAdapter;
use App\Services\Import\Adapters\IndoleadsAdapter;
use App\Services\Import\Adapters\JsonFeedAdapter;
use App\Services\Import\Adapters\ManualAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the affiliate-import adapter registry. Register additional network
 * adapters here (or from a package's provider) to plug in new partner networks
 * without touching the importer.
 */
class ImportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdapterRegistry::class, function (): AdapterRegistry {
            $registry = new AdapterRegistry;

            $registry->register(new ManualAdapter);
            $registry->register(new JsonFeedAdapter);
            $registry->register(new AdmitadAdapter);
            $registry->register(new CjAdapter);
            $registry->register(new AwinAdapter);
            $registry->register(new IndoleadsAdapter);

            return $registry;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Import;

use App\Services\Import\Contracts\ImportAdapter;

/**
 * Holds the available import adapters keyed by integration. Bound as a
 * singleton so adapters can be registered at boot (or by plugins).
 */
class AdapterRegistry
{
    /** @var array<string, ImportAdapter> */
    private array $adapters = [];

    public function register(ImportAdapter $adapter): void
    {
        $this->adapters[$adapter->key()] = $adapter;
    }

    public function for(string $key): ?ImportAdapter
    {
        return $this->adapters[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->adapters[$key]);
    }

    /**
     * @return array<string, ImportAdapter>
     */
    public function all(): array
    {
        return $this->adapters;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->adapters);
    }
}

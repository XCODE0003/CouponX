<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Versioned cache for public catalog reads. Bumping the version invalidates all
 * derived keys at once — works on any cache store (Redis in production), without
 * relying on tag support.
 */
final class CatalogCache
{
    private const VERSION_KEY = 'catalog:version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, static fn (): int => 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $callback
     * @return TValue
     */
    public static function remember(string $key, DateTimeInterface|int $ttl, Closure $callback): mixed
    {
        return Cache::remember('catalog:'.self::version().':'.$key, $ttl, $callback);
    }
}

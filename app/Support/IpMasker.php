<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Privacy-preserving IP handling: the last octet (IPv4) / second half (IPv6)
 * is zeroed before hashing, so raw IPs are never stored (spec: partially
 * masked IP).
 */
final class IpMasker
{
    public static function maskAndHash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        $masked = self::mask($ip);

        return hash('sha256', $masked.'|'.config('app.key'));
    }

    public static function mask(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            $parts[3] = '0';

            return implode('.', $parts);
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            $kept = array_slice($parts, 0, 4);

            return implode(':', $kept).'::';
        }

        return 'unknown';
    }
}

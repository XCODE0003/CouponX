<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises a URL to a bare host used as the canonical key for matching the
 * same merchant across affiliate networks (e.g. "https://www.AviaSales.ru/x"
 * → "aviasales.ru").
 */
final class Domain
{
    public static function fromUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;

        return $host !== '' ? $host : null;
    }
}

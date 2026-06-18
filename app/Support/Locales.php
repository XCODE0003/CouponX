<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Central registry of supported site locales. Add a locale here and it flows
 * through routing, the locale switcher, hreflang tags and admin translation tabs.
 */
final class Locales
{
    /** @var array<string, array{label: string, native: string, region: string}> */
    public const SUPPORTED = [
        'en' => ['label' => 'English', 'native' => 'English', 'region' => 'US'],
        'ru' => ['label' => 'Russian', 'native' => 'Русский', 'region' => 'RU'],
    ];

    public const DEFAULT = 'en';

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::SUPPORTED);
    }

    public static function pattern(): string
    {
        return implode('|', self::codes());
    }

    /**
     * @return array<int, array{code: string, label: string, native: string}>
     */
    public static function forSwitcher(): array
    {
        $out = [];
        foreach (self::SUPPORTED as $code => $meta) {
            $out[] = ['code' => $code, 'label' => $meta['label'], 'native' => $meta['native']];
        }

        return $out;
    }

    /**
     * BCP-47 hreflang value, e.g. "en-US", "ru-RU".
     */
    public static function hreflang(string $locale): string
    {
        $region = self::SUPPORTED[$locale]['region'] ?? '';

        return $region === '' ? $locale : $locale.'-'.$region;
    }
}

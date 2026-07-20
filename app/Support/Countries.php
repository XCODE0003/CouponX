<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalises the geo payloads affiliate networks return into ISO-2 codes.
 *
 * Every network spells this differently — Admitad sends
 * `regions: [{"region": "RU"}]`, Indoleads sends `countries: ["RU", "UA"]` (and
 * sometimes a comma-joined string) — so parsing is deliberately permissive and
 * simply discards anything that is not a two-letter code.
 */
final class Countries
{
    /**
     * @return array<int, string> upper-case ISO-2 codes, deduplicated
     */
    public static function normalize(mixed $raw): array
    {
        $codes = [];

        foreach (self::flatten($raw) as $value) {
            $code = mb_strtoupper(trim($value), 'UTF-8');

            if (preg_match('/^[A-Z]{2}$/', $code) === 1) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    /** Human label for a code, falling back to the code when untranslated. */
    public static function label(string $code): string
    {
        $key = 'messages.countries.'.$code;
        $label = __($key);

        return is_string($label) && $label !== $key ? $label : $code;
    }

    /**
     * @return array<int, string>
     */
    private static function flatten(mixed $raw): array
    {
        if (is_string($raw)) {
            return preg_split('/[\s,;|\/]+/u', $raw) ?: [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $out = [];

        foreach ($raw as $item) {
            if (is_string($item)) {
                $out[] = $item;

                continue;
            }

            if (is_array($item)) {
                // {"region": "RU"} / {"country_code": "UA"} / {"country": {"code": "BY"}}
                foreach (['region', 'country', 'code', 'iso', 'iso_code', 'country_code'] as $key) {
                    if (array_key_exists($key, $item)) {
                        $out = array_merge($out, self::flatten($item[$key]));
                    }
                }
            }
        }

        return $out;
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Cleans affiliate-program names into a presentable store name by stripping the
 * technical suffixes networks append, e.g.:
 *   "GSASS CPL UA+KZ"     → "GSASS"
 *   "Geekmall [CPS] IT"   → "Geekmall"
 *   "Aviasales_RU"        → "Aviasales"
 *   "Parallels WW"        → "Parallels"
 */
final class StoreName
{
    /** Commission-model / traffic markers — everything from here on is technical. */
    private const MODELS = [
        'CPL', 'CPS', 'CPA', 'CPI', 'CPM', 'CPO', 'CPV', 'CPC',
        'REVSHARE', 'REV', 'SOI', 'DOI', 'CPADOI', 'INSTALL', 'INAPP',
    ];

    /** Country / region codes used as a trailing geo marker. */
    private const REGIONS = [
        'WW', 'EU', 'CIS', 'GLOBAL', 'INT', 'INTL', 'ROW', 'WORLD',
        'RU', 'UA', 'KZ', 'BY', 'AM', 'AZ', 'GE', 'MD', 'UZ', 'KG', 'TJ',
        'US', 'UK', 'GB', 'DE', 'FR', 'IT', 'ES', 'PT', 'PL', 'CZ', 'SK',
        'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'FI', 'DK', 'IE', 'GR', 'RO',
        'BG', 'HU', 'HR', 'RS', 'TR', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE',
        'IN', 'CN', 'JP', 'KR', 'ID', 'TH', 'VN', 'MY', 'PH', 'SG', 'AE',
        'SA', 'IL', 'EG', 'ZA', 'AU', 'NZ', 'CA',
    ];

    public static function clean(string $name): string
    {
        $name = trim($name);

        // Drop bracketed segments: "[CPS]", "(new)", "{ru}".
        $stripped = preg_replace('/[\[\(\{][^\]\)\}]*[\]\)\}]/u', ' ', $name) ?? $name;

        $tokens = preg_split('/[\s_]+/u', trim($stripped)) ?: [];
        $kept = [];

        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }

            $upper = mb_strtoupper($token, 'UTF-8');

            // A commission model marker ends the meaningful name immediately.
            if (in_array($upper, self::MODELS, true)) {
                break;
            }

            // A trailing geo marker (only once we already have a name) ends it too.
            if ($kept !== [] && self::isRegionToken($upper)) {
                break;
            }

            $kept[] = $token;
        }

        $result = trim(implode(' ', $kept));

        return $result !== '' ? $result : $name;
    }

    public static function slug(string $name): string
    {
        return Str::slug(self::clean($name)) ?: 'store';
    }

    /** True for "RU", "UA+KZ", "RU/UA", "DE-AT" etc. composed only of region codes. */
    private static function isRegionToken(string $upper): bool
    {
        $parts = preg_split('/[+\/,&-]/u', $upper) ?: [];
        $parts = array_filter($parts, static fn (string $p): bool => $p !== '');

        if ($parts === []) {
            return false;
        }

        foreach ($parts as $part) {
            if (! in_array($part, self::REGIONS, true)) {
                return false;
            }
        }

        return true;
    }
}

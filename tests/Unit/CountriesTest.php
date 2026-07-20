<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Countries;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CountriesTest extends TestCase
{
    /**
     * @param  array<int, string>  $expected
     */
    #[DataProvider('payloads')]
    public function test_normalize(mixed $raw, array $expected): void
    {
        $this->assertSame($expected, Countries::normalize($raw));
    }

    /** @return array<string, array{0: mixed, 1: array<int, string>}> */
    public static function payloads(): array
    {
        return [
            'null' => [null, []],
            'plain array' => [['RU', 'UA'], ['RU', 'UA']],
            'lower case' => [['ru', 'by'], ['RU', 'BY']],
            'comma string' => ['RU,UA, KZ', ['RU', 'UA', 'KZ']],
            'slash string' => ['RU/UA', ['RU', 'UA']],
            // Admitad: regions: [{"region": "RU"}, ...]
            'admitad regions' => [[['region' => 'RU'], ['region' => 'de']], ['RU', 'DE']],
            // Indoleads and friends
            'country_code objects' => [[['country_code' => 'PL']], ['PL']],
            'nested country object' => [[['country' => ['code' => 'FR']]], ['FR']],
            'deduplicates' => [['RU', 'ru', 'RU'], ['RU']],
            'drops non iso2' => [['RUS', 'Russia', '', '12', 'UA'], ['UA']],
            'ignores scalars' => [42, []],
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\StoreName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StoreNameTest extends TestCase
{
    #[DataProvider('names')]
    public function test_clean(string $input, string $expected): void
    {
        $this->assertSame($expected, StoreName::clean($input));
    }

    /** @return array<int, array{0: string, 1: string}> */
    public static function names(): array
    {
        return [
            ['GSASS CPL UA+KZ', 'GSASS'],
            ['Aviasales_RU', 'Aviasales'],
            ['Parallels WW', 'Parallels'],
            ['TVC Mall / CPS', 'TVC Mall'],
            ['H&M / CPS', 'H&M'],
            ['Charles & Keith - CPS', 'Charles & Keith'],
            ['Airpaz /', 'Airpaz'],
            ['Ticombo -', 'Ticombo'],
            ['Sasa -', 'Sasa'],
            ['hollyshop.', 'hollyshop'],
            ['Geekmall [CPS] IT', 'Geekmall'],
            ['Korston', 'Korston'],
            // Cyrillic must survive intact — a byte-based trim() with multibyte
            // dashes in its charlist used to chew off the last letter.
            ['Аквафор', 'Аквафор'],
            ['РЕДМОНД', 'РЕДМОНД'],
            ['Купер', 'Купер'],
            ['Тари Тур', 'Тари Тур'],
            // Separators inside the real name are kept; only edge ones are trimmed.
            ['Camp David | Soccx', 'Camp David | Soccx'],
            ['Meshnology - Оборудование', 'Meshnology - Оборудование'],
        ];
    }
}

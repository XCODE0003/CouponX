<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
    case FreeShipping = 'free_shipping';
    case Bogo = 'bogo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Процент',
            self::Fixed => 'Фиксированная сумма',
            self::FreeShipping => 'Бесплатная доставка',
            self::Bogo => '1+1',
            self::Other => 'Другое',
        };
    }
}

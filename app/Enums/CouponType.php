<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponType: string
{
    case Code = 'code';   // промокод
    case Deal = 'deal';   // скидка / offer without a code
    case Sale = 'sale';   // акция / sitewide sale

    public function label(): string
    {
        return match ($this) {
            self::Code => 'Промокод',
            self::Deal => 'Скидка',
            self::Sale => 'Акция',
        };
    }

    public function requiresCode(): bool
    {
        return $this === self::Code;
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum CouponStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Draft = 'draft';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Активен',
            self::Expired => 'Истёк',
            self::Draft => 'Черновик',
            self::Archived => 'В архиве',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Active;
    }
}

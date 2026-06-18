<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Editor => 'Редактор',
            self::User => 'Пользователь',
        };
    }

    /** Roles allowed into the Filament admin panel. */
    public function canAccessPanel(): bool
    {
        return in_array($this, [self::Admin, self::Editor], true);
    }
}

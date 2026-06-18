<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Models\User;

/**
 * Restricts a Filament resource to Admin users. Editors (per spec) only work
 * with coupons and the blog, so all other resources use this trait.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }
}

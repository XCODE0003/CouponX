<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces the Filament admin panel into Russian, regardless of the visitor's
 * public-site locale, so the built-in Filament UI (buttons, validation,
 * pagination) and our localized labels render in Russian.
 */
class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('ru');

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale from the {locale} route prefix (falling back to
 * cookie → Accept-Language → default) and makes it the default for all
 * generated URLs, so route('home') etc. stay within the current language.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request);

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);

        if ($request->cookie('locale') !== $locale) {
            Cookie::queue('locale', $locale, 60 * 24 * 365);
        }

        return $next($request);
    }

    private function resolve(Request $request): string
    {
        $routeLocale = $request->route('locale');
        if (is_string($routeLocale) && Locales::isSupported($routeLocale)) {
            return $routeLocale;
        }

        $cookie = $request->cookie('locale');
        if (is_string($cookie) && Locales::isSupported($cookie)) {
            return $cookie;
        }

        $preferred = $request->getPreferredLanguage(Locales::codes());
        if (Locales::isSupported($preferred)) {
            return (string) $preferred;
        }

        return Locales::DEFAULT;
    }
}

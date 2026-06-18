<?php

namespace App\Http\Middleware;

use App\Http\Presenters\CategoryPresenter;
use App\Models\Category;
use App\Support\CatalogCache;
use App\Support\Locales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // NB: Inertia evaluates share() eagerly, before route middleware (SetLocale)
        // runs. Locale-dependent props must therefore be Closures so they resolve
        // at response-build time, once the active locale has been set.
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            // i18n
            'locale' => fn (): string => app()->getLocale(),
            'locales' => Locales::forSwitcher(),
            'alternates' => fn (): array => $this->alternateUrls($request),
            'seo' => fn (): array => $this->seo($request),
            'translations' => fn (): array => $this->translations(app()->getLocale()),

            // Shared navigation/footer data
            'nav' => [
                'categories' => fn (): array => $this->navCategories(),
            ],

            // Flash toast (e.g. newsletter subscription)
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
        ];
    }

    /**
     * The same path in every supported locale (for the locale switcher & hreflang).
     *
     * @return array<string, string>
     */
    private function alternateUrls(Request $request): array
    {
        $segments = explode('/', trim($request->path(), '/'));
        if (Locales::isSupported($segments[0])) {
            array_shift($segments);
        }
        $rest = implode('/', $segments);
        $query = $request->getQueryString();
        $suffix = ($rest === '' ? '' : '/'.$rest).($query !== null ? '?'.$query : '');

        $alternates = [];
        foreach (Locales::codes() as $code) {
            $alternates[$code] = url('/'.$code.$suffix);
        }

        return $alternates;
    }

    /**
     * Canonical URL + hreflang alternates (incl. x-default) for the current page.
     *
     * @return array{canonical: string, alternates: array<int, array{hreflang: string, href: string}>}
     */
    private function seo(Request $request): array
    {
        $alternates = $this->alternateUrls($request);
        $locale = app()->getLocale();

        $links = [];
        foreach ($alternates as $code => $href) {
            $links[] = ['hreflang' => Locales::hreflang($code), 'href' => $href];
        }
        $links[] = ['hreflang' => 'x-default', 'href' => $alternates[Locales::DEFAULT] ?? (string) reset($alternates)];

        return [
            'canonical' => $alternates[$locale] ?? '',
            'alternates' => $links,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function translations(string $locale): array
    {
        $messages = Lang::get('messages', [], $locale);

        return is_array($messages) ? $messages : [];
    }

    /**
     * Nav/footer categories — shared on every request, so cached per locale
     * (Redis in production). Skipped under tests for deterministic state.
     *
     * @return array<int, array<string, mixed>>
     */
    private function navCategories(): array
    {
        $build = fn (): array => CategoryPresenter::collection(
            Category::query()
                ->where('is_active', true)
                ->orderByDesc('is_featured')
                ->orderBy('position')
                ->limit(8)
                ->get()
        );

        if (app()->runningUnitTests()) {
            return $build();
        }

        // Versioned cache — busted automatically when any store/category/coupon changes.
        return CatalogCache::remember('nav:'.app()->getLocale(), now()->addDay(), $build);
    }
}

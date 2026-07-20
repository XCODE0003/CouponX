<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Store;
use App\Support\Locales;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $entries = [];

        // Static index pages.
        foreach (['home', 'stores.index', 'categories.index', 'blog.index'] as $name) {
            array_push($entries, ...$this->entries($name, []));
        }

        Store::query()->where('is_active', true)->orderBy('id')
            ->each(function (Store $store) use (&$entries): void {
                array_push($entries, ...$this->entries('stores.show', ['store' => $store->slug]));
            });

        Category::query()->where('is_active', true)->orderBy('id')
            ->each(function (Category $category) use (&$entries): void {
                array_push($entries, ...$this->entries('categories.show', ['category' => $category->slug]));
            });

        BlogPost::query()->published()->orderBy('id')
            ->each(function (BlogPost $post) use (&$entries): void {
                array_push($entries, ...$this->entries('blog.show', ['post' => $post->slug]));
            });

        $xml = view('sitemap', ['entries' => $entries])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * One <url> per locale, all sharing the same hreflang alternate set.
     *
     * Emitting only the default locale as <loc> (with the others living solely
     * inside xhtml:link) is not enough — Google requires alternates to be
     * reciprocal, so every localised URL must be submitted in its own right.
     *
     * @param  array<string, string>  $params
     * @return array<int, array{loc: string, alternates: array<string, string>}>
     */
    private function entries(string $routeName, array $params): array
    {
        $alternates = [];
        foreach (Locales::codes() as $code) {
            $alternates[$code] = route($routeName, array_merge(['locale' => $code], $params));
        }

        return array_map(
            fn (string $loc): array => ['loc' => $loc, 'alternates' => $alternates],
            array_values($alternates),
        );
    }
}

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
            $entries[] = $this->entry($name, []);
        }

        Store::query()->where('is_active', true)->orderBy('id')
            ->each(function (Store $store) use (&$entries): void {
                $entries[] = $this->entry('stores.show', ['store' => $store->slug]);
            });

        Category::query()->where('is_active', true)->orderBy('id')
            ->each(function (Category $category) use (&$entries): void {
                $entries[] = $this->entry('categories.show', ['category' => $category->slug]);
            });

        BlogPost::query()->published()->orderBy('id')
            ->each(function (BlogPost $post) use (&$entries): void {
                $entries[] = $this->entry('blog.show', ['post' => $post->slug]);
            });

        $xml = view('sitemap', ['entries' => $entries])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * @param  array<string, string>  $params
     * @return array{loc: string, alternates: array<string, string>}
     */
    private function entry(string $routeName, array $params): array
    {
        $alternates = [];
        foreach (Locales::codes() as $code) {
            $alternates[$code] = route($routeName, array_merge(['locale' => $code], $params));
        }

        return [
            'loc' => $alternates[Locales::DEFAULT],
            'alternates' => $alternates,
        ];
    }
}

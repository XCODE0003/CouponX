<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Presenters\BlogPostPresenter;
use App\Models\BlogPost;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::query()
            ->published()
            ->with('author')
            ->orderByDesc('published_at')
            ->paginate(12);

        return Inertia::render('public/Blog', [
            'posts' => BlogPostPresenter::collection($posts->items()),
            'pagination' => [
                'current' => $posts->currentPage(),
                'last' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
            'meta' => Seo::meta((string) __('messages.blog.title'), (string) __('messages.blog.subtitle')),
        ]);
    }

    public function show(string $locale, BlogPost $post): Response
    {
        abort_unless($post->status->value === 'published', 404);

        $post->load('author');

        $related = BlogPost::query()
            ->published()
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return Inertia::render('public/BlogPost', [
            'post' => BlogPostPresenter::full($post),
            'related' => BlogPostPresenter::collection($related),
            'meta' => Seo::meta($post->meta_title ?: $post->title, $post->meta_description ?: $post->excerpt),
            'jsonLd' => [
                Seo::breadcrumbs([
                    ['name' => __('messages.breadcrumb_home'), 'url' => route('home')],
                    ['name' => __('messages.nav.blog'), 'url' => route('blog.index')],
                    ['name' => $post->title, 'url' => route('blog.show', $post->slug)],
                ]),
            ],
        ]);
    }
}

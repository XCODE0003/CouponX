<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\BlogPost;

final class BlogPostPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(BlogPost $post): array
    {
        return [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'cover_image' => $post->cover_image ? asset('storage/'.$post->cover_image) : null,
            'published_at' => $post->published_at?->toIso8601String(),
            'author' => $post->relationLoaded('author') && $post->author !== null ? $post->author->name : null,
            'url' => route('blog.show', $post->slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function full(BlogPost $post): array
    {
        return array_merge(self::card($post), [
            'body' => $post->body,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
        ]);
    }

    /**
     * @param  iterable<int, BlogPost>  $posts
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $posts): array
    {
        $out = [];
        foreach ($posts as $post) {
            $out[] = self::card($post);
        }

        return $out;
    }
}

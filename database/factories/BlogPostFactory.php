<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(5);

        return [
            'slug' => Str::slug($title),
            'title' => ['en' => $title, 'ru' => $title],
            'excerpt' => ['en' => fake()->sentence(), 'ru' => fake()->sentence()],
            'body' => ['en' => fake()->paragraphs(4, true), 'ru' => fake()->paragraphs(4, true)],
            'status' => BlogPostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => BlogPostStatus::Draft,
            'published_at' => null,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('role', 'editor')->first()
            ?? User::query()->where('role', 'admin')->first();

        $posts = [
            [
                'slug' => 'best-laptop-deals',
                'title' => ['en' => 'Best Laptop Deals This Month', 'ru' => 'Лучшие скидки на ноутбуки в этом месяце'],
                'excerpt' => ['en' => 'Our pick of the best laptop discounts available right now.', 'ru' => 'Подборка лучших скидок на ноутбуки прямо сейчас.'],
                'body' => ['en' => '<p>Looking for a new laptop? Here are the best deals we found across top stores.</p>', 'ru' => '<p>Ищете новый ноутбук? Вот лучшие предложения, которые мы нашли в популярных магазинах.</p>'],
            ],
            [
                'slug' => 'how-to-use-promo-codes',
                'title' => ['en' => 'How to Use Promo Codes and Save More', 'ru' => 'Как использовать промокоды и экономить больше'],
                'excerpt' => ['en' => 'A quick guide to getting the most out of coupon codes.', 'ru' => 'Краткое руководство по максимальной выгоде от промокодов.'],
                'body' => ['en' => '<p>Copy the code, paste it at checkout, and watch the discount apply.</p>', 'ru' => '<p>Скопируйте код, вставьте его при оформлении заказа и получите скидку.</p>'],
            ],
        ];

        foreach ($posts as $post) {
            BlogPost::query()->updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'author_id' => $author?->id,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'status' => BlogPostStatus::Published,
                    'published_at' => now()->subDays(random_int(1, 30)),
                ],
            );
        }
    }
}

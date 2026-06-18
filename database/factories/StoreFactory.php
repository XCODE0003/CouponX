<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => $name,
            'description' => ['en' => fake()->sentence(), 'ru' => fake()->sentence()],
            'about' => ['en' => fake()->paragraph(), 'ru' => fake()->paragraph()],
            'website_url' => fake()->url(),
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'rating_count' => fake()->numberBetween(10, 5000),
            'cashback_type' => 'percent',
            'cashback_value' => 'up to '.fake()->numberBetween(2, 10).'%',
            'countries' => ['US', 'RU'],
            'is_featured' => fake()->boolean(30),
            'is_active' => true,
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (): array => ['is_featured' => true]);
    }
}

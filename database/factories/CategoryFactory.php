<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'name' => ['en' => ucfirst($name), 'ru' => ucfirst($name)],
            'description' => ['en' => fake()->sentence(), 'ru' => fake()->sentence()],
            'icon' => fake()->randomElement(['tag', 'shopping-bag', 'shirt', 'home', 'sparkles', 'laptop']),
            'position' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(30),
            'is_active' => true,
        ];
    }
}

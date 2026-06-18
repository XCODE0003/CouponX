<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreAlias;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreAlias>
 */
class StoreAliasFactory extends Factory
{
    protected $model = StoreAlias::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'store_id' => Store::factory(),
            'name' => $name,
            'normalized' => StoreAlias::normalize($name),
            'source' => fake()->randomElement(['admitad', 'cj', 'awin']),
        ];
    }
}

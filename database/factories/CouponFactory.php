<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        $type = fake()->randomElement(CouponType::cases());
        $title = fake()->randomElement(['20% Off', 'Free Shipping', 'Extra 15% Off', 'Save $10']);

        return [
            'store_id' => Store::factory(),
            'type' => $type,
            'title' => ['en' => $title, 'ru' => $title],
            'description' => ['en' => fake()->sentence(), 'ru' => fake()->sentence()],
            'code' => $type === CouponType::Code ? Str::upper(Str::random(6)) : null,
            'discount_type' => fake()->randomElement(DiscountType::cases()),
            'discount_value' => fake()->numberBetween(5, 80),
            'expires_at' => fake()->boolean(70) ? fake()->dateTimeBetween('now', '+30 days') : null,
            'used_count' => fake()->numberBetween(0, 5000),
            'is_featured' => fake()->boolean(25),
            'is_verified' => fake()->boolean(70),
            'status' => CouponStatus::Active,
            'position' => fake()->numberBetween(0, 100),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => CouponStatus::Expired,
            'expires_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}

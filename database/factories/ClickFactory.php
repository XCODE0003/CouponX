<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Click;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Click>
 */
class ClickFactory extends Factory
{
    protected $model = Click::class;

    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'store_id' => Store::factory(),
            'affiliate_network_id' => null,
            'country_code' => fake()->randomElement(['US', 'RU', 'UA']),
            'locale' => fake()->randomElement(['en', 'ru']),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
            'referer' => fake()->url(),
            'utm' => ['utm_source' => 'couponx'],
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}

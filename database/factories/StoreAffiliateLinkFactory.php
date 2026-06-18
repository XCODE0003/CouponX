<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreAffiliateLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreAffiliateLink>
 */
class StoreAffiliateLinkFactory extends Factory
{
    protected $model = StoreAffiliateLink::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'affiliate_network_id' => null,
            'country_code' => null,
            'affiliate_url' => fake()->url(),
            'cashback_value' => 'up to '.fake()->numberBetween(2, 10).'%',
            'priority' => 0,
            'is_active' => true,
        ];
    }
}

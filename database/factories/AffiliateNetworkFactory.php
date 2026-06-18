<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AffiliateNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AffiliateNetwork>
 */
class AffiliateNetworkFactory extends Factory
{
    protected $model = AffiliateNetwork::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'integration' => 'manual',
            'is_active' => true,
            'tracking_template' => 'https://track.'.Str::slug($name).'.com/click?url={target}',
            'default_utm' => ['utm_source' => 'couponx', 'utm_medium' => 'affiliate'],
            'config' => [],
        ];
    }
}

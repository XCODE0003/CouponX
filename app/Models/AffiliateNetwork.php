<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Database\Factories\AffiliateNetworkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $integration
 * @property bool $is_active
 * @property string|null $tracking_template
 * @property array<string, mixed>|null $default_utm
 * @property array<string, mixed>|null $config
 */
class AffiliateNetwork extends Model
{
    /** @use HasFactory<AffiliateNetworkFactory> */
    use HasFactory;

    use RecordsActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_utm' => 'array',
            // API credentials live here — encrypted at rest.
            'config' => 'encrypted:array',
            'last_imported_at' => 'datetime',
        ];
    }

    /** @return HasMany<Store, $this> */
    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'default_affiliate_network_id');
    }

    /** @return HasMany<Coupon, $this> */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /** @return HasMany<StoreAffiliateLink, $this> */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(StoreAffiliateLink::class);
    }
}

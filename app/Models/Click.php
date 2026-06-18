<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ClickFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $coupon_id
 * @property int|null $store_id
 * @property int|null $affiliate_network_id
 * @property string|null $country_code
 * @property string|null $locale
 * @property string|null $ip_hash
 * @property array<string, mixed>|null $utm
 * @property Carbon|null $created_at
 */
class Click extends Model
{
    /** @use HasFactory<ClickFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'utm' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return BelongsTo<AffiliateNetwork, $this> */
    public function network(): BelongsTo
    {
        return $this->belongsTo(AffiliateNetwork::class, 'affiliate_network_id');
    }
}

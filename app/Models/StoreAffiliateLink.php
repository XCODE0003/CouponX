<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreAffiliateLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $affiliate_network_id
 * @property string|null $country_code
 * @property string $affiliate_url
 * @property int $priority
 * @property bool $is_active
 */
class StoreAffiliateLink extends Model
{
    /** @use HasFactory<StoreAffiliateLinkFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
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

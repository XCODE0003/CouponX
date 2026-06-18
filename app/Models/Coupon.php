<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Models\Concerns\BustsCatalogCache;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $affiliate_network_id
 * @property CouponType $type
 * @property string|null $code
 * @property DiscountType|null $discount_type
 * @property float|null $discount_value
 * @property string|null $destination_url
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property int $used_count
 * @property CouponStatus $status
 * @property bool $is_featured
 * @property bool $is_exclusive
 * @property bool $is_verified
 */
class Coupon extends Model
{
    use BustsCatalogCache;

    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    use HasTranslations;
    use RecordsActivity;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['title', 'description', 'terms'];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'status' => CouponStatus::class,
            'discount_type' => DiscountType::class,
            'discount_value' => 'float',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'used_count' => 'integer',
            'clicks_count' => 'integer',
            'success_rate' => 'integer',
            'is_exclusive' => 'boolean',
            'is_featured' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    /**
     * Active, non-expired coupons visible to the public.
     *
     * @param  Builder<Coupon>  $query
     */
    public function scopePublic(Builder $query): void
    {
        $query->where('status', CouponStatus::Active->value)
            ->where(function (Builder $q): void {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
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

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    /** @return HasMany<Click, $this> */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }
}

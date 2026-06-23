<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BustsCatalogCache;
use App\Models\Concerns\RecordsActivity;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $logo
 * @property string|null $logo_dark
 * @property string|null $website_url
 * @property string|null $domain
 * @property int|null $default_affiliate_network_id
 * @property float|null $rating
 * @property string|null $cashback_value
 * @property bool $is_featured
 * @property bool $is_active
 * @property int $position
 * @property array<int, string>|null $countries
 */
class Store extends Model
{
    use BustsCatalogCache;

    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    use HasSlug;
    use HasTranslations;
    use RecordsActivity;

    protected $guarded = ['id'];

    /** @var array<int, string> */
    public array $translatable = ['description', 'about', 'cashback_terms', 'meta_title', 'meta_description'];

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'rating_count' => 'integer',
            'countries' => 'array',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
            'clicks_count' => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return BelongsTo<AffiliateNetwork, $this> */
    public function defaultNetwork(): BelongsTo
    {
        return $this->belongsTo(AffiliateNetwork::class, 'default_affiliate_network_id');
    }

    /** @return HasMany<StoreAlias, $this> */
    public function aliases(): HasMany
    {
        return $this->hasMany(StoreAlias::class);
    }

    /** @return HasMany<StoreAffiliateLink, $this> */
    public function affiliateLinks(): HasMany
    {
        return $this->hasMany(StoreAffiliateLink::class);
    }

    /** @return HasMany<Coupon, $this> */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /** @return HasMany<Click, $this> */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}

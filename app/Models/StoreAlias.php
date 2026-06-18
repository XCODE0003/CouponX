<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreAliasFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string $normalized
 * @property string|null $source
 */
class StoreAlias extends Model
{
    /** @use HasFactory<StoreAliasFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (StoreAlias $alias): void {
            $alias->normalized = self::normalize($alias->name);
        });
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->value();
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}

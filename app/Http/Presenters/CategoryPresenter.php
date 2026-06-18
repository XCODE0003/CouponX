<?php

declare(strict_types=1);

namespace App\Http\Presenters;

use App\Models\Category;

final class CategoryPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function card(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'icon' => $category->icon,
            'stores_count' => $category->relationLoaded('stores')
                ? $category->stores->count()
                : ($category->stores_count ?? null),
            'coupons_count' => $category->coupons_count ?? null,
            'url' => route('categories.show', $category->slug),
        ];
    }

    /**
     * @param  iterable<int, Category>  $categories
     * @return array<int, array<string, mixed>>
     */
    public static function collection(iterable $categories): array
    {
        $out = [];
        foreach ($categories as $category) {
            $out[] = self::card($category);
        }

        return $out;
    }
}

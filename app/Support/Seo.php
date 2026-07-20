<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Builders for page <meta> data and schema.org JSON-LD blocks. Controllers
 * attach these as Inertia props ('meta', 'jsonLd'); the root Blade view renders
 * them server-side so crawlers see them without executing JavaScript.
 */
final class Seo
{
    /**
     * @return array{title: string, description: string}
     */
    public static function meta(string $title, ?string $description = null): array
    {
        return [
            'title' => $title,
            'description' => $description !== null && $description !== ''
                ? str($description)->stripTags()->limit(160)->value()
                : (string) __('messages.footer.tagline'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function organization(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'CouponX Deals',
            'url' => url('/'),
            'logo' => url('/favicon.svg'),
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        $elements = [];
        foreach ($items as $i => $item) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * A schema.org Offer for a coupon.
     *
     * @return array<string, mixed>
     */
    public static function offer(string $name, ?string $description, string $url, ?string $expires, ?string $code): array
    {
        $offer = [
            '@type' => 'Offer',
            'name' => $name,
            'url' => $url,
            'availability' => 'https://schema.org/InStock',
        ];

        if ($description !== null && $description !== '') {
            $offer['description'] = $description;
        }
        if ($expires !== null) {
            $offer['validThrough'] = $expires;
        }
        if ($code !== null && $code !== '') {
            $offer['category'] = 'coupon';
        }

        return $offer;
    }

    /**
     * Wrap a store + its offers as a schema.org Store with an offer catalog.
     *
     * @param  array<int, array<string, mixed>>  $offers
     * @return array<string, mixed>
     */
    public static function storeWithOffers(string $name, string $url, ?string $logo, array $offers): array
    {
        // NOTE: deliberately no aggregateRating. Google only permits it when the
        // ratings are real and visible to users; ours were seeded/manual numbers
        // with zero reviews behind them, which is a structured-data violation.
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => $name,
            'url' => $url,
        ];

        if ($logo !== null) {
            $data['logo'] = $logo;
        }
        if ($offers !== []) {
            $data['makesOffer'] = $offers;
        }

        return $data;
    }
}

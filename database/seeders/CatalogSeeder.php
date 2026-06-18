<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Models\AffiliateNetwork;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Store;
use App\Models\StoreAffiliateLink;
use App\Models\StoreAlias;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $networks = $this->seedNetworks();
        $categories = $this->seedCategories();
        $this->seedStores($networks, $categories);
        $this->seedSubcategories($categories);
    }

    /**
     * @param  array<string, Category>  $categories
     */
    private function seedSubcategories(array $categories): void
    {
        $parent = $categories['electronics'];

        $subs = [
            'phones-accessories' => ['en' => 'Phones & accessories', 'ru' => 'Телефоны и аксессуары', 'icon' => 'shopping-bag'],
            'computers-laptops' => ['en' => 'Computers & laptops', 'ru' => 'Компьютеры и ноутбуки', 'icon' => 'laptop'],
            'audio-headphones' => ['en' => 'Audio & headphones', 'ru' => 'Аудио и наушники', 'icon' => 'sparkles'],
            'tv-video' => ['en' => 'TV & video', 'ru' => 'ТВ и видео', 'icon' => 'tag'],
            'smart-watches' => ['en' => 'Smart watches & gadgets', 'ru' => 'Умные часы и гаджеты', 'icon' => 'tag'],
        ];

        /** @var array<int, int> $electronicsStoreIds */
        $electronicsStoreIds = $parent->stores()->pluck('stores.id')->all();

        $position = 0;
        foreach ($subs as $slug => $row) {
            $sub = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => $parent->id,
                    'name' => ['en' => $row['en'], 'ru' => $row['ru']],
                    'icon' => $row['icon'],
                    'is_active' => true,
                    'is_featured' => false,
                    'position' => $position++,
                ],
            );

            // Attach a varying slice of the parent's stores for non-uniform counts.
            $take = max(1, count($electronicsStoreIds) - ($position % 2));
            $sub->stores()->syncWithoutDetaching(array_slice($electronicsStoreIds, 0, $take));
        }
    }

    /**
     * @return array<string, AffiliateNetwork>
     */
    private function seedNetworks(): array
    {
        $data = [
            'admitad' => ['name' => 'Admitad', 'tpl' => 'https://ad.admitad.com/g/click?ulp={target}'],
            'cj' => ['name' => 'CJ Affiliate', 'tpl' => 'https://www.anrdoezrs.net/click?url={target}'],
            'awin' => ['name' => 'Awin', 'tpl' => 'https://www.awin1.com/cread.php?ued={target}'],
        ];

        $networks = [];
        foreach ($data as $slug => $row) {
            $networks[$slug] = AffiliateNetwork::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $row['name'],
                    'integration' => 'manual',
                    'is_active' => true,
                    'tracking_template' => $row['tpl'],
                    'default_utm' => ['utm_source' => 'couponx', 'utm_medium' => 'affiliate'],
                ],
            );
        }

        return $networks;
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $data = [
            'electronics' => ['en' => 'Electronics', 'ru' => 'Электроника', 'icon' => 'laptop'],
            'fashion' => ['en' => 'Fashion', 'ru' => 'Одежда и обувь', 'icon' => 'shirt'],
            'home-garden' => ['en' => 'Home & Garden', 'ru' => 'Дом и сад', 'icon' => 'home'],
            'beauty' => ['en' => 'Beauty & Health', 'ru' => 'Красота и здоровье', 'icon' => 'sparkles'],
            'travel' => ['en' => 'Travel', 'ru' => 'Путешествия', 'icon' => 'plane'],
            'auto' => ['en' => 'Automotive', 'ru' => 'Автотовары', 'icon' => 'car'],
        ];

        $categories = [];
        $position = 0;
        foreach ($data as $slug => $row) {
            $categories[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['en' => $row['en'], 'ru' => $row['ru']],
                    'icon' => $row['icon'],
                    'is_featured' => true,
                    'is_active' => true,
                    'position' => $position++,
                ],
            );
        }

        return $categories;
    }

    /**
     * @param  array<string, AffiliateNetwork>  $networks
     * @param  array<string, Category>  $categories
     */
    private function seedStores(array $networks, array $categories): void
    {
        foreach ($this->storeData() as $row) {
            /** @var Store $store */
            $store = Store::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'about' => $row['about'] ?? null,
                    'website_url' => $row['website_url'],
                    'rating' => $row['rating'],
                    'rating_count' => $row['rating_count'] ?? 0,
                    'cashback_type' => 'percent',
                    'cashback_value' => $row['cashback_value'],
                    'cashback_payout_terms' => $row['cashback_payout_terms'] ?? null,
                    'countries' => $row['countries'] ?? ['US', 'RU'],
                    'default_affiliate_network_id' => $networks[$row['network']]->id,
                    'is_featured' => true,
                    'is_active' => true,
                    'position' => $row['position'],
                ],
            );

            /** @var array<int, string> $storeCategorySlugs */
            $storeCategorySlugs = $row['categories'];
            $store->categories()->syncWithoutDetaching($this->categoryIds($categories, $storeCategorySlugs));

            // Default (global) affiliate destination — cloaked behind /go & /out.
            StoreAffiliateLink::query()->updateOrCreate(
                ['store_id' => $store->id, 'country_code' => null, 'affiliate_network_id' => $networks[$row['network']]->id],
                ['affiliate_url' => $row['website_url'], 'cashback_value' => $row['cashback_value'], 'priority' => 0, 'is_active' => true],
            );

            foreach ($row['aliases'] ?? [] as $alias) {
                StoreAlias::query()->updateOrCreate(
                    ['normalized' => StoreAlias::normalize($alias), 'source' => $row['network']],
                    ['store_id' => $store->id, 'name' => $alias],
                );
            }

            foreach ($row['coupons'] as $couponRow) {
                $this->seedCoupon($store, $networks[$row['network']]->id, $couponRow, $categories, $row['categories']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, Category>  $categories
     * @param  array<int, string>  $storeCategorySlugs
     */
    private function seedCoupon(Store $store, int $networkId, array $row, array $categories, array $storeCategorySlugs): void
    {
        /** @var Coupon $coupon */
        $coupon = Coupon::query()->updateOrCreate(
            ['store_id' => $store->id, 'source' => 'seed', 'external_id' => $row['ext']],
            [
                'affiliate_network_id' => $networkId,
                'type' => $row['type'],
                'title' => $row['title'],
                'description' => $row['description'],
                'code' => $row['code'] ?? null,
                'discount_type' => $row['discount_type'] ?? null,
                'discount_value' => $row['discount_value'] ?? null,
                'expires_at' => isset($row['expires_in_days']) ? now()->addDays($row['expires_in_days']) : null,
                'used_count' => $row['used_count'] ?? 0,
                'is_featured' => $row['is_featured'] ?? false,
                'is_exclusive' => $row['is_exclusive'] ?? false,
                'is_verified' => true,
                'status' => CouponStatus::Active,
                'position' => $row['position'] ?? 0,
            ],
        );

        $coupon->categories()->syncWithoutDetaching($this->categoryIds($categories, $storeCategorySlugs));
    }

    /**
     * @param  array<string, Category>  $categories
     * @param  array<int, string>  $slugs
     * @return array<int, int>
     */
    private function categoryIds(array $categories, array $slugs): array
    {
        return array_map(static fn (string $slug): int => $categories[$slug]->id, $slugs);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function storeData(): array
    {
        return [
            [
                'slug' => 'amazon',
                'name' => 'Amazon',
                'description' => ['en' => 'The world\'s largest online marketplace.', 'ru' => 'Крупнейший в мире интернет-магазин.'],
                'website_url' => 'https://www.amazon.com',
                'rating' => 4.7, 'rating_count' => 4210,
                'cashback_value' => 'up to 7%', 'cashback_payout_terms' => '30-60 days',
                'network' => 'cj', 'position' => 1,
                'categories' => ['electronics', 'home-garden', 'fashion'],
                'aliases' => ['Amazon US', 'Amazon Global'],
                'coupons' => [
                    ['ext' => 'amz-1', 'type' => CouponType::Code, 'code' => 'FREESHIP', 'title' => ['en' => 'Free Shipping on Orders $25+', 'ru' => 'Бесплатная доставка от $25'], 'description' => ['en' => 'Use this code to get free shipping on all orders over $25.', 'ru' => 'Бесплатная доставка для заказов от $25.'], 'discount_type' => DiscountType::FreeShipping, 'used_count' => 842, 'is_featured' => true, 'position' => 1],
                    ['ext' => 'amz-2', 'type' => CouponType::Deal, 'title' => ['en' => 'Up to 50% Off Electronics', 'ru' => 'Скидки до 50% на электронику'], 'description' => ['en' => 'Daily deals on top electronics.', 'ru' => 'Ежедневные скидки на электронику.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 50, 'used_count' => 1290, 'position' => 2],
                ],
            ],
            [
                'slug' => 'aliexpress',
                'name' => 'AliExpress',
                'description' => ['en' => 'One of the largest online stores with products from China at wholesale prices.', 'ru' => 'Один из крупнейших интернет-магазинов с товарами из Китая по оптовым ценам.'],
                'website_url' => 'https://www.aliexpress.com',
                'rating' => 4.6, 'rating_count' => 8800,
                'cashback_value' => 'up to 5%', 'cashback_payout_terms' => '30-45 days',
                'countries' => ['RU', 'UA', 'BY', 'US'],
                'network' => 'admitad', 'position' => 2,
                'categories' => ['electronics', 'fashion', 'home-garden', 'beauty', 'auto'],
                'aliases' => ['AliExpress WW', 'AliExpress RU', 'AliExpress Global'],
                'coupons' => [
                    ['ext' => 'alix-500', 'type' => CouponType::Code, 'code' => 'ALIX500', 'title' => ['en' => '500₽ Off Orders Over 3000₽', 'ru' => 'Скидка 500₽ от 3000₽'], 'description' => ['en' => 'Copy the code and get 500 rubles off orders over 3000 rubles.', 'ru' => 'Скопируйте промокод и получите скидку 500 рублей при заказе от 3000 рублей на AliExpress.'], 'discount_type' => DiscountType::Fixed, 'discount_value' => 500, 'expires_in_days' => 3, 'used_count' => 1254, 'is_featured' => true, 'position' => 1],
                    ['ext' => 'alix-800', 'type' => CouponType::Code, 'code' => 'ALIX800', 'title' => ['en' => '800₽ Off Orders Over 5000₽', 'ru' => 'Скидка 800₽ от 5000₽'], 'description' => ['en' => 'Apply the code and get 800 rubles off orders over 5000 rubles.', 'ru' => 'Примените промокод и получите скидку 800 рублей при заказе от 5000 рублей.'], 'discount_type' => DiscountType::Fixed, 'discount_value' => 800, 'expires_in_days' => 5, 'used_count' => 982, 'position' => 2],
                    ['ext' => 'alix-sale', 'type' => CouponType::Sale, 'title' => ['en' => 'Up to 70% Off Selected Items', 'ru' => 'Скидка до 70% на избранные товары'], 'description' => ['en' => 'Sale on thousands of products. Up to 70% off electronics, clothing, home goods and more.', 'ru' => 'Распродажа на тысячи товаров. Скидки до 70% на электронику, одежду, товары для дома и не только.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 70, 'expires_in_days' => 20, 'used_count' => 3421, 'is_featured' => true, 'position' => 3],
                    ['ext' => 'alix-300', 'type' => CouponType::Code, 'code' => 'ALIX300', 'title' => ['en' => '300₽ Off Orders Over 2000₽', 'ru' => 'Скидка 300₽ от 2000₽'], 'description' => ['en' => 'Get 300 rubles off orders over 2000 rubles on any products.', 'ru' => 'Получите скидку 300 рублей при заказе от 2000 рублей на любые товары.'], 'discount_type' => DiscountType::Fixed, 'discount_value' => 300, 'expires_in_days' => 2, 'used_count' => 713, 'position' => 4],
                    ['ext' => 'alix-1500', 'type' => CouponType::Code, 'code' => 'ALIX1500', 'title' => ['en' => '1500₽ Off Orders Over 10000₽', 'ru' => 'Скидка 1500₽ от 10000₽'], 'description' => ['en' => 'Use the code and get 1500 rubles off orders over 10000 rubles.', 'ru' => 'Используйте промокод и получите скидку 1500 рублей при заказе от 10000 рублей.'], 'discount_type' => DiscountType::Fixed, 'discount_value' => 1500, 'expires_in_days' => 6, 'used_count' => 422, 'position' => 5],
                ],
            ],
            [
                'slug' => 'nike',
                'name' => 'Nike',
                'description' => ['en' => 'Official store for shoes, clothing and sports gear.', 'ru' => 'Официальный магазин обуви, одежды и спортивной экипировки.'],
                'website_url' => 'https://www.nike.com',
                'rating' => 4.8, 'rating_count' => 3100,
                'cashback_value' => 'up to 6%',
                'network' => 'awin', 'position' => 3,
                'categories' => ['fashion'],
                'aliases' => ['Nike Inc', 'Nike Store'],
                'coupons' => [
                    ['ext' => 'nike-save20', 'type' => CouponType::Code, 'code' => 'SAVE20', 'title' => ['en' => 'Extra 20% Off Select Styles', 'ru' => 'Дополнительно 20% на избранные модели'], 'description' => ['en' => 'Use this code at checkout to get extra 20% off on select styles.', 'ru' => 'Используйте код при оформлении заказа и получите дополнительные 20% на избранные модели.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 20, 'expires_in_days' => 10, 'used_count' => 1254, 'is_featured' => true, 'position' => 1],
                ],
            ],
            [
                'slug' => 'ebay',
                'name' => 'eBay',
                'description' => ['en' => 'Buy and sell electronics, fashion, collectibles and more.', 'ru' => 'Покупка и продажа электроники, одежды, коллекционных товаров и не только.'],
                'website_url' => 'https://www.ebay.com',
                'rating' => 4.5, 'rating_count' => 2750,
                'cashback_value' => 'up to 4%',
                'network' => 'cj', 'position' => 4,
                'categories' => ['electronics', 'home-garden'],
                'coupons' => [
                    ['ext' => 'ebay-1', 'type' => CouponType::Deal, 'title' => ['en' => 'Up to 60% Off Daily Deals', 'ru' => 'Скидки до 60% каждый день'], 'description' => ['en' => 'Shop daily deals across all categories.', 'ru' => 'Ежедневные скидки во всех категориях.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 60, 'used_count' => 540, 'position' => 1],
                ],
            ],
            [
                'slug' => 'iherb',
                'name' => 'iHerb',
                'description' => ['en' => 'Vitamins, supplements and natural health products.', 'ru' => 'Витамины, добавки и натуральные товары для здоровья.'],
                'website_url' => 'https://www.iherb.com',
                'rating' => 4.7, 'rating_count' => 1980,
                'cashback_value' => 'up to 3%',
                'network' => 'admitad', 'position' => 5,
                'categories' => ['beauty'],
                'coupons' => [
                    ['ext' => 'iherb-1', 'type' => CouponType::Code, 'code' => 'IHERB10', 'title' => ['en' => '10% Off First Order', 'ru' => '10% на первый заказ'], 'description' => ['en' => 'New customers get 10% off the first order.', 'ru' => 'Новые покупатели получают 10% на первый заказ.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 10, 'expires_in_days' => 14, 'used_count' => 320, 'position' => 1],
                ],
            ],
            [
                'slug' => 'booking',
                'name' => 'Booking.com',
                'description' => ['en' => 'Book hotels, apartments and stays worldwide.', 'ru' => 'Бронирование отелей, апартаментов и жилья по всему миру.'],
                'website_url' => 'https://www.booking.com',
                'rating' => 4.6, 'rating_count' => 5400,
                'cashback_value' => 'up to 4%',
                'network' => 'awin', 'position' => 6,
                'categories' => ['travel'],
                'coupons' => [
                    ['ext' => 'booking-1', 'type' => CouponType::Deal, 'title' => ['en' => 'Up to 40% Off Stays', 'ru' => 'Скидки до 40% на проживание'], 'description' => ['en' => 'Save on hotels with limited-time deals.', 'ru' => 'Экономьте на отелях с ограниченными по времени предложениями.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 40, 'used_count' => 760, 'is_featured' => true, 'position' => 1],
                ],
            ],
            [
                'slug' => 'shein',
                'name' => 'SHEIN',
                'description' => ['en' => 'Trendy fashion and accessories at affordable prices.', 'ru' => 'Модная одежда и аксессуары по доступным ценам.'],
                'website_url' => 'https://www.shein.com',
                'rating' => 4.3, 'rating_count' => 6200,
                'cashback_value' => 'up to 8%',
                'network' => 'admitad', 'position' => 7,
                'categories' => ['fashion', 'beauty'],
                'coupons' => [
                    ['ext' => 'shein-1', 'type' => CouponType::Code, 'code' => 'SHEIN15', 'title' => ['en' => '15% Off Everything', 'ru' => '15% на всё'], 'description' => ['en' => 'Sitewide 15% off with this code.', 'ru' => 'Скидка 15% на весь ассортимент по этому коду.'], 'discount_type' => DiscountType::Percentage, 'discount_value' => 15, 'expires_in_days' => 7, 'used_count' => 1500, 'is_featured' => true, 'position' => 1],
                ],
            ],
        ];
    }
}

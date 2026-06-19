<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Curated set of category icons. The keys are Lucide icon names and MUST stay in
 * sync with the map in resources/js/components/public/CategoryIcon.vue — the admin
 * can only pick icons the storefront can actually render.
 */
final class CategoryIcons
{
    /** @var array<string, string> key => human label (EN / RU for search) */
    public const OPTIONS = [
        'tag' => 'Tag / Тег',
        'shopping-bag' => 'Shopping bag / Покупки',
        'shopping-cart' => 'Cart / Корзина',
        'gift' => 'Gift / Подарок',
        'percent' => 'Percent / Процент',
        'ticket-percent' => 'Coupon / Купон',
        'store' => 'Store / Магазин',
        'package' => 'Package / Посылка',
        'laptop' => 'Laptop / Ноутбук',
        'smartphone' => 'Smartphone / Телефон',
        'monitor' => 'Monitor / Монитор',
        'headphones' => 'Headphones / Наушники',
        'camera' => 'Camera / Камера',
        'gamepad-2' => 'Games / Игры',
        'tv' => 'TV / Телевизор',
        'watch' => 'Watch / Часы',
        'cpu' => 'Components / Комплектующие',
        'shirt' => 'Clothing / Одежда',
        'footprints' => 'Shoes / Обувь',
        'glasses' => 'Glasses / Очки',
        'gem' => 'Jewelry / Украшения',
        'home' => 'Home / Дом',
        'sofa' => 'Furniture / Мебель',
        'lamp' => 'Lighting / Свет',
        'bed' => 'Bedroom / Спальня',
        'utensils' => 'Kitchen / Кухня',
        'refrigerator' => 'Appliances / Техника',
        'sparkles' => 'Beauty / Красота',
        'heart-pulse' => 'Health / Здоровье',
        'pill' => 'Pharmacy / Аптека',
        'scissors' => 'Barber / Парикмахерская',
        'flower' => 'Flowers / Цветы',
        'apple' => 'Grocery / Продукты',
        'coffee' => 'Coffee / Кофе',
        'pizza' => 'Food / Еда',
        'cake' => 'Sweets / Сладости',
        'plane' => 'Travel / Путешествия',
        'luggage' => 'Luggage / Багаж',
        'map-pin' => 'Places / Места',
        'car' => 'Auto / Авто',
        'fuel' => 'Fuel / Топливо',
        'train-front' => 'Trains / Поезда',
        'baby' => 'Kids / Дети',
        'toy-brick' => 'Toys / Игрушки',
        'book-open' => 'Books / Книги',
        'music' => 'Music / Музыка',
        'film' => 'Movies / Кино',
        'dumbbell' => 'Sports / Спорт',
        'bike' => 'Bikes / Велосипеды',
        'trophy' => 'Hobby / Хобби',
        'paw-print' => 'Pets / Питомцы',
        'credit-card' => 'Finance / Финансы',
        'wallet' => 'Wallet / Кошелёк',
    ];

    /** @return array<string, string> */
    public static function options(): array
    {
        return self::OPTIONS;
    }
}

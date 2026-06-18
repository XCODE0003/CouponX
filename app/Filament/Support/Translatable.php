<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Helper for building locale-tabbed form sections backed by
 * spatie/laravel-translatable JSON columns.
 *
 * attributesToArray() returns the full {"en":..,"ru":..} array for translatable
 * attributes, and assigning an array sets every translation — so binding fields
 * to "field.{locale}" paths round-trips cleanly with no plugin or mutate hooks.
 */
class Translatable
{
    /** @var array<string, string> Locale code => display label. */
    public const LOCALES = [
        'en' => 'English',
        'ru' => 'Русский',
    ];

    public static function defaultLocale(): string
    {
        return (string) config('app.fallback_locale', 'en');
    }

    /**
     * Build a Tabs component with one tab per locale.
     *
     * @param  callable(string $locale, bool $isDefault): array<int, Component>  $fields
     */
    public static function tabs(callable $fields): Tabs
    {
        $default = self::defaultLocale();

        $tabs = [];
        foreach (self::LOCALES as $locale => $label) {
            $tabs[] = Tab::make($label)->schema($fields($locale, $locale === $default));
        }

        return Tabs::make('translations')
            ->label('Translations')
            ->tabs($tabs)
            ->columnSpanFull();
    }
}

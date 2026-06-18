<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Enums\DiscountType;
use App\Filament\Support\Translatable;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Купон')
                    ->columns(2)
                    ->schema([
                        Select::make('store_id')
                            ->label('Магазин')
                            ->relationship('store', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Тип')
                            ->options(self::enumOptions(CouponType::cases()))
                            ->default(CouponType::Code->value)
                            ->live()
                            ->required(),
                        TextInput::make('code')
                            ->label('Промокод')
                            ->maxLength(100)
                            ->helperText('Обязательно для купонов с промокодом')
                            ->visible(fn (Get $get): bool => $get('type') === CouponType::Code->value)
                            ->required(fn (Get $get): bool => $get('type') === CouponType::Code->value),
                        TextInput::make('destination_url')
                            ->label('URL назначения (необязательно)')
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Конкретная целевая страница; по умолчанию используется партнёрская ссылка магазина'),
                    ]),

                Translatable::tabs(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("title.$locale")->label('Заголовок')->required($isDefault)->maxLength(255),
                    Textarea::make("description.$locale")->label('Описание')->rows(2),
                    Textarea::make("terms.$locale")->label('Условия')->rows(2),
                ]),

                Section::make('Скидка и сроки')
                    ->columns(3)
                    ->schema([
                        Select::make('discount_type')
                            ->label('Тип скидки')
                            ->options(self::enumOptions(DiscountType::cases()))
                            ->native(false),
                        TextInput::make('discount_value')->label('Размер скидки')->numeric()->step(0.01),
                        TextInput::make('used_count')->label('Использовано')->numeric()->default(0),
                        DateTimePicker::make('starts_at')->label('Начало'),
                        DateTimePicker::make('expires_at')->label('Истекает'),
                        TextInput::make('position')->label('Позиция')->numeric()->default(0),
                    ]),

                Section::make('Категории и статус')
                    ->columns(2)
                    ->schema([
                        Select::make('categories')
                            ->label('Категории')
                            ->relationship('categories', 'slug')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Статус')
                            ->options(self::enumOptions(CouponStatus::cases()))
                            ->default(CouponStatus::Active->value)
                            ->required(),
                        Toggle::make('is_featured')->label('Рекомендуемый')->inline(false),
                        Toggle::make('is_exclusive')->label('Эксклюзив')->inline(false),
                        Toggle::make('is_verified')->label('Проверен')->inline(false),
                    ]),
            ]);
    }

    /**
     * @param  array<int, CouponType|CouponStatus|DiscountType>  $cases
     * @return array<string, string>
     */
    private static function enumOptions(array $cases): array
    {
        $options = [];
        foreach ($cases as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

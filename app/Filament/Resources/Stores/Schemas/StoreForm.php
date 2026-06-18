<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\Schemas;

use App\Filament\Support\Translatable;
use App\Support\ImageOptimizer;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Магазин')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')
                            ->label('URL (slug)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Сегмент URL без привязки к языку, напр. /store/nike'),
                        TextInput::make('website_url')
                            ->label('Сайт магазина')
                            ->url()
                            ->maxLength(2048)
                            ->columnSpanFull(),
                        FileUpload::make('logo')
                            ->label('Логотип')
                            ->image()
                            ->disk('public')
                            ->directory('stores')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(2048)
                            ->helperText('PNG, JPEG или WebP. При загрузке конвертируется в оптимизированный WebP.')
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): ?string => ImageOptimizer::storeAsWebp($file, 'stores', 512))
                            ->columnSpanFull(),
                    ]),

                Translatable::tabs(fn (string $locale, bool $isDefault): array => [
                    Textarea::make("description.$locale")
                        ->label('Краткое описание')
                        ->rows(2)
                        ->required($isDefault)
                        ->maxLength(500),
                    Textarea::make("about.$locale")
                        ->label('О магазине / SEO-текст')
                        ->rows(5),
                    Textarea::make("cashback_terms.$locale")
                        ->label('Условия кэшбэка')
                        ->rows(2),
                ]),

                Section::make('Кэшбэк и рейтинг')
                    ->columns(3)
                    ->schema([
                        TextInput::make('cashback_value')->label('Размер кэшбэка')->maxLength(60)->placeholder('до 5%'),
                        TextInput::make('cashback_payout_terms')->label('Срок выплаты')->maxLength(60)->placeholder('30-45 дней'),
                        TextInput::make('cashback_type')->label('Тип кэшбэка')->maxLength(60)->placeholder('процент'),
                        TextInput::make('rating')->label('Рейтинг')->numeric()->minValue(0)->maxValue(5)->step(0.1),
                        TextInput::make('rating_count')->label('Кол-во оценок')->numeric()->default(0),
                        TextInput::make('position')->label('Позиция')->numeric()->default(0),
                    ]),

                Section::make('Связи и статус')
                    ->columns(2)
                    ->schema([
                        Select::make('default_affiliate_network_id')
                            ->label('Партнёрская сеть по умолчанию')
                            ->relationship('defaultNetwork', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('categories')
                            ->label('Категории')
                            ->relationship('categories', 'slug')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_featured')->label('Рекомендуемый')->inline(false),
                        Toggle::make('is_active')->label('Активен')->default(true)->inline(false),
                    ]),

                Translatable::tabs(fn (string $locale): array => [
                    TextInput::make("meta_title.$locale")->label('Meta title')->maxLength(255),
                    Textarea::make("meta_description.$locale")->label('Meta description')->rows(2)->maxLength(500),
                ])->label('SEO'),
            ]);
    }
}

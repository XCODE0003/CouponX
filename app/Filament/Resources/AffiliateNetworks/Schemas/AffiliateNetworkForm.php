<?php

declare(strict_types=1);

namespace App\Filament\Resources\AffiliateNetworks\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AffiliateNetworkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Сеть')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')->label('URL')->required()->unique(ignoreRecord: true),
                        Select::make('integration')
                            ->label('Интеграция')
                            ->options([
                                'manual' => 'Вручную (ручной ввод)',
                                'json_feed' => 'JSON-фид',
                                'admitad' => 'Admitad (API)',
                                'indoleads' => 'Indoleads (API)',
                                'cj' => 'CJ Affiliate (API)',
                                'awin' => 'Awin (API)',
                            ])
                            ->default('manual')
                            ->live()
                            ->required()
                            ->helperText('Адаптер импорта, используемый командой coupons:import'),
                        Toggle::make('is_active')->label('Активен')->default(true)->inline(false),
                        Textarea::make('tracking_template')
                            ->label('Шаблон трекинга')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('URL для маскировки с подстановкой {target}, например https://track.net/click?url={target}'),
                    ]),

                Section::make('API-доступы')
                    ->description('Хранятся в зашифрованном виде.')
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('integration') !== 'manual')
                    ->schema([
                        // JSON feed
                        TextInput::make('config.feed_url')->label('URL фида')->url()->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('integration') === 'json_feed'),
                        TextInput::make('config.items_path')->label('Путь к элементам (точечная нотация)')->placeholder('data.coupons')
                            ->visible(fn (Get $get): bool => $get('integration') === 'json_feed'),
                        KeyValue::make('config.fields')->label('Сопоставление полей')->keyLabel('Наше поле')->valueLabel('Поле фида')->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get('integration') === 'json_feed'),

                        // Admitad
                        TextInput::make('config.client_id')->label('Client ID')
                            ->visible(fn (Get $get): bool => $get('integration') === 'admitad'),
                        TextInput::make('config.client_secret')->label('Client secret')->password()->revealable()
                            ->visible(fn (Get $get): bool => $get('integration') === 'admitad'),
                        TextInput::make('config.website_id')->label('Website ID (необязательно)')
                            ->visible(fn (Get $get): bool => $get('integration') === 'admitad'),
                        TextInput::make('config.scope')->label('Scope')->placeholder('coupons')
                            ->visible(fn (Get $get): bool => $get('integration') === 'admitad'),

                        // CJ
                        TextInput::make('config.cj_token')->label('Персональный токен доступа')->password()->revealable()
                            ->visible(fn (Get $get): bool => $get('integration') === 'cj'),
                        TextInput::make('config.cj_website_id')->label('Website ID (PID)')
                            ->visible(fn (Get $get): bool => $get('integration') === 'cj'),
                        TextInput::make('config.cj_advertiser_ids')->label('ID рекламодателей')->placeholder('joined')
                            ->visible(fn (Get $get): bool => $get('integration') === 'cj'),

                        // Awin
                        TextInput::make('config.awin_token')->label('API-токен')->password()->revealable()
                            ->visible(fn (Get $get): bool => $get('integration') === 'awin'),
                        TextInput::make('config.awin_publisher_id')->label('Publisher ID')
                            ->visible(fn (Get $get): bool => $get('integration') === 'awin'),

                        // Indoleads
                        TextInput::make('config.token')->label('API-токен')->password()->revealable()
                            ->helperText('Account → Api Settings')
                            ->visible(fn (Get $get): bool => $get('integration') === 'indoleads'),
                        TextInput::make('config.source_id')->label('Source ID')
                            ->helperText('ID источника из раздела Sources')
                            ->visible(fn (Get $get): bool => $get('integration') === 'indoleads'),
                    ]),

                Section::make('Значения по умолчанию')
                    ->schema([
                        KeyValue::make('default_utm')
                            ->label('UTM по умолчанию')
                            ->keyLabel('Ключ UTM')
                            ->valueLabel('Значение'),
                    ]),
            ]);
    }
}

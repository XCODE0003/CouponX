<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Manual management of a store's geo-aware affiliate destinations
 * (ТЗ: «Ручное добавление affiliate-ссылок»).
 */
class AffiliateLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'affiliateLinks';

    protected static ?string $title = 'Партнёрские ссылки';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('affiliate_network_id')
                    ->label('Сеть')
                    ->relationship('network', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('country_code')
                    ->label('Страна (ISO-2, пусто = по умолчанию)')
                    ->maxLength(2)
                    ->placeholder('RU, US…')
                    ->helperText('Оставьте пустым для глобальной ссылки по умолчанию'),
                TextInput::make('affiliate_url')
                    ->label('Партнёрский URL')
                    ->url()
                    ->required()
                    ->maxLength(2048)
                    ->columnSpanFull(),
                TextInput::make('priority')->label('Приоритет')->numeric()->default(0)->helperText('При нескольких совпадениях побеждает большее значение'),
                Toggle::make('is_active')->label('Активен')->default(true)->inline(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('affiliate_url')
            ->defaultSort('priority', 'desc')
            ->columns([
                TextColumn::make('country_code')->label('Страна')->badge()->placeholder('по умолчанию'),
                TextColumn::make('network.name')->label('Сеть')->placeholder('—'),
                TextColumn::make('affiliate_url')->label('Партнёрская ссылка')->limit(40)->wrap()->color('gray'),
                TextColumn::make('priority')->label('Приоритет')->numeric()->sortable(),
                IconColumn::make('is_active')->label('Активен')->boolean(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('logo')->label('Логотип')->circular()->defaultImageUrl(asset('favicon.ico')),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold'),
                TextColumn::make('slug')->label('URL')->searchable()->toggleable()->color('gray'),
                TextColumn::make('coupons_count')->counts('coupons')->label('Купоны')->badge(),
                TextColumn::make('cashback_value')->label('Кэшбэк')->toggleable(),
                TextColumn::make('rating')->label('Рейтинг')->numeric(1)->sortable(),
                TextColumn::make('clicks_count')->label('Клики')->numeric()->sortable()->toggleable(),
                IconColumn::make('is_featured')->label('Рекомендуемый')->boolean()->sortable(),
                IconColumn::make('is_active')->label('Активен')->boolean()->sortable(),
                TextColumn::make('position')->label('Позиция')->numeric()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('categories')->relationship('categories', 'slug')->multiple()->preload(),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_featured'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

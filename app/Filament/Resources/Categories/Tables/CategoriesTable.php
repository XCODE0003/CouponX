<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold'),
                TextColumn::make('slug')->label('URL')->color('gray')->searchable(),
                TextColumn::make('parent.slug')->label('Родительская категория')->placeholder('—')->toggleable(),
                TextColumn::make('icon')->label('Иконка')->badge()->color('gray')->toggleable(),
                TextColumn::make('stores_count')->counts('stores')->label('Магазины')->badge(),
                TextColumn::make('coupons_count')->counts('coupons')->label('Купоны')->badge(),
                IconColumn::make('is_featured')->label('Рекомендуемый')->boolean()->sortable(),
                IconColumn::make('is_active')->label('Активен')->boolean()->sortable(),
            ])
            ->filters([
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

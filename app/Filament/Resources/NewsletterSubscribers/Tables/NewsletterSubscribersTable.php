<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('Email')->searchable()->sortable()->copyable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'subscribed' ? 'success' : 'gray'),
                TextColumn::make('locale')->label('Язык')->badge()->toggleable(),
                TextColumn::make('country_code')->label('Страна')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->label('Присоединился'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'subscribed' => 'Подписан',
                    'unsubscribed' => 'Отписан',
                ]),
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

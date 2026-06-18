<?php

declare(strict_types=1);

namespace App\Filament\Resources\AffiliateNetworks\Tables;

use App\Models\AffiliateNetwork;
use App\Services\Import\AdapterRegistry;
use App\Services\Import\CouponImporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Throwable;

class AffiliateNetworksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold'),
                TextColumn::make('slug')->label('URL')->color('gray')->searchable(),
                TextColumn::make('integration')->label('Интеграция')->badge()->toggleable(),
                TextColumn::make('stores_count')->counts('stores')->label('Магазины')->badge(),
                TextColumn::make('coupons_count')->counts('coupons')->label('Купоны')->badge(),
                IconColumn::make('is_active')->label('Активен')->boolean()->sortable(),
                TextColumn::make('last_imported_at')->label('Последний импорт')->dateTime()->placeholder('никогда')->sortable()->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                Action::make('import')
                    ->label('Импортировать сейчас')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (AffiliateNetwork $record): bool => $record->integration !== null
                        && $record->integration !== 'manual'
                        && app(AdapterRegistry::class)->has($record->integration))
                    ->requiresConfirmation()
                    ->action(function (AffiliateNetwork $record): void {
                        try {
                            $result = app(CouponImporter::class)->import($record);
                            Notification::make()
                                ->title('Импорт завершён')
                                ->body($result->summary())
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Ошибка импорта')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

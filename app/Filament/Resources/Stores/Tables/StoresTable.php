<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\Tables;

use App\Models\Store;
use App\Services\Stores\StoreMerger;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class StoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('logo')->label('Логотип')->disk('public')->circular()->defaultImageUrl(asset('favicon.ico')),
                TextColumn::make('name')->label('Название')->searchable()->sortable()->weight('bold'),
                TextColumn::make('slug')->label('URL')->searchable()->toggleable()->color('gray'),
                TextColumn::make('domain')->label('Домен')->searchable()->sortable()->toggleable()->color('gray')->placeholder('—'),
                TextColumn::make('coupons_count')->counts('coupons')->label('Купоны')->badge(),
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
                    BulkAction::make('merge')
                        ->label('Объединить')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('warning')
                        ->modalHeading('Объединить магазины')
                        ->modalDescription('Выберите главный магазин — остальные присоединятся к нему (купоны, ссылки, категории, клики). Ручные поля главного магазина сохранятся, дубли удалятся, а будущие импорты будут попадать в него.')
                        ->modalSubmitActionLabel('Объединить')
                        ->schema([
                            Select::make('target_id')
                                ->label('Главный магазин (остальные присоединятся к нему)')
                                ->options(fn (Collection $records): array => $records->pluck('name', 'id')->all())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data, StoreMerger $merger): void {
                            $target = $records->firstWhere('id', (int) $data['target_id']);

                            if (! $target instanceof Store) {
                                return;
                            }

                            $sources = $records->reject(fn (Store $store): bool => $store->getKey() === $target->getKey());
                            $count = $merger->merge($target, $sources);

                            Notification::make()
                                ->title("Объединено магазинов: {$count} → {$target->name}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

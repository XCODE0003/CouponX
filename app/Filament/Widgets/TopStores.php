<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Store;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopStores extends TableWidget
{
    protected static ?string $heading = 'Топ магазинов по кликам';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Store::query()->withCount('coupons')->orderByDesc('clicks_count')->limit(10)
            )
            ->columns([
                TextColumn::make('name')->label('Название')->weight('bold'),
                TextColumn::make('clicks_count')->label('Клики')->numeric()->sortable(),
                TextColumn::make('coupons_count')->label('Купоны')->badge(),
                TextColumn::make('cashback_value')->label('Кэшбэк')->placeholder('—'),
            ])
            ->paginated(false);
    }
}

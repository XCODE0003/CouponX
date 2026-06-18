<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CouponType;
use App\Models\Coupon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopCoupons extends TableWidget
{
    protected static ?string $heading = 'Топ купонов по кликам';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Coupon::query()->with('store')->orderByDesc('clicks_count')->orderByDesc('used_count')->limit(10)
            )
            ->columns([
                TextColumn::make('store.name')->label('Магазин')->weight('bold'),
                TextColumn::make('title')->label('Заголовок')->limit(40)->wrap(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (CouponType $state): string => $state->label()),
                TextColumn::make('clicks_count')->label('Клики')->numeric()->sortable(),
                TextColumn::make('used_count')->label('Использовано')->numeric()->sortable(),
            ])
            ->paginated(false);
    }
}

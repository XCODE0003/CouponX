<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponStatus;
use App\Enums\CouponType;
use App\Models\Category;
use App\Models\Coupon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('store.name')->label('Магазин')->searchable()->sortable(),
                TextColumn::make('title')->label('Заголовок')->searchable()->limit(40)->wrap()->weight('bold'),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (CouponType $state): string => $state->label())
                    ->color(fn (CouponType $state): string => match ($state) {
                        CouponType::Code => 'success',
                        CouponType::Deal => 'info',
                        CouponType::Sale => 'warning',
                    }),
                TextColumn::make('code')->label('Промокод')->badge()->color('gray')->copyable()->placeholder('—'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (CouponStatus $state): string => $state->label())
                    ->color(fn (CouponStatus $state): string => $state === CouponStatus::Active ? 'success' : 'gray'),
                TextColumn::make('used_count')->label('Использовано')->numeric()->sortable()->toggleable(),
                IconColumn::make('is_featured')->label('Рекомендуемый')->boolean()->sortable()->toggleable(),
                TextColumn::make('expires_at')->label('Истекает')->dateTime()->sortable()->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('store')->relationship('store', 'name')->searchable()->preload(),
                SelectFilter::make('type')->options(self::typeOptions()),
                SelectFilter::make('status')->options(self::statusOptions()),
                Filter::make('expired')
                    ->label('Только истёкшие')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('expires_at')->where('expires_at', '<', now())),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::changeStatusAction(),
                    self::changeCategoryAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function changeStatusAction(): BulkAction
    {
        return BulkAction::make('changeStatus')
            ->label('Изменить статус')
            ->icon('heroicon-o-arrow-path')
            ->schema([
                Select::make('status')->options(self::statusOptions())->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                /** @var Coupon $record */
                foreach ($records as $record) {
                    $record->update(['status' => $data['status']]);
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function changeCategoryAction(): BulkAction
    {
        return BulkAction::make('changeCategory')
            ->label('Назначить категории')
            ->icon('heroicon-o-tag')
            ->schema([
                Select::make('categories')
                    ->multiple()
                    ->options(fn (): array => Category::query()->pluck('slug', 'id')->all())
                    ->required(),
            ])
            ->action(function (Collection $records, array $data): void {
                /** @var Coupon $record */
                foreach ($records as $record) {
                    $record->categories()->sync($data['categories']);
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    /** @return array<string, string> */
    private static function typeOptions(): array
    {
        $options = [];
        foreach (CouponType::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        $options = [];
        foreach (CouponStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}

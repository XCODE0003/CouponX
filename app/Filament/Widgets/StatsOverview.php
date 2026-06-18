<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CouponStatus;
use App\Models\Click;
use App\Models\Coupon;
use App\Models\NewsletterSubscriber;
use App\Models\Store;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $clicksToday = Click::query()->whereDate('created_at', today())->count();
        $clicksTotal = Click::query()->count();

        return [
            Stat::make('Кликов сегодня', number_format($clicksToday))
                ->description(number_format($clicksTotal).' за всё время')
                ->color('success'),
            Stat::make('Активные купоны', number_format(Coupon::query()->where('status', CouponStatus::Active->value)->count()))
                ->color('info'),
            Stat::make('Магазины', number_format(Store::query()->where('is_active', true)->count()))
                ->color('primary'),
            Stat::make('Подписчики', number_format(NewsletterSubscriber::query()->where('status', 'subscribed')->count()))
                ->color('warning'),
        ];
    }
}

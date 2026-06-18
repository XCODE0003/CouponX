<?php

use App\Services\Notifications\AdminNotifier;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hourly coupon import across all active networks with a registered adapter.
// Failures are reported to admins via Telegram/email by the command itself;
// onFailure covers the case where the command process itself crashes.
Schedule::command('coupons:import')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure(function (): void {
        app(AdminNotifier::class)
            ->error('Cron failure', 'The scheduled coupons:import job failed to run.');
    });

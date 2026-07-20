<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataObjects\ClickContext;
use App\Models\Click;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

class LogClick implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(private readonly ClickContext $context) {}

    public function handle(): void
    {
        Click::query()->create([
            'coupon_id' => $this->context->couponId,
            'store_id' => $this->context->storeId,
            'affiliate_network_id' => $this->context->networkId,
            'country_code' => $this->context->countryCode,
            'locale' => $this->context->locale,
            'ip_hash' => $this->context->ipHash,
            'user_agent' => $this->context->userAgent,
            'referer' => $this->context->referer,
            'utm' => $this->context->utm,
            'created_at' => now(),
        ]);

        // Counter bumps bypass model events to avoid activity-log noise.
        if ($this->context->storeId !== null) {
            Store::query()->whereKey($this->context->storeId)->increment('clicks_count');
        }

        if ($this->context->couponId !== null) {
            // used_count backs the public "Использовали N раз" label, so it has to
            // track real redirects; it keeps any manual baseline set in the admin.
            Coupon::query()->whereKey($this->context->couponId)
                ->incrementEach(['clicks_count' => 1, 'used_count' => 1]);
        }
    }
}

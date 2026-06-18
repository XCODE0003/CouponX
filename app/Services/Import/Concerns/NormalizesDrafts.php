<?php

declare(strict_types=1);

namespace App\Services\Import\Concerns;

use App\Enums\CouponType;
use App\Enums\DiscountType;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Shared mapping helpers for network adapters (locale wrapping, date/type/
 * discount parsing) so each adapter only has to map field names.
 */
trait NormalizesDrafts
{
    /**
     * @return array<string, string>
     */
    protected function localized(string $value): array
    {
        return [(string) config('app.fallback_locale', 'en') => $value];
    }

    /**
     * @return array<string, string>|null
     */
    protected function localizedNullable(?string $value): ?array
    {
        return $value === null || $value === '' ? null : $this->localized($value);
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : (is_int($value) || is_float($value) ? (string) $value : null);
    }

    protected function typeFromCode(?string $code, ?string $hint = null): CouponType
    {
        return match (strtolower((string) $hint)) {
            'sale' => CouponType::Sale,
            'deal', 'offer' => CouponType::Deal,
            'code', 'promo', 'voucher' => CouponType::Code,
            default => $code !== null && $code !== '' ? CouponType::Code : CouponType::Deal,
        };
    }

    protected function discountType(?string $value): ?DiscountType
    {
        return match (strtolower((string) $value)) {
            'percentage', 'percent', '%' => DiscountType::Percentage,
            'fixed', 'amount', 'money' => DiscountType::Fixed,
            'free_shipping', 'shipping', 'freeshipping' => DiscountType::FreeShipping,
            'bogo' => DiscountType::Bogo,
            '' => null,
            default => DiscountType::Other,
        };
    }

    /**
     * Extracts the first number from values like "20%", "до 20%", 20 or "500 RUB".
     */
    protected function numberFrom(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && preg_match('/(\d+(?:[.,]\d+)?)/', $value, $matches) === 1) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }
}

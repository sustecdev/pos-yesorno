<?php

namespace App\Support;

use App\Models\Order;

class DiscountCalculator
{
    public const TYPE_FLAT = 'flat';

    public const TYPE_PERCENT = 'percent';

    public static function calculateCents(Order $order, string $type, int $value): int
    {
        $value = max(0, $value);
        $maxDiscount = $order->subtotal_cents + $order->tax_cents;

        if ($type === self::TYPE_PERCENT) {
            $percent = min(100, $value);

            return (int) min($maxDiscount, round($order->subtotal_cents * $percent / 100));
        }

        return (int) min($maxDiscount, $value);
    }

    public static function label(string $type, int $value, int $discountCents): string
    {
        if ($discountCents <= 0) {
            return 'Discount';
        }

        if ($type === self::TYPE_PERCENT && $value > 0) {
            return "Discount ({$value}%)";
        }

        return 'Discount';
    }
}

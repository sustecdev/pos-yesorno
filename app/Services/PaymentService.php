<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Receipt;
use App\Support\DiscountCalculator;
use App\Support\RestaurantProfile;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    public function applyDiscount(Order $order, string $type, int $value): Order
    {
        $type = $type === DiscountCalculator::TYPE_PERCENT
            ? DiscountCalculator::TYPE_PERCENT
            : DiscountCalculator::TYPE_FLAT;

        $discountCents = DiscountCalculator::calculateCents($order, $type, $value);

        $order->update([
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_cents' => $discountCents,
        ]);

        $order->recalculateTotals(RestaurantProfile::taxRateDecimal());

        return $order;
    }

    public function recordPayment(
        Order $order,
        PaymentMethod $method,
        int $amountCents,
        User $cashier,
        int $tipCents = 0,
    ): Payment {
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'order_split_id' => null,
            'cashier_id' => $cashier->id,
            'method' => $method,
            'amount_cents' => $amountCents,
            'tip_cents' => $tipCents,
        ]);

        $paidTotal = $order->payments()->sum('amount_cents');

        if ($paidTotal >= $order->total_cents) {
            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
            ]);

            $order->table?->update(['status' => TableStatus::Dirty]);
        }

        $this->generateReceipt($order, $payment);

        return $payment;
    }

    public function generateReceipt(Order $order, Payment $payment): Receipt
    {
        $order->load(['items.modifiers', 'table', 'waiter']);

        return Receipt::query()->create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'receipt_number' => 'R'.strtoupper(Str::random(8)),
            'data' => array_merge(RestaurantProfile::forReceipt(), [
                'order_number' => $order->order_number,
                'table' => $order->table?->number,
                'waiter' => $order->waiter?->name,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->name,
                    'qty' => $item->quantity,
                    'total' => $item->total_cents / 100,
                    'modifiers' => $item->modifiers->pluck('name'),
                ]),
                'subtotal' => $order->subtotal_cents / 100,
                'tax' => $order->tax_cents / 100,
                'tax_label' => RestaurantProfile::taxLabel(),
                'discount' => $order->discount_cents / 100,
                'discount_type' => $order->discount_type,
                'discount_value' => $order->discount_value,
                'discount_label' => DiscountCalculator::label(
                    $order->discount_type ?? DiscountCalculator::TYPE_FLAT,
                    (int) ($order->discount_value ?? 0),
                    $order->discount_cents,
                ),
                'total' => $order->total_cents / 100,
                'payment_method' => $payment->method->value,
                'paid_at' => now()->toDateTimeString(),
            ]),
        ]);
    }
}

<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'dining_table_id', 'waiter_id', 'reservation_id',
        'status', 'course_number', 'is_rush', 'is_vip', 'notes',
        'subtotal_cents', 'tax_cents', 'discount_type', 'discount_value', 'discount_cents', 'total_cents',
        'sent_to_kitchen_at', 'ready_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'is_rush' => 'boolean',
            'is_vip' => 'boolean',
            'sent_to_kitchen_at' => 'datetime',
            'ready_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'dining_table_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(OrderSplit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function kitchenAlerts(): HasMany
    {
        return $this->hasMany(KitchenAlert::class);
    }

    public function recalculateTotals(float $taxRate = 0.08): void
    {
        $subtotal = $this->items()->where('status', '!=', 'cancelled')->sum('total_cents');
        $tax = (int) round($subtotal * $taxRate);
        $total = max(0, $subtotal + $tax - $this->discount_cents);

        $this->update([
            'subtotal_cents' => $subtotal,
            'tax_cents' => $tax,
            'total_cents' => $total,
        ]);
    }

    public function formattedTotal(): string
    {
        return money($this->total_cents);
    }

    public function canRemoveItems(): bool
    {
        return ! in_array($this->status, [
            OrderStatus::Paid,
            OrderStatus::Closed,
            OrderStatus::Cancelled,
        ], true);
    }
}

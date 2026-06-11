<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'menu_item_id', 'kitchen_station_id', 'name', 'quantity',
        'unit_price_cents', 'total_cents', 'status', 'course_number',
        'has_allergy', 'allergy_note', 'special_instructions',
        'sent_at', 'started_at', 'ready_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderItemStatus::class,
            'has_allergy' => 'boolean',
            'sent_at' => 'datetime',
            'started_at' => 'datetime',
            'ready_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function kitchenStation(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(OrderItemModifier::class);
    }
}

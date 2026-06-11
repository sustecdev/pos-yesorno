<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderSplit extends Model
{
    protected $fillable = ['order_id', 'label', 'amount_cents', 'seat_number'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(OrderItem::class, 'order_split_items');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

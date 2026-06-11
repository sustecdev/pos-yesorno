<?php

namespace App\Models;

use App\Enums\KitchenAlertType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenAlert extends Model
{
    protected $fillable = [
        'order_id', 'kitchen_station_id', 'type', 'priority',
        'payload', 'diff', 'acknowledged_at', 'acknowledged_by', 'escalation_level',
    ];

    protected function casts(): array
    {
        return [
            'type' => KitchenAlertType::class,
            'payload' => 'array',
            'diff' => 'array',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class, 'kitchen_station_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }
}

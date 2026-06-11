<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenNotificationSetting extends Model
{
    protected $fillable = [
        'kitchen_station_id', 'sla_minutes', 'escalation_minutes',
        'volume', 'sound_enabled', 'printer_enabled',
    ];

    protected function casts(): array
    {
        return [
            'sound_enabled' => 'boolean',
            'printer_enabled' => 'boolean',
        ];
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(KitchenStation::class, 'kitchen_station_id');
    }
}

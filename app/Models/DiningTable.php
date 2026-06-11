<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DiningTable extends Model
{
    protected $fillable = [
        'dining_area_id', 'number', 'seats', 'status', 'position_x', 'position_y',
    ];

    protected function casts(): array
    {
        return ['status' => TableStatus::class];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(DiningArea::class, 'dining_area_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrder(): HasOne
    {
        return $this->hasOne(Order::class)
            ->whereNotIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::Closed->value,
                OrderStatus::Cancelled->value,
            ])
            ->latest();
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeOrderedByNumber(Builder $query): void
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $query->orderByRaw('CAST(number AS INTEGER)');

            return;
        }

        $query->orderByRaw('CAST(number AS UNSIGNED)');
    }
}

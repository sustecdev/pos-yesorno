<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KitchenStation extends Model
{
    protected $fillable = ['name', 'slug', 'color', 'is_expo', 'sort_order'];

    protected function casts(): array
    {
        return ['is_expo' => 'boolean'];
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(KitchenNotificationSetting::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(KitchenAlert::class);
    }
}

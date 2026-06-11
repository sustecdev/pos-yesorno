<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('kitchen.station.{stationId}', function ($user) {
    return $user->hasAnyRole(['kitchen', 'admin', 'manager']);
});

Broadcast::channel('kitchen.expo', function ($user) {
    return $user->hasAnyRole(['kitchen', 'admin', 'manager']);
});

Broadcast::channel('kitchen.manager', function ($user) {
    return $user->hasAnyRole(['admin', 'manager']);
});

Broadcast::channel('floor.{areaId}', function ($user) {
    return $user->hasAnyRole(['waiter', 'admin', 'manager', 'host']);
});

Broadcast::channel('orders.{orderId}', function ($user) {
    return $user->hasAnyRole(['waiter', 'kitchen', 'cashier', 'admin', 'manager']);
});

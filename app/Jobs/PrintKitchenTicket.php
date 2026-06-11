<?php

namespace App\Jobs;

use App\Models\KitchenAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PrintKitchenTicket implements ShouldQueue
{
    use Queueable;

    public function __construct(public KitchenAlert $alert) {}

    public function handle(): void
    {
        $alert = $this->alert->fresh();

        if (! $alert || $alert->isAcknowledged()) {
            return;
        }

        Log::info('Kitchen ticket print fallback', [
            'alert_id' => $alert->id,
            'order_id' => $alert->order_id,
            'station_id' => $alert->kitchen_station_id,
            'payload' => $alert->payload,
        ]);
    }
}

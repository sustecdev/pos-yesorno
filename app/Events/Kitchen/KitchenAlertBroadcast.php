<?php

namespace App\Events\Kitchen;

use App\Models\KitchenAlert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenAlertBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public KitchenAlert $alert)
    {
        $this->alert->loadMissing(['order.table', 'station']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new Channel('kitchen.station.'.$this->alert->kitchen_station_id),
        ];

        if ($this->alert->station?->is_expo) {
            $channels[] = new Channel('kitchen.expo');
        }

        if ($this->alert->escalation_level > 0) {
            $channels[] = new Channel('kitchen.manager');
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'KitchenAlertReceived';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->alert->id,
            'type' => $this->alert->type->value,
            'priority' => $this->alert->priority,
            'sound' => $this->alert->type->sound(),
            'payload' => $this->alert->payload,
            'diff' => $this->alert->diff,
            'order_id' => $this->alert->order_id,
            'station_id' => $this->alert->kitchen_station_id,
            'escalation_level' => $this->alert->escalation_level,
            'created_at' => $this->alert->created_at->toIso8601String(),
        ];
    }
}

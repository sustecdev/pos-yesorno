<?php

namespace App\Events\Kitchen;

use App\Models\DiningTable;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DiningTable $table) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('floor.'.$this->table->dining_area_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TableStatusChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'table_id' => $this->table->id,
            'status' => $this->table->status->value,
            'order_status' => $this->table->activeOrder?->status->value,
        ];
    }
}

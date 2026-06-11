<?php

namespace App\Jobs;

use App\Enums\KitchenAlertType;
use App\Events\Kitchen\KitchenAlertBroadcast;
use App\Models\KitchenAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EscalateUnackedKitchenAlert implements ShouldQueue
{
    use Queueable;

    public function __construct(public KitchenAlert $alert) {}

    public function handle(): void
    {
        $alert = $this->alert->fresh();

        if (! $alert || $alert->isAcknowledged()) {
            return;
        }

        $alert->update(['escalation_level' => $alert->escalation_level + 1]);

        if ($alert->escalation_level >= 2) {
            $alert->update(['type' => KitchenAlertType::SlaBreach, 'priority' => 95]);
        }

        broadcast(new KitchenAlertBroadcast($alert->fresh()));
    }
}

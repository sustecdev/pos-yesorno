<?php

namespace App\Services;

use App\Enums\KitchenAlertType;
use App\Events\Kitchen\KitchenAlertBroadcast;
use App\Jobs\EscalateUnackedKitchenAlert;
use App\Jobs\PrintKitchenTicket;
use App\Models\KitchenAlert;
use App\Models\KitchenStation;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class KitchenNotificationService
{
    public function notifyNewTicket(Order $order): void
    {
        $payload = $this->buildOrderPayload($order);
        $stations = $this->stationsForOrder($order);

        foreach ($stations as $station) {
            $this->dispatch(
                type: KitchenAlertType::NewTicket,
                order: $order,
                stationId: $station->id,
                payload: $this->filterPayloadForStation($payload, $station->id),
            );
        }

        $this->notifyExpo($order, KitchenAlertType::NewTicket, $payload);
    }

    public function notifyFireCourse(Order $order): void
    {
        $payload = $this->buildOrderPayload($order, $order->course_number);

        foreach ($this->stationsForOrder($order, $order->course_number) as $station) {
            $this->dispatch(
                type: KitchenAlertType::FireCourse,
                order: $order,
                stationId: $station->id,
                payload: $this->filterPayloadForStation($payload, $station->id),
            );
        }

        $this->notifyExpo($order, KitchenAlertType::FireCourse, $payload);
    }

    public function notifyModified(Order $order, array $diff): void
    {
        $payload = $this->buildOrderPayload($order);

        foreach ($this->stationsForOrder($order) as $station) {
            $this->dispatch(
                type: KitchenAlertType::OrderModified,
                order: $order,
                stationId: $station->id,
                payload: $this->filterPayloadForStation($payload, $station->id),
                diff: $diff,
            );
        }
    }

    public function notifyCancelled(Order $order, ?OrderItem $item = null): void
    {
        $payload = $this->buildOrderPayload($order);
        $type = KitchenAlertType::OrderCancelled;

        if ($item?->kitchen_station_id) {
            $this->dispatch($type, $order, $item->kitchen_station_id, $payload, [
                'cancelled_item' => $item->name,
            ]);
        } else {
            foreach ($this->stationsForOrder($order) as $station) {
                $this->dispatch($type, $order, $station->id, $payload);
            }
        }
    }

    public function notifyRush(Order $order): void
    {
        $payload = $this->buildOrderPayload($order);

        foreach ($this->stationsForOrder($order) as $station) {
            $this->dispatch(KitchenAlertType::RushOrder, $order, $station->id, $payload);
        }
    }

    public function notifyAllergy(OrderItem $item): void
    {
        if (! $item->kitchen_station_id) {
            return;
        }

        $this->dispatch(
            KitchenAlertType::AllergyAlert,
            $item->order,
            $item->kitchen_station_id,
            $this->buildOrderPayload($item->order),
            ['item' => $item->name, 'allergy_note' => $item->allergy_note],
        );
    }

    public function notifyRecall(Order $order): void
    {
        $payload = $this->buildOrderPayload($order);

        foreach ($this->stationsForOrder($order) as $station) {
            $this->dispatch(KitchenAlertType::RecallTicket, $order, $station->id, $payload);
        }
    }

    public function broadcastMessage(string $message, ?int $userId = null): void
    {
        $payload = ['message' => $message, 'from' => $userId];

        KitchenStation::query()->each(function (KitchenStation $station) use ($payload) {
            $alert = KitchenAlert::query()->create([
                'kitchen_station_id' => $station->id,
                'type' => KitchenAlertType::KitchenBroadcast,
                'priority' => KitchenAlertType::KitchenBroadcast->priority(),
                'payload' => $payload,
            ]);

            broadcast(new KitchenAlertBroadcast($alert));
        });
    }

    public function acknowledge(KitchenAlert $alert, int $userId): void
    {
        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId,
        ]);
    }

    protected function dispatch(
        KitchenAlertType $type,
        Order $order,
        int $stationId,
        array $payload,
        ?array $diff = null,
    ): void {
        $alert = KitchenAlert::query()->create([
            'order_id' => $order->id,
            'kitchen_station_id' => $stationId,
            'type' => $type,
            'priority' => $type->priority() + ($order->is_rush ? 10 : 0),
            'payload' => $payload,
            'diff' => $diff,
        ]);

        broadcast(new KitchenAlertBroadcast($alert));

        $settings = KitchenStation::find($stationId)?->notificationSettings;
        $escalationMinutes = $settings?->escalation_minutes ?? 2;

        EscalateUnackedKitchenAlert::dispatch($alert)->delay(now()->addMinutes($escalationMinutes));

        if ($settings?->printer_enabled) {
            PrintKitchenTicket::dispatch($alert)->delay(now()->addSeconds(30));
        }
    }

    protected function notifyExpo(Order $order, KitchenAlertType $type, array $payload): void
    {
        $expo = KitchenStation::query()->where('is_expo', true)->first();

        if (! $expo) {
            return;
        }

        $this->dispatch($type, $order, $expo->id, $payload);
    }

    protected function stationsForOrder(Order $order, ?int $course = null): Collection
    {
        $query = $order->items()
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('kitchen_station_id');

        if ($course) {
            $query->where('course_number', $course);
        }

        $stationIds = $query->pluck('kitchen_station_id')->unique();

        return KitchenStation::query()->whereIn('id', $stationIds)->get();
    }

    protected function buildOrderPayload(Order $order, ?int $course = null): array
    {
        $order->load(['table', 'waiter', 'items.modifiers', 'items.kitchenStation']);

        $items = $order->items->when($course, fn ($c) => $c->where('course_number', $course));

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'table' => $order->table?->number,
            'waiter' => $order->waiter?->name,
            'is_rush' => $order->is_rush,
            'is_vip' => $order->is_vip,
            'course_number' => $course ?? $order->course_number,
            'notes' => $order->notes,
            'sent_at' => $order->sent_to_kitchen_at?->toIso8601String(),
            'items' => $items->map(fn (OrderItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'status' => $item->status->value,
                'station_id' => $item->kitchen_station_id,
                'has_allergy' => $item->has_allergy,
                'allergy_note' => $item->allergy_note,
                'instructions' => $item->special_instructions,
                'modifiers' => $item->modifiers->pluck('name'),
            ])->values()->all(),
        ];
    }

    protected function filterPayloadForStation(array $payload, int $stationId): array
    {
        $payload['items'] = collect($payload['items'])
            ->where('station_id', $stationId)
            ->values()
            ->all();

        return $payload;
    }
}

<?php

namespace App\Livewire\Kitchen;

use App\Enums\OrderItemStatus;
use App\Models\KitchenAlert;
use App\Models\KitchenStation;
use App\Models\OrderItem;
use App\Services\KitchenNotificationService;
use App\Services\OrderService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.kitchen')]
#[Title('Kitchen')]
class KdsBoard extends Component
{
    public ?int $stationId = null;

    public int $unackedCount = 0;

    public function mount(): void
    {
        $this->stationId = KitchenStation::query()->orderBy('sort_order')->value('id');
        $this->refreshBoard();
    }

    #[On('echo:kitchen.station.{stationId},KitchenAlertReceived')]
    public function onKitchenAlert(): void
    {
        $this->refreshBoard();
        $this->dispatch('kitchen-alert-received');
    }

    #[On('echo:kitchen.expo,KitchenAlertReceived')]
    public function onExpoAlert(): void
    {
        $this->refreshBoard();
        $this->dispatch('kitchen-alert-received');
    }

    public function switchStation(int $stationId): void
    {
        $this->stationId = $stationId;
        $this->refreshBoard();
    }

    public function refreshBoard(): void
    {
        $this->unackedCount = KitchenAlert::query()
            ->when($this->stationId, fn ($q) => $q->where('kitchen_station_id', $this->stationId))
            ->whereNull('acknowledged_at')
            ->count();
    }

    public function acknowledge(int $alertId, KitchenNotificationService $notifications): void
    {
        $alert = KitchenAlert::query()->findOrFail($alertId);
        $notifications->acknowledge($alert, auth()->id());
        $this->refreshBoard();
        $this->dispatch('kitchen-alert-acked');
    }

    public function acknowledgeAll(KitchenNotificationService $notifications): void
    {
        $alerts = KitchenAlert::query()
            ->when($this->stationId, fn ($q) => $q->where('kitchen_station_id', $this->stationId))
            ->whereNull('acknowledged_at')
            ->get();

        foreach ($alerts as $alert) {
            $notifications->acknowledge($alert, auth()->id());
        }

        $this->refreshBoard();
        $this->dispatch('kitchen-alert-acked');
    }

    public function startItem(int $itemId, OrderService $orderService): void
    {
        $item = OrderItem::query()->findOrFail($itemId);
        $orderService->updateItemStatus($item, OrderItemStatus::Preparing);
    }

    public function readyItem(int $itemId, OrderService $orderService): void
    {
        $item = OrderItem::query()->findOrFail($itemId);
        $orderService->updateItemStatus($item, OrderItemStatus::Ready);
    }

    public function bumpItem(int $itemId, OrderService $orderService): void
    {
        $item = OrderItem::query()->findOrFail($itemId);
        $orderService->updateItemStatus($item, OrderItemStatus::Served);
    }

    public function startAllOnTicket(int $orderId, OrderService $orderService): void
    {
        $this->itemsForStation($orderId)
            ->where('status', OrderItemStatus::Queued)
            ->each(fn ($item) => $orderService->updateItemStatus($item, OrderItemStatus::Preparing));
    }

    public function readyAllOnTicket(int $orderId, OrderService $orderService): void
    {
        $this->itemsForStation($orderId)
            ->whereIn('status', [OrderItemStatus::Queued, OrderItemStatus::Preparing])
            ->each(fn ($item) => $orderService->updateItemStatus($item, OrderItemStatus::Ready));
    }

    public function bumpTicket(int $orderId, OrderService $orderService): void
    {
        $this->itemsForStation($orderId)
            ->where('status', OrderItemStatus::Ready)
            ->each(fn ($item) => $orderService->updateItemStatus($item, OrderItemStatus::Served));
    }

    protected function itemsForStation(int $orderId): Collection
    {
        return OrderItem::query()
            ->where('order_id', $orderId)
            ->when($this->stationId, fn ($q) => $q->where('kitchen_station_id', $this->stationId))
            ->whereNotIn('status', [OrderItemStatus::Served, OrderItemStatus::Cancelled])
            ->get();
    }

    public function render()
    {
        $stations = KitchenStation::query()->orderBy('sort_order')->get();

        $stationCounts = OrderItem::query()
            ->whereNotIn('status', [OrderItemStatus::Served, OrderItemStatus::Cancelled])
            ->whereNotNull('sent_at')
            ->selectRaw('kitchen_station_id, count(*) as cnt')
            ->groupBy('kitchen_station_id')
            ->pluck('cnt', 'kitchen_station_id');

        $allItems = OrderItem::query()
            ->with(['order.table', 'order.waiter', 'modifiers'])
            ->whereNotIn('status', [OrderItemStatus::Served, OrderItemStatus::Cancelled])
            ->when($this->stationId, fn ($q) => $q->where('kitchen_station_id', $this->stationId))
            ->whereNotNull('sent_at')
            ->get();

        $stats = [
            'queued' => $allItems->where('status', OrderItemStatus::Queued)->count(),
            'preparing' => $allItems->where('status', OrderItemStatus::Preparing)->count(),
            'ready' => $allItems->where('status', OrderItemStatus::Ready)->count(),
            'tickets' => $allItems->pluck('order_id')->unique()->count(),
        ];

        $items = $allItems
            ->sortBy(fn ($item) => ($item->order->is_rush ? 0 : 100_000)
                + ($item->has_allergy ? 0 : 10_000)
                + ($item->sent_at?->timestamp ?? 0))
            ->groupBy('order_id');

        $alerts = KitchenAlert::query()
            ->when($this->stationId, fn ($q) => $q->where('kitchen_station_id', $this->stationId))
            ->whereNull('acknowledged_at')
            ->orderByDesc('priority')
            ->limit(10)
            ->get();

        $broadcasts = KitchenAlert::query()
            ->where('type', 'kitchen_broadcast')
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->limit(2)
            ->get();

        $currentStation = $stations->firstWhere('id', $this->stationId);

        return view('livewire.kitchen.kds-board', compact(
            'stations', 'items', 'alerts', 'broadcasts', 'stats', 'stationCounts', 'currentStation'
        ));
    }
}

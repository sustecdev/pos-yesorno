<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\KitchenAlert;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InventoryService;
use App\Services\KitchenNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $kitchenBroadcast = '';

    public function sendBroadcast(KitchenNotificationService $notifications): void
    {
        $this->validate(['kitchenBroadcast' => 'required|min:2']);

        $notifications->broadcastMessage($this->kitchenBroadcast, auth()->id());
        $this->kitchenBroadcast = '';
        $this->dispatch('toast', message: 'Broadcast sent to all KDS screens!', type: 'success');
    }

    public function render()
    {
        $todaySales = Payment::query()->whereDate('created_at', today())->sum('amount_cents');
        $openOrders = Order::query()->whereNotIn('status', [OrderStatus::Paid, OrderStatus::Closed, OrderStatus::Cancelled])->count();
        $unackedAlerts = KitchenAlert::query()->whereNull('acknowledged_at')->count();
        $lowStock = app(InventoryService::class)->lowStockItems();

        $recentOrders = Order::query()->with('table')->latest()->limit(8)->get();

        return view('livewire.admin.dashboard', compact(
            'todaySales', 'openOrders', 'unackedAlerts', 'lowStock', 'recentOrders'
        ));
    }
}

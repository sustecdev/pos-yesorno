<?php

namespace App\Livewire\Reports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Reports')]
class SalesDashboard extends Component
{
    public int $days = 7;

    public function render()
    {
        $start = now()->subDays($this->days);

        $dailySales = Payment::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, SUM(amount_cents) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topItems = OrderItem::query()
            ->where('created_at', '>=', $start)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('name, SUM(quantity) as qty, SUM(total_cents) as revenue')
            ->groupBy('name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $orderCount = Order::query()->where('created_at', '>=', $start)->count();
        $totalRevenue = Payment::query()->where('created_at', '>=', $start)->sum('amount_cents');

        return view('livewire.reports.sales-dashboard', compact(
            'dailySales', 'topItems', 'orderCount', 'totalRevenue'
        ));
    }
}

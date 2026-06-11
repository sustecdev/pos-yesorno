<?php

namespace App\Livewire\Waiter;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\DiningArea;
use App\Models\DiningTable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.waiter')]
#[Title('Floor')]
class FloorPlan extends Component
{
    public ?int $selectedAreaId = null;

    #[Url]
    public string $filter = 'all';

    public function mount(): void
    {
        $this->selectedAreaId = DiningArea::query()->where('is_active', true)->value('id');

        if (request()->has('filter')) {
            $this->filter = request()->get('filter', 'all');
        }
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function selectTable(int $tableId): void
    {
        $this->redirect(route('waiter.order', $tableId), navigate: true);
    }

    protected function tableMatchesFilter(DiningTable $table): bool
    {
        $order = $table->activeOrder;

        return match ($this->filter) {
            'active' => $order !== null,
            'kitchen' => $order && in_array($order->status, [OrderStatus::Sent, OrderStatus::Preparing]),
            'ready' => $order && $order->status === OrderStatus::Ready,
            'bill' => $order && $order->status === OrderStatus::Served,
            default => true,
        };
    }

    public function render()
    {
        $areas = DiningArea::query()->where('is_active', true)->orderBy('sort_order')->get();

        $allTables = DiningTable::query()
            ->with(['activeOrder.items'])
            ->when($this->selectedAreaId, fn ($q) => $q->where('dining_area_id', $this->selectedAreaId))
            ->orderBy('number')
            ->get();

        $tables = $allTables->filter(fn ($t) => $this->tableMatchesFilter($t));

        $stats = [
            'free' => $allTables->where('status', TableStatus::Free)->count(),
            'active' => $allTables->filter(fn ($t) => $t->activeOrder)->count(),
            'ready' => $allTables->filter(fn ($t) => $t->activeOrder?->status === OrderStatus::Ready)->count(),
            'bill' => $allTables->filter(fn ($t) => $t->activeOrder?->status === OrderStatus::Served)->count(),
        ];

        $activeOrders = $this->activeOrdersList($allTables);

        return view('livewire.waiter.floor-plan', compact('areas', 'tables', 'stats', 'activeOrders'));
    }

    protected function activeOrdersList(Collection $tables): Collection
    {
        return $tables
            ->filter(fn ($t) => $t->activeOrder)
            ->sortBy(fn ($t) => match ($t->activeOrder->status) {
                OrderStatus::Served => 0,
                OrderStatus::Ready => 1,
                OrderStatus::Preparing, OrderStatus::Sent => 2,
                default => 3,
            })
            ->values();
    }
}

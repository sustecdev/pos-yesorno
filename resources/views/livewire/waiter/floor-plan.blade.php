<div wire:poll.8s class="flex flex-col">
    {{-- Quick stats --}}
    <div class="grid grid-cols-5 gap-2 mb-4">
        <button wire:click="setFilter('all')" class="waiter-stat-pill {{ $filter === 'all' ? 'waiter-stat-pill-active' : '' }}">
            <span class="text-2xl font-bold font-display">{{ $tables->count() }}</span>
            <span class="text-xs text-tebo-cream/50 mt-0.5">All</span>
        </button>
        <button wire:click="setFilter('active')" class="waiter-stat-pill {{ $filter === 'active' ? 'waiter-stat-pill-active' : '' }}">
            <span class="text-2xl font-bold font-display text-tebo-amber">{{ $stats['active'] }}</span>
            <span class="text-xs text-tebo-cream/50 mt-0.5">Active</span>
        </button>
        <button wire:click="setFilter('kitchen')" class="waiter-stat-pill {{ $filter === 'kitchen' ? 'waiter-stat-pill-active' : '' }}">
            <span class="text-2xl font-bold font-display text-tebo-blue">{{ $stats['kitchen'] }}</span>
            <span class="text-xs text-tebo-cream/50 mt-0.5">Kitchen</span>
        </button>
        <button wire:click="setFilter('ready')" class="waiter-stat-pill {{ $filter === 'ready' ? 'waiter-stat-pill-active' : '' }}">
            <span class="text-2xl font-bold font-display text-tebo-green">{{ $stats['ready'] }}</span>
            <span class="text-xs text-tebo-cream/50 mt-0.5">Ready</span>
        </button>
        <button wire:click="setFilter('bill')" class="waiter-stat-pill {{ $filter === 'bill' ? 'waiter-stat-pill-active' : '' }}">
            <span class="text-2xl font-bold font-display text-tebo-blue">{{ $stats['bill'] }}</span>
            <span class="text-xs text-tebo-cream/50 mt-0.5">Bill</span>
        </button>
    </div>

    {{-- Active orders quick access --}}
    @if($activeOrders->isNotEmpty())
        <div class="mb-5">
            <h3 class="text-xs font-bold uppercase tracking-wider text-tebo-cream/40 mb-2">Jump to order</h3>
            <div class="tablet-scroll-x">
                @foreach($activeOrders as $table)
                    @php $order = $table->activeOrder; @endphp
                    <button wire:click="selectTable({{ $table->id }})"
                        class="tebo-tab shrink-0 {{ $order->status->value === 'ready' ? 'bg-tebo-green/20 border-tebo-green text-tebo-green' : ($order->status->value === 'served' ? 'bg-tebo-blue/20 border-tebo-blue text-tebo-blue' : ($order->status->value === 'preparing' ? 'bg-tebo-blue/20 border-tebo-blue text-tebo-blue' : ($order->status->value === 'sent' ? 'bg-tebo-amber/20 border-tebo-amber text-tebo-amber' : 'tebo-tab-inactive'))) }}">
                        <span class="font-bold">T{{ $table->number }}</span>
                        <span class="text-xs opacity-70">· {{ $order->status->label() }}</span>
                        <span class="text-xs font-bold">{{ money($order->total_cents, false) }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Area tabs --}}
    @if($areas->count() > 1)
        <div class="tablet-scroll-x mb-4">
            @foreach($areas as $area)
                <button wire:click="$set('selectedAreaId', {{ $area->id }})"
                    class="tebo-tab {{ $selectedAreaId === $area->id ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
                    {{ $area->name }}
                </button>
            @endforeach
        </div>
    @endif

    {{-- Table grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-4">
        @forelse($tables as $table)
            @php
                $order = $table->activeOrder;
                $orderStatus = $order?->status;
                $visual = match (true) {
                    $orderStatus?->value === 'served' => 'waiter-table-bill',
                    $orderStatus?->value === 'ready' => 'waiter-table-ready',
                    $orderStatus?->value === 'preparing' => 'waiter-table-preparing',
                    $order !== null => 'waiter-table-occupied',
                    $table->status->value === 'reserved' => 'waiter-table-reserved',
                    $table->status->value === 'dirty' => 'waiter-table-dirty',
                    default => 'waiter-table-free',
                };
                $itemCount = $order?->items->where('status', '!=', 'cancelled')->count() ?? 0;
                $kitchenSummary = $order
                    ? $order->items->where('status', '!=', 'cancelled')->countBy(fn ($item) => $item->status->value)
                    : collect();
            @endphp
            <button wire:click="selectTable({{ $table->id }})"
                class="tablet-table-card {{ $visual }} relative aspect-[4/3] flex flex-col items-center justify-center gap-1">
                <div class="font-display text-5xl font-bold leading-none">{{ $table->number }}</div>
                @if($order)
                    <div class="text-tebo-amber font-bold text-lg">{{ money($order->total_cents, false) }}</div>
                    <div class="text-xs font-medium px-2 py-0.5 rounded-full
                        {{ $orderStatus->value === 'ready' ? 'bg-tebo-green text-tebo-dark' : ($orderStatus->value === 'served' ? 'bg-tebo-blue text-tebo-dark' : ($orderStatus->value === 'preparing' ? 'bg-tebo-blue/30 text-tebo-blue' : 'bg-tebo-amber/30 text-tebo-amber')) }}">
                        {{ $orderStatus->label() }} · {{ $itemCount }} items
                    </div>
                    @if($kitchenSummary->has('preparing') || $kitchenSummary->has('ready') || $kitchenSummary->has('queued'))
                        <div class="text-[10px] text-tebo-cream/50 mt-0.5">
                            @if($kitchenSummary->get('queued')){{ $kitchenSummary->get('queued') }} queued @endif
                            @if($kitchenSummary->get('preparing')){{ $kitchenSummary->get('preparing') }} preparing @endif
                            @if($kitchenSummary->get('ready')){{ $kitchenSummary->get('ready') }} ready @endif
                        </div>
                    @endif
                @else
                    <div class="text-sm text-tebo-cream/40">{{ $table->seats }} seats · {{ $table->status->value }}</div>
                @endif
            </button>
        @empty
            <div class="col-span-full text-center py-16 text-tebo-cream/30">
                <p class="text-lg">No tables match this filter</p>
                <button wire:click="setFilter('all')" class="tebo-btn-primary mt-4">Show all tables</button>
            </div>
        @endforelse
    </div>
</div>

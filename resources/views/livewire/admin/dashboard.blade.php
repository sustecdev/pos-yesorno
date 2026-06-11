<div>
    <h2 class="font-display text-2xl font-bold mb-6">Dashboard</h2>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="tebo-card p-5">
            <div class="text-tebo-cream/50 text-sm">Today's Sales</div>
            <div class="font-display text-2xl font-bold text-tebo-amber mt-1">{{ money($todaySales) }}</div>
        </div>
        <div class="tebo-card p-5">
            <div class="text-tebo-cream/50 text-sm">Open Orders</div>
            <div class="font-display text-2xl font-bold mt-1">{{ $openOrders }}</div>
        </div>
        <div class="tebo-card p-5 {{ $unackedAlerts > 0 ? 'border-tebo-red pulse-rush' : '' }}">
            <div class="text-tebo-cream/50 text-sm">Unacked Kitchen Alerts</div>
            <div class="font-display text-2xl font-bold mt-1 {{ $unackedAlerts > 0 ? 'text-tebo-red' : '' }}">{{ $unackedAlerts }}</div>
        </div>
        <div class="tebo-card p-5">
            <div class="text-tebo-cream/50 text-sm">Low Stock Items</div>
            <div class="font-display text-2xl font-bold mt-1 {{ $lowStock->count() > 0 ? 'text-tebo-red' : 'text-tebo-green' }}">{{ $lowStock->count() }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="tebo-card p-5">
            <h3 class="font-display font-bold mb-4">Kitchen Broadcast</h3>
            <form wire:submit="sendBroadcast" class="flex gap-2">
                <input type="text" wire:model="kitchenBroadcast" placeholder='e.g. "86 salmon"' class="tebo-input flex-1">
                <button type="submit" class="tebo-btn-primary">Send to KDS</button>
            </form>
        </div>

        <div class="tebo-card p-5">
            <h3 class="font-display font-bold mb-4">Low Stock Alerts</h3>
            @forelse($lowStock as $item)
                <div class="flex justify-between py-2 border-b border-tebo-border/50 text-sm">
                    <span>{{ $item->name }}</span>
                    <span class="text-tebo-red">{{ $item->quantity }} {{ $item->unit }}</span>
                </div>
            @empty
                <p class="text-tebo-cream/30 text-sm">All stock levels OK</p>
            @endforelse
        </div>
    </div>

    <div class="tebo-card p-5 mt-6">
        <h3 class="font-display font-bold mb-4">Recent Orders</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-tebo-cream/50 text-left">
                        <th class="pb-3">Order</th>
                        <th class="pb-3">Table</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr class="border-t border-tebo-border/50">
                            <td class="py-2">{{ $order->order_number }}</td>
                            <td class="py-2">{{ $order->table?->number ?? '—' }}</td>
                            <td class="py-2 capitalize">{{ $order->status->label() }}</td>
                            <td class="py-2 text-tebo-amber">{{ money($order->total_cents) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div>
    <div class="flex items-center justify-between mb-6">
        <h2 class="font-display text-2xl font-bold">Sales Reports</h2>
        <select wire:model.live="days" class="tebo-input w-auto">
            <option value="7">Last 7 days</option>
            <option value="14">Last 14 days</option>
            <option value="30">Last 30 days</option>
        </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mb-8">
        <div class="tebo-card p-5">
            <div class="text-tebo-cream/50 text-sm">Total Revenue</div>
            <div class="font-display text-3xl font-bold text-tebo-amber">{{ money($totalRevenue) }}</div>
        </div>
        <div class="tebo-card p-5">
            <div class="text-tebo-cream/50 text-sm">Orders</div>
            <div class="font-display text-3xl font-bold">{{ $orderCount }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="tebo-card p-5">
            <h3 class="font-display font-bold mb-4">Daily Sales</h3>
            <div class="space-y-2">
                @foreach($dailySales as $day)
                    @php $pct = $dailySales->max('total') > 0 ? ($day->total / $dailySales->max('total')) * 100 : 0; @endphp
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-24 text-tebo-cream/50">{{ $day->date }}</span>
                        <div class="flex-1 bg-tebo-darker rounded-full h-2">
                            <div class="bg-tebo-amber h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-20 text-right text-tebo-amber">{{ money($day->total) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="tebo-card p-5">
            <h3 class="font-display font-bold mb-4">Top Items</h3>
            <div class="space-y-2">
                @foreach($topItems as $item)
                    <div class="flex justify-between text-sm py-2 border-b border-tebo-border/50">
                        <span>{{ $item->name }} <span class="text-tebo-cream/40">×{{ $item->qty }}</span></span>
                        <span class="text-tebo-amber">{{ money($item->revenue) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

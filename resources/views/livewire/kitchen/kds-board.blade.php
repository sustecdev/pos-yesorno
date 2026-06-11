<div wire:poll.5s="refreshBoard" data-station-id="{{ $stationId }}" class="h-full flex flex-col min-h-0 kitchen-kds">

    {{-- Top bar --}}
    <header class="kitchen-header shrink-0 mb-2">
        <div class="flex items-center justify-between gap-3 px-1">
            <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                <span class="font-display text-xl sm:text-2xl font-bold text-tebo-amber shrink-0">KDS</span>
                @if($currentStation)
                    <span class="hidden sm:inline text-tebo-cream/40">·</span>
                    <span class="text-base sm:text-lg font-bold truncate" style="color: {{ $currentStation->color }}">{{ $currentStation->name }}</span>
                @endif
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="kitchen-clock font-display text-2xl font-bold tabular-nums" x-text="time"></span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="kitchen-icon-btn" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Live stats --}}
        <div class="grid grid-cols-4 gap-1.5 sm:gap-2 mt-2 sm:mt-3">
            <div class="kitchen-stat">
                <span class="kitchen-stat-num">{{ $stats['tickets'] }}</span>
                <span class="kitchen-stat-label">Tickets</span>
            </div>
            <div class="kitchen-stat kitchen-stat-new">
                <span class="kitchen-stat-num">{{ $stats['queued'] }}</span>
                <span class="kitchen-stat-label">New</span>
            </div>
            <div class="kitchen-stat kitchen-stat-cooking">
                <span class="kitchen-stat-num">{{ $stats['preparing'] }}</span>
                <span class="kitchen-stat-label">Cooking</span>
            </div>
            <div class="kitchen-stat kitchen-stat-ready">
                <span class="kitchen-stat-num">{{ $stats['ready'] }}</span>
                <span class="kitchen-stat-label">Ready</span>
            </div>
        </div>
    </header>

    {{-- Unacked alerts banner --}}
    @if($unackedCount > 0)
        <button wire:click="acknowledgeAll" class="kitchen-alert-banner shrink-0 mb-3 w-full">
            <span>{{ $unackedCount }} NEW ALERT{{ $unackedCount > 1 ? 'S' : '' }}</span>
            <span class="text-sm opacity-80">— TAP TO ACK ALL</span>
        </button>
    @endif

    {{-- Manager broadcasts --}}
    @foreach($broadcasts as $broadcast)
        <div class="kitchen-broadcast shrink-0 mb-3">
            <span class="text-2xl">📢</span>
            <span class="text-xl font-bold">{{ $broadcast->payload['message'] ?? '' }}</span>
        </div>
    @endforeach

    {{-- Individual alert chips --}}
    @if($alerts->isNotEmpty())
        <div class="tablet-scroll-x shrink-0 mb-3">
            @foreach($alerts as $alert)
                <button wire:click="acknowledge({{ $alert->id }})"
                    class="kitchen-alert-chip {{ in_array($alert->type->value, ['rush_order', 'allergy_alert', 'sla_breach']) ? 'kitchen-alert-urgent' : '' }}">
                    {{ strtoupper(str_replace('_', ' ', $alert->type->value)) }}
                    @if($alert->order) · T{{ $alert->order->table?->number }} @endif
                </button>
            @endforeach
        </div>
    @endif

    {{-- Station tabs --}}
    <div class="kitchen-station-bar shrink-0 mb-2 sm:mb-3">
        @foreach($stations as $station)
            @php $count = $stationCounts[$station->id] ?? 0; @endphp
            <button wire:click="switchStation({{ $station->id }})"
                class="kitchen-station-tab {{ $stationId === $station->id ? 'kitchen-station-active' : '' }}"
                @if($stationId === $station->id) style="--station-color: {{ $station->color }}" @endif>
                <span>{{ $station->name }}</span>
                @if($count > 0)
                    <span class="kitchen-station-badge">{{ $count }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Ticket grid --}}
    <div class="kitchen-ticket-scroll flex-1 min-h-0 overflow-y-auto overflow-x-hidden overscroll-contain">
        <div class="kitchen-ticket-grid gap-3 pb-4">
            @forelse($items as $orderId => $orderItems)
                @php
                    $first = $orderItems->first();
                    $order = $first->order;
                    $hasAllergy = $orderItems->contains(fn ($i) => $i->has_allergy);
                    $mins = $order->sent_to_kitchen_at ? max(0, (int) floor($order->sent_to_kitchen_at->diffInMinutes(now()))) : 0;
                    $timerClass = $mins >= 15 ? 'kitchen-timer-late' : ($mins >= 8 ? 'kitchen-timer-warn' : 'kitchen-timer-ok');
                    $queuedCount = $orderItems->filter(fn ($i) => $i->status->value === 'queued')->count();
                    $preparingCount = $orderItems->filter(fn ($i) => $i->status->value === 'preparing')->count();
                    $readyCount = $orderItems->filter(fn ($i) => $i->status->value === 'ready')->count();
                    $ticketClass = $order->is_rush ? 'kitchen-ticket-rush' : ($hasAllergy ? 'kitchen-ticket-allergy' : '');
                @endphp
                <article class="kitchen-ticket {{ $ticketClass }} kds-ticket-enter">
                    {{-- Ticket header --}}
                    <div class="kitchen-ticket-header">
                        <div class="flex items-end gap-2">
                            <span class="kitchen-table-num">T{{ $order->table?->number ?? '—' }}</span>
                            <span class="text-sm text-tebo-cream/40 font-mono">#{{ substr($order->order_number, -4) }}</span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="kitchen-timer {{ $timerClass }}">{{ $mins }}m</span>
                            @if($order->is_rush)
                                <span class="kitchen-badge-rush">RUSH</span>
                            @endif
                            @if($hasAllergy)
                                <span class="kitchen-badge-allergy">ALLERGY</span>
                            @endif
                        </div>
                    </div>

                    <p class="text-xs text-tebo-cream/40 px-4 -mt-1 mb-2">{{ $order->waiter?->name }}</p>

                    {{-- Ticket bulk actions --}}
                    <div class="flex gap-1.5 px-3 mb-3">
                        @if($queuedCount > 0)
                            <button wire:click="startAllOnTicket({{ $orderId }})" class="kitchen-ticket-action kitchen-action-start">
                                Start all ({{ $queuedCount }})
                            </button>
                        @endif
                        @if($preparingCount > 0 || $queuedCount > 0)
                            <button wire:click="readyAllOnTicket({{ $orderId }})" class="kitchen-ticket-action kitchen-action-ready">
                                All ready
                            </button>
                        @endif
                        @if($readyCount > 0)
                            <button wire:click="bumpTicket({{ $orderId }})" class="kitchen-ticket-action kitchen-action-bump">
                                Bump ({{ $readyCount }})
                            </button>
                        @endif
                    </div>

                    {{-- Items --}}
                    <div class="kitchen-ticket-items">
                        @foreach($orderItems as $item)
                            <div class="kitchen-item {{ $item->has_allergy ? 'kitchen-item-allergy' : '' }} {{ $item->status->value === 'ready' ? 'kitchen-item-done' : '' }}">
                                <div class="flex justify-between items-start gap-2 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <div class="kitchen-item-name">{{ $item->quantity }}× {{ $item->name }}</div>
                                        @foreach($item->modifiers as $mod)
                                            <div class="text-xs text-tebo-cream/50">+ {{ $mod->name }}</div>
                                        @endforeach
                                        @if($item->special_instructions)
                                            <div class="kitchen-item-note">{{ $item->special_instructions }}</div>
                                        @endif
                                        @if($item->has_allergy)
                                            <div class="kitchen-item-allergy-text">⚠ {{ $item->allergy_note }}</div>
                                        @endif
                                    </div>
                                    <span class="kitchen-item-status kitchen-status-{{ $item->status->value }}">
                                        {{ $item->status->value }}
                                    </span>
                                </div>
                                <div class="flex gap-1.5">
                                    @if($item->status->value === 'queued')
                                        <button wire:click="startItem({{ $item->id }})" class="kitchen-item-btn kitchen-btn-start">Start</button>
                                    @endif
                                    @if($item->status->value === 'preparing')
                                        <button wire:click="readyItem({{ $item->id }})" class="kitchen-item-btn kitchen-btn-ready">Ready</button>
                                    @endif
                                    @if($item->status->value === 'ready')
                                        <button wire:click="bumpItem({{ $item->id }})" class="kitchen-item-btn kitchen-btn-bump">Bump</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="col-span-full kitchen-empty">
                    <div class="kitchen-empty-icon">✓</div>
                    <h2 class="font-display text-4xl font-bold text-tebo-green/60">All Clear</h2>
                    <p class="text-tebo-cream/30 text-lg mt-2">No tickets for this station</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

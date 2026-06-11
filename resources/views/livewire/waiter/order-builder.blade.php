@php
    $itemCount = $order->items->where('status', '!=', 'cancelled')->count();
    $progressStep = match ($order->status->value) {
        'draft' => 1,
        'sent', 'preparing' => 2,
        'ready' => 3,
        'served' => 4,
        default => 1,
    };
@endphp

<div class="h-full flex flex-col min-h-0">
    {{-- Header + back --}}
    <div class="shrink-0 mb-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('waiter.floor') }}" class="tebo-touch-lg tebo-btn-ghost w-14 h-14 rounded-2xl shrink-0 flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="flex-1 min-w-0">
                <h2 class="font-display text-3xl font-bold">Table {{ $table->number }}</h2>
                <p class="text-sm text-tebo-cream/50">{{ $table->area->name }} · {{ $itemCount }} items</p>
            </div>
            <div class="text-right shrink-0">
                <div class="text-2xl font-bold text-tebo-amber">{{ money($order->total_cents) }}</div>
                @if($order->is_rush)
                    <span class="text-xs font-bold text-tebo-red">RUSH</span>
                @endif
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="flex gap-1.5 mt-3 px-1">
            @foreach(['Order', 'Kitchen', 'Ready', 'Bill'] as $i => $label)
                <div class="flex-1">
                    <div class="waiter-progress-step {{ $i + 1 < $progressStep ? 'waiter-progress-step-done' : ($i + 1 === $progressStep ? 'waiter-progress-step-current' : '') }}"></div>
                    <span class="text-[10px] text-tebo-cream/30 mt-1 block text-center">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Portrait tabs --}}
    <div class="md:hidden flex gap-2 mb-3 shrink-0">
        <button wire:click="showMenu" class="flex-1 tebo-tab {{ $activePanel === 'menu' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
            Menu
        </button>
        <button wire:click="showCart" class="flex-1 tebo-tab {{ $activePanel === 'cart' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
            Order ({{ $itemCount }})
        </button>
    </div>

    <div class="flex-1 min-h-0 flex flex-col md:flex-row gap-3 overflow-hidden">
        {{-- Menu --}}
        <div class="flex-1 flex flex-col min-h-0 min-w-0 {{ $activePanel !== 'menu' ? 'hidden md:flex' : 'flex' }}">
            <input type="search"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search menu…"
                   class="tebo-input text-lg py-4 mb-3 shrink-0"
                   inputmode="search"
                   autocomplete="off">

            <div class="tablet-scroll-x mb-3 shrink-0">
                @foreach($categories as $cat)
                    <button wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                        class="tebo-tab {{ $selectedCategoryId === $cat->id ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            <div class="flex-1 overflow-y-auto tablet-menu-grid content-start pb-2">
                @forelse($menuItems as $item)
                    <button wire:click="quickAddOrConfigure({{ $item->id }})"
                        wire:loading.attr="disabled"
                        wire:target="quickAddOrConfigure({{ $item->id }})"
                        class="tebo-card tebo-touch p-4 text-left active:scale-[0.97] transition-transform min-h-[5rem] flex flex-col justify-between
                               {{ $selectedMenuItemId === $item->id ? 'border-tebo-amber ring-2 ring-tebo-amber/30' : '' }}">
                        <div class="font-semibold text-base leading-snug pr-1">{{ $item->name }}</div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-tebo-amber text-lg font-bold">{{ $item->formattedPrice() }}</span>
                            @if($item->modifierGroups->isNotEmpty())
                                <span class="text-[10px] text-tebo-cream/40 uppercase tracking-wide">Options</span>
                            @else
                                <span class="text-[10px] text-tebo-green font-bold">+ Tap add</span>
                            @endif
                        </div>
                    </button>
                @empty
                    <p class="col-span-full text-center text-tebo-cream/40 py-12">No items found</p>
                @endforelse
            </div>
        </div>

        {{-- Cart sidebar --}}
        <div class="w-full md:w-72 lg:w-80 shrink-0 flex flex-col min-h-0 {{ $activePanel !== 'cart' ? 'hidden md:flex' : 'flex' }}">
            <div class="tebo-card flex-1 flex flex-col min-h-0 overflow-hidden border-tebo-amber/20">
                <div class="p-4 border-b border-tebo-border flex items-center justify-between shrink-0">
                    <h3 class="font-display font-bold text-lg">Order</h3>
                    <span class="text-tebo-amber font-bold">{{ money($order->total_cents) }}</span>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-3">
                    @forelse($order->items->where('status', '!=', 'cancelled') as $item)
                        <div class="rounded-xl bg-tebo-darker/60 p-3 {{ $item->sent_at ? 'border-l-4 border-tebo-amber' : '' }}">
                            <div class="flex justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-base leading-tight">{{ $item->quantity }}× {{ $item->name }}</div>
                                    @if($item->has_allergy)
                                        <span class="text-xs text-tebo-red font-bold">⚠ {{ $item->allergy_note }}</span>
                                    @endif
                                    @foreach($item->modifiers as $mod)
                                        <div class="text-xs text-tebo-cream/40">+ {{ $mod->name }}</div>
                                    @endforeach
                                    @if($item->sent_at)
                                        <span class="text-[10px] text-tebo-cream/30 capitalize">{{ $item->status->value }}</span>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <div class="text-tebo-amber font-bold">{{ money($item->total_cents) }}</div>
                                    @if($order->canRemoveItems())
                                        <button wire:click="removeItem({{ $item->id }})"
                                            wire:confirm="{{ $item->sent_at ? 'Remove? Kitchen will be notified.' : 'Remove this item?' }}"
                                            class="mt-2 text-xs font-bold text-tebo-red px-2 py-1.5 rounded-lg bg-tebo-red/10 border border-tebo-red/20">
                                            Remove
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-10 text-tebo-cream/40">
                            <p class="text-4xl mb-2">🍽</p>
                            <p>Tap menu items to start</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Floating cart (portrait, on menu tab) --}}
    @if($activePanel === 'menu' && $itemCount > 0)
        <button wire:click="showCart" class="waiter-fab md:hidden">
            <span>{{ $itemCount }}</span>
            <span>View order</span>
            <span class="font-bold">{{ money($order->total_cents, false) }}</span>
        </button>
    @endif

    {{-- Action bar --}}
    <div class="shrink-0 mt-2 pt-2 border-t border-tebo-border/50 bg-tebo-dark/80">
        <div class="grid grid-cols-6 gap-2">
            <button wire:click="sendToKitchen" wire:loading.attr="disabled"
                class="col-span-3 tebo-touch-lg tebo-btn-primary text-lg font-bold py-4 rounded-2xl">
                <span wire:loading.remove wire:target="sendToKitchen">Kitchen</span>
                <span wire:loading wire:target="sendToKitchen">…</span>
            </button>
            <button wire:click="openBillPanel"
                class="col-span-3 tebo-touch-lg rounded-2xl font-bold py-4 border-2 border-tebo-amber/50 text-tebo-amber bg-tebo-amber/10">
                Bill
            </button>
            <button wire:click="markRush" class="col-span-2 tebo-touch-lg tebo-btn-ghost text-tebo-red border-tebo-red/30 font-bold text-sm py-3">
                Rush
            </button>
            <button wire:click="fireCourse" class="col-span-2 tebo-touch-lg tebo-btn-ghost font-bold text-sm py-3">
                Fire
            </button>
            <button wire:click="showCart" class="col-span-2 md:hidden tebo-touch-lg tebo-btn-ghost font-bold text-sm py-3">
                Order ({{ $itemCount }})
            </button>
        </div>
    </div>

    {{-- Bill sheet --}}
    @if($showBillPanel)
        @php
            $billTotal = max(0, $order->subtotal_cents + $order->tax_cents - $previewDiscountCents);
        @endphp
        <div class="tablet-sheet-backdrop" wire:click="closeBillPanel"></div>
        <div class="tablet-sheet overflow-y-auto kds-ticket-enter">
            <div class="w-12 h-1.5 bg-tebo-border rounded-full mx-auto mt-3 mb-2"></div>
            <div class="p-5 space-y-5 max-w-2xl mx-auto">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-tebo-cream/40 uppercase tracking-wide">Send bill</p>
                        <h3 class="font-display font-bold text-2xl mt-1">Table {{ $table->number }}</h3>
                        <p class="text-sm text-tebo-cream/50 mt-1">#{{ $order->order_number }}</p>
                    </div>
                    <button wire:click="closeBillPanel" class="tebo-touch-lg tebo-btn-ghost w-12 h-12 rounded-2xl text-2xl">×</button>
                </div>

                <div class="space-y-2 text-base">
                    @foreach($order->items->where('status', '!=', 'cancelled') as $item)
                        <div class="flex justify-between gap-2">
                            <span>{{ $item->quantity }}× {{ $item->name }}</span>
                            <span class="text-tebo-amber font-bold shrink-0">{{ money($item->total_cents) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl bg-tebo-darker p-4 space-y-2 text-base border border-tebo-border">
                    <div class="flex justify-between"><span class="text-tebo-cream/50">Subtotal</span><span>{{ money($order->subtotal_cents) }}</span></div>
                    <div class="flex justify-between"><span class="text-tebo-cream/50">{{ \App\Support\RestaurantProfile::taxLabel() }}</span><span>{{ money($order->tax_cents) }}</span></div>
                    <x-order.discount-fields
                        :discount-type="$discountType"
                        :discount-value="$discountValue"
                        :preview-cents="$previewDiscountCents"
                        live
                    />
                    <div class="flex justify-between text-2xl font-bold pt-2 border-t border-tebo-border">
                        <span>Total</span>
                        <span class="text-tebo-amber">{{ money($billTotal) }}</span>
                    </div>
                </div>

                <button wire:click="sendToCashier" wire:loading.attr="disabled"
                    class="tebo-touch-lg tebo-btn-primary w-full text-xl font-bold py-5 rounded-2xl sticky bottom-0">
                    <span wire:loading.remove wire:target="sendToCashier">Send to cashier</span>
                    <span wire:loading wire:target="sendToCashier">Sending…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Item sheet --}}
    @if($selectedItem)
        <div class="tablet-sheet-backdrop" wire:click="closeItemPanel"></div>
        <div class="tablet-sheet overflow-y-auto kds-ticket-enter">
            <div class="w-12 h-1.5 bg-tebo-border rounded-full mx-auto mt-3 mb-2"></div>
            <div class="p-5 space-y-5 max-w-2xl mx-auto">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs text-tebo-cream/40 uppercase tracking-wide">Customize</p>
                        <h3 class="font-display font-bold text-2xl mt-1">{{ $selectedItem->name }}</h3>
                        <p class="text-tebo-amber text-xl font-bold">{{ $selectedItem->formattedPrice() }}</p>
                    </div>
                    <button wire:click="closeItemPanel" class="tebo-touch-lg tebo-btn-ghost w-12 h-12 rounded-2xl text-2xl">×</button>
                </div>

                <div class="flex items-center justify-center gap-8 bg-tebo-darker rounded-2xl py-4">
                    <button wire:click="$set('quantity', max(1, $quantity - 1))" class="tebo-touch-lg w-14 h-14 rounded-2xl bg-tebo-surface border border-tebo-border text-3xl font-bold">−</button>
                    <span class="font-display text-5xl font-bold w-12 text-center">{{ $quantity }}</span>
                    <button wire:click="$set('quantity', $quantity + 1)" class="tebo-touch-lg w-14 h-14 rounded-2xl bg-tebo-surface border border-tebo-border text-3xl font-bold">+</button>
                </div>

                @foreach($selectedItem->modifierGroups as $group)
                    <div>
                        <label class="text-sm font-bold text-tebo-cream/60 uppercase tracking-wide">{{ $group->name }}</label>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            @foreach($group->modifiers as $mod)
                                <button type="button" wire:click="$toggle('selectedModifiers', {{ $mod->id }})"
                                    class="tebo-touch py-4 rounded-2xl border text-base font-semibold
                                           {{ in_array($mod->id, $selectedModifiers) ? 'bg-tebo-amber text-tebo-dark border-tebo-amber' : 'bg-tebo-darker border-tebo-border' }}">
                                    {{ $mod->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <input type="text" wire:model="instructions" placeholder="Special instructions…" class="tebo-input text-lg py-4">

                <button type="button" wire:click="$toggle('hasAllergy')"
                    class="tebo-touch w-full py-4 rounded-2xl border text-base font-bold
                           {{ $hasAllergy ? 'bg-tebo-red/20 border-tebo-red text-tebo-red' : 'border-tebo-border' }}">
                    Allergy alert
                </button>
                @if($hasAllergy)
                    <input type="text" wire:model="allergyNote" placeholder="Allergy details…" class="tebo-input text-lg py-4 border-tebo-red/50">
                @endif

                <button wire:click="addToOrder" wire:loading.attr="disabled"
                    class="tebo-touch-lg tebo-btn-primary w-full text-xl font-bold py-5 rounded-2xl sticky bottom-0">
                    Add {{ $quantity }} to order
                </button>
            </div>
        </div>
    @endif
</div>

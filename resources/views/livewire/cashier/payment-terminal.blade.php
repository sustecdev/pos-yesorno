<div class="cashier-terminal h-full flex flex-col min-h-0">
    {{-- Phone only: toggle orders vs payment (768px+ shows both) --}}
    <div class="md:hidden flex gap-2 mb-2 shrink-0">
        <button wire:click="showOrders" class="flex-1 tebo-tab {{ $activePanel === 'orders' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
            Orders ({{ $listTab === 'pending' ? $pendingOrders->count() : $recentOrders->count() }})
        </button>
        <button wire:click="showPayment" class="flex-1 tebo-tab {{ $activePanel === 'payment' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}" @disabled(! $order)>
            Payment
        </button>
    </div>

    <div class="flex-1 min-h-0 flex flex-col md:flex-row gap-3 overflow-hidden">
        {{-- Order list --}}
        <div class="cashier-orders-panel md:w-64 lg:w-72 xl:w-80 shrink-0 flex flex-col min-h-0 min-w-0 {{ $activePanel !== 'orders' ? 'hidden md:flex' : 'flex' }}">
            <div class="flex gap-2 mb-2 shrink-0">
                <button wire:click="setListTab('pending')"
                    class="flex-1 tebo-touch py-3 rounded-xl text-sm font-bold {{ $listTab === 'pending' ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
                    Pending ({{ $pendingOrders->count() }})
                </button>
                <button wire:click="setListTab('recent')"
                    class="flex-1 tebo-touch py-3 rounded-xl text-sm font-bold {{ $listTab === 'recent' ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
                    Recent ({{ $recentOrders->count() }})
                </button>
            </div>
            <input type="search"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search order #…"
                   class="tebo-input text-base sm:text-lg py-3 sm:py-4 mb-2 shrink-0"
                   inputmode="search">
            <div class="cashier-orders-scroll flex-1 min-h-0 overflow-y-auto space-y-2">
                @if($listTab === 'pending')
                    @forelse($pendingOrders as $pending)
                        <button wire:click="selectOrder({{ $pending->id }})"
                            class="tebo-card tebo-touch w-full text-left p-4 sm:p-5 active:scale-[0.98] transition-transform
                                   {{ $orderId === $pending->id ? 'border-tebo-amber ring-2 ring-tebo-amber/30' : '' }}
                                   {{ $pending->status->value === 'served' ? 'border-tebo-green/50 bg-tebo-green/5' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-bold text-base sm:text-lg">#{{ $pending->order_number }}</div>
                                @if($pending->status->value === 'served')
                                    <span class="px-2 py-1 rounded-lg text-xs font-bold bg-tebo-green text-tebo-dark">BILL</span>
                                @endif
                            </div>
                            <div class="text-sm sm:text-base text-tebo-cream/50 mt-1">Table {{ $pending->table?->number }} · {{ $pending->status->label() }}</div>
                            <div class="text-tebo-amber text-lg sm:text-xl font-bold mt-2">{{ money($pending->total_cents) }}</div>
                        </button>
                    @empty
                        <p class="text-tebo-cream/40 text-center py-8 text-sm">No bills waiting. Orders appear here when the waiter sends them to cashier.</p>
                    @endforelse
                @else
                    @forelse($recentOrders as $recent)
                        <button wire:click="selectOrder({{ $recent->id }})"
                            class="tebo-card tebo-touch w-full text-left p-4 sm:p-5 active:scale-[0.98] transition-transform
                                   {{ $orderId === $recent->id ? 'border-tebo-amber ring-2 ring-tebo-amber/30' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <div class="font-bold text-base sm:text-lg">#{{ $recent->order_number }}</div>
                                <span class="px-2 py-1 rounded-lg text-xs font-bold bg-tebo-green/20 text-tebo-green">PAID</span>
                            </div>
                            <div class="text-sm sm:text-base text-tebo-cream/50 mt-1">Table {{ $recent->table?->number }} · {{ $recent->paid_at?->format('H:i') }}</div>
                            <div class="text-tebo-amber text-lg sm:text-xl font-bold mt-2">{{ money($recent->total_cents) }}</div>
                        </button>
                    @empty
                        <p class="text-tebo-cream/40 text-center py-8 text-sm">No paid orders today yet.</p>
                    @endforelse
                @endif
            </div>
        </div>

        {{-- Payment panel --}}
        <div class="cashier-payment-panel flex-1 flex flex-col min-h-0 min-w-0 {{ $activePanel !== 'payment' ? 'hidden md:flex' : 'flex' }}">
            @if($order)
                <div class="cashier-payment-scroll flex-1 min-h-0 overflow-y-auto space-y-3 sm:space-y-4">
                    <div class="tebo-card p-4 sm:p-6 shrink-0">
                        <x-restaurant.bill-header :profile="$restaurant" compact class="mb-4 pb-4 border-b border-tebo-border" />

                        <div class="flex justify-between items-start gap-4">
                            <div class="min-w-0">
                                <h2 class="font-display text-2xl sm:text-3xl font-bold truncate">#{{ $order->order_number }}</h2>
                                <p class="text-base sm:text-lg text-tebo-cream/50 mt-1 truncate">Table {{ $order->table?->number }} · {{ $order->waiter?->name }}</p>
                            </div>
                            <span class="shrink-0 px-3 py-1.5 rounded-xl text-sm font-medium bg-tebo-surface border border-tebo-border">{{ $order->status->label() }}</span>
                        </div>

                        <div class="space-y-2 sm:space-y-3 my-4 sm:my-5 text-sm sm:text-base">
                            @foreach($order->items->where('status', '!=', 'cancelled') as $item)
                                <div class="flex justify-between gap-2">
                                    <span class="font-medium">{{ $item->quantity }}× {{ $item->name }}</span>
                                    <span class="text-tebo-amber font-bold shrink-0">{{ money($item->total_cents) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-tebo-border pt-4 space-y-2">
                            <div class="flex justify-between text-sm sm:text-base"><span class="text-tebo-cream/50">Subtotal</span><span>{{ money($order->subtotal_cents) }}</span></div>
                            <div class="flex justify-between text-sm sm:text-base"><span class="text-tebo-cream/50">{{ \App\Support\RestaurantProfile::taxLabel() }}</span><span>{{ money($order->tax_cents) }}</span></div>
                            @if($order->discount_cents > 0)
                                <div class="flex justify-between text-sm sm:text-base text-tebo-green">
                                    <span>{{ \App\Support\DiscountCalculator::label($order->discount_type ?? 'flat', (int) ($order->discount_value ?? 0), $order->discount_cents) }}</span>
                                    <span>-{{ money($order->discount_cents) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-2xl sm:text-3xl font-bold pt-2"><span>Total</span><span class="text-tebo-amber">{{ money($order->total_cents) }}</span></div>
                        </div>
                    </div>

                    @if($order->status->value === 'paid')
                        <div class="tebo-card p-4 sm:p-5 shrink-0 space-y-3">
                            <p class="text-tebo-green font-medium text-base sm:text-lg">Paid {{ $order->paid_at?->format('M j, H:i') }}</p>
                            @foreach($order->receipts as $receipt)
                                <a href="{{ route('receipts.show', $receipt) }}" target="_blank"
                                   class="tebo-touch-lg tebo-btn-primary w-full inline-block text-center text-base sm:text-lg font-bold py-4">
                                    Reprint {{ $receipt->receipt_number }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="tebo-card p-4 space-y-3 shrink-0">
                            <h4 class="font-medium text-base sm:text-lg">Discount</h4>
                            <x-order.discount-fields
                                :discount-type="$discountType"
                                :discount-value="$discountValue"
                                :preview-cents="$previewDiscountCents"
                            />
                            <button wire:click="applyDiscount" class="tebo-touch-lg tebo-btn-ghost w-full">Apply discount</button>
                        </div>

                        <div class="tebo-card p-4 sm:p-5 space-y-4 shrink-0">
                            <div class="grid grid-cols-3 gap-2 sm:gap-3">
                                @foreach(\App\Enums\PaymentMethod::cases() as $method)
                                    <button wire:click="$set('paymentMethod', '{{ $method->value }}')"
                                        class="tebo-touch-lg rounded-2xl text-sm sm:text-lg font-bold capitalize
                                               {{ $paymentMethod === $method->value ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
                                        {{ $method->value }}
                                    </button>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-4 gap-2">
                                @foreach([0, 500, 1000, 1500] as $tip)
                                    <button wire:click="$set('tipCents', {{ $tip }})" class="tebo-touch py-3 rounded-xl text-xs sm:text-sm font-medium {{ $tipCents === $tip ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
                                        {{ $tip ? money($tip, false) : 'No tip' }}
                                    </button>
                                @endforeach
                            </div>

                            <button wire:click="processPayment" wire:loading.attr="disabled" class="tebo-touch-lg tebo-btn-primary w-full text-xl sm:text-2xl font-bold py-4 sm:py-5">
                                <span wire:loading.remove wire:target="processPayment">Charge {{ money($order->total_cents) }}</span>
                                <span wire:loading wire:target="processPayment">Processing…</span>
                            </button>
                        </div>
                    @endif

                    @if($order->payments->isNotEmpty())
                        <div class="tebo-card p-4 shrink-0">
                            @foreach($order->payments as $payment)
                                <div class="text-base sm:text-lg text-tebo-green font-medium py-1">{{ money($payment->amount_cents) }} — {{ $payment->method->value }}</div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="flex-1 flex items-center justify-center text-tebo-cream/30 text-base sm:text-xl p-6 text-center">
                    Select an order to process payment
                </div>
            @endif
        </div>
    </div>
</div>

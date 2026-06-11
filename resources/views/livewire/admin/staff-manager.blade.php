<div>
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <h2 class="font-display text-2xl font-bold">Staff</h2>
        <button wire:click="openForm" class="tebo-btn-primary whitespace-nowrap">+ Add staff</button>
    </div>

    <div class="tablet-scroll-x mb-6">
        <button wire:click="$set('filterRole', 'all')"
            class="tebo-tab {{ $filterRole === 'all' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">
            All
        </button>
        @foreach($roles as $roleOption)
            <button wire:click="$set('filterRole', '{{ $roleOption }}')"
                class="tebo-tab {{ $filterRole === $roleOption ? 'tebo-tab-active' : 'tebo-tab-inactive' }} capitalize">
                {{ $roleOption }}
            </button>
        @endforeach
    </div>

    @if($viewingUser)
        <div class="tebo-card p-5 md:p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5">
                <div>
                    <h3 class="font-display font-bold text-xl">Orders — {{ $viewingUser->name }}</h3>
                    <p class="text-sm text-tebo-cream/50 mt-1">{{ $viewingUser->email }} · <span class="capitalize">{{ $viewingUser->roles->first()?->name }}</span></p>
                </div>
                <button wire:click="closeOrders" class="tebo-btn-ghost shrink-0">Back to staff</button>
            </div>

            <div class="grid sm:grid-cols-3 gap-3 mb-5">
                <div class="rounded-xl bg-tebo-darker border border-tebo-border p-4">
                    <div class="text-xs text-tebo-cream/40 uppercase tracking-wide">Total orders</div>
                    <div class="font-display text-2xl font-bold mt-1">{{ $orderStats['total'] }}</div>
                </div>
                <div class="rounded-xl bg-tebo-darker border border-tebo-border p-4">
                    <div class="text-xs text-tebo-cream/40 uppercase tracking-wide">Open orders</div>
                    <div class="font-display text-2xl font-bold mt-1 text-tebo-amber">{{ $orderStats['open'] }}</div>
                </div>
                <div class="rounded-xl bg-tebo-darker border border-tebo-border p-4">
                    <div class="text-xs text-tebo-cream/40 uppercase tracking-wide">Paid revenue</div>
                    <div class="font-display text-2xl font-bold mt-1 text-tebo-green">{{ money($orderStats['revenue']) }}</div>
                </div>
            </div>

            <div class="flex gap-2 mb-4">
                <button wire:click="$set('orderFilter', 'all')"
                    class="tebo-tab {{ $orderFilter === 'all' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">All</button>
                <button wire:click="$set('orderFilter', 'open')"
                    class="tebo-tab {{ $orderFilter === 'open' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">Open</button>
                <button wire:click="$set('orderFilter', 'paid')"
                    class="tebo-tab {{ $orderFilter === 'paid' ? 'tebo-tab-active' : 'tebo-tab-inactive' }}">Paid</button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-tebo-darker">
                        <tr class="text-tebo-cream/50 text-left">
                            <th class="p-3">Order</th>
                            <th class="p-3">Table</th>
                            <th class="p-3">Items</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Total</th>
                            <th class="p-3">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr class="border-t border-tebo-border/50">
                                <td class="p-3 font-medium">#{{ $order->order_number }}</td>
                                <td class="p-3">{{ $order->table?->number ?? '—' }}</td>
                                <td class="p-3 text-tebo-cream/50">{{ $order->items_count }}</td>
                                <td class="p-3">
                                    <span class="px-2 py-1 rounded-lg text-xs font-medium capitalize bg-tebo-surface border border-tebo-border">
                                        {{ $order->status->label() }}
                                    </span>
                                    @if($order->is_rush)
                                        <span class="text-xs text-tebo-red font-bold ml-1">RUSH</span>
                                    @endif
                                </td>
                                <td class="p-3 text-tebo-amber font-medium">{{ money($order->total_cents) }}</td>
                                <td class="p-3 text-tebo-cream/50">{{ $order->created_at->format('M j, g:i A') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-tebo-cream/30">No orders for this filter</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="mt-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($showForm)
        <form wire:submit="save" class="tebo-card p-5 md:p-6 mb-6 grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <h3 class="font-display font-bold text-lg">{{ $editingId ? 'Edit staff' : 'New staff member' }}</h3>
            </div>
            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Name</label>
                <input type="text" wire:model="name" class="tebo-input" placeholder="Full name" autocomplete="name">
                @error('name') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Email</label>
                <input type="email" wire:model="email" class="tebo-input" placeholder="staff@restaurant.com" autocomplete="username">
                @error('email') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Role</label>
                <select wire:model="role" class="tebo-input capitalize">
                    @foreach($roles as $roleOption)
                        <option value="{{ $roleOption }}">{{ $roleOption }}</option>
                    @endforeach
                </select>
                @error('role') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">
                    Password {{ $editingId ? '(leave blank to keep)' : '' }}
                </label>
                <input type="password" wire:model="password" class="tebo-input" placeholder="Min. 8 characters" autocomplete="new-password">
                @error('password') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="tebo-btn-primary flex-1">{{ $editingId ? 'Update' : 'Add staff' }}</button>
                <button type="button" wire:click="cancel" class="tebo-btn-ghost flex-1">Cancel</button>
            </div>
        </form>
    @endif

    <div class="tebo-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tebo-darker">
                <tr class="text-tebo-cream/50 text-left">
                    <th class="p-4">Name</th>
                    <th class="p-4">Email</th>
                    <th class="p-4">Role</th>
                    <th class="p-4">Orders</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    <tr class="border-t border-tebo-border/50">
                        <td class="p-4 font-medium">
                            {{ $member->name }}
                            @if($member->id === $currentUserId)
                                <span class="text-xs text-tebo-cream/40">(you)</span>
                            @endif
                        </td>
                        <td class="p-4 text-tebo-cream/50">{{ $member->email }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-lg text-xs font-medium capitalize bg-tebo-surface border border-tebo-border">
                                {{ $member->roles->first()?->name ?? '—' }}
                            </span>
                        </td>
                        <td class="p-4 text-tebo-cream/50">{{ $member->orders_count }}</td>
                        <td class="p-4 text-right space-x-3">
                            <button wire:click="viewOrders({{ $member->id }})" class="text-tebo-cream/70 text-sm font-medium hover:text-tebo-amber">Orders</button>
                            <button wire:click="edit({{ $member->id }})" class="text-tebo-amber text-sm font-medium">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-tebo-cream/30">No staff match this filter</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<x-layouts.app-shell :title="$title ?? 'Admin'" nav="Admin">
    <div class="flex min-h-[calc(100vh-57px)]">
        <aside class="w-56 bg-tebo-darker border-r border-tebo-border p-4 hidden md:block">
            <nav class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.dashboard') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Dashboard</a>
                <a href="{{ route('admin.restaurant') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.restaurant') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Restaurant</a>
                <a href="{{ route('admin.tax') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.tax') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Tax</a>
                <a href="{{ route('admin.menu') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.menu') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Menu</a>
                <a href="{{ route('admin.staff') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.staff') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Staff</a>
                <a href="{{ route('admin.inventory') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.inventory') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Inventory</a>
                <a href="{{ route('admin.reports') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm {{ request()->routeIs('admin.reports') ? 'bg-tebo-surface text-tebo-amber' : '' }}">Reports</a>
                <hr class="border-tebo-border my-3">
                <p class="px-3 text-xs uppercase tracking-wide text-tebo-cream/30 mb-1">Workspaces</p>
                <a href="{{ route('waiter.floor') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm text-tebo-cream/60">Waiter Floor</a>
                <a href="{{ route('kitchen.kds') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm text-tebo-cream/60">Kitchen KDS</a>
                <a href="{{ route('cashier.terminal') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm text-tebo-cream/60">Cashier</a>
                <a href="{{ route('host.reservations') }}" class="block px-3 py-2 rounded-lg hover:bg-tebo-surface text-sm text-tebo-cream/60">Host</a>
            </nav>
        </aside>
        <div class="flex-1 p-6">
            {{ $slot }}
        </div>
    </div>
</x-layouts.app-shell>

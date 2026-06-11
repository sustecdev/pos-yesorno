<x-layouts.app-shell :title="$title ?? 'Cashier'" nav="Payment Terminal" :tablet="true">
    <div class="cashier-main">
        {{ $slot }}
    </div>
</x-layouts.app-shell>

<x-layouts.app-shell :title="$title ?? 'Cashier'" nav="Payment Terminal" :tablet="true">
    <div class="h-[calc(100dvh-57px)] overflow-hidden p-4 md:p-6">
        {{ $slot }}
    </div>
</x-layouts.app-shell>

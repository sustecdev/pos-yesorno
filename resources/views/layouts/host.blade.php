<x-layouts.app-shell :title="$title ?? 'Reservations'" nav="Reservations" :tablet="true">
    <div class="max-w-6xl mx-auto p-4 md:p-6 tablet-safe-bottom">
        {{ $slot }}
    </div>
</x-layouts.app-shell>

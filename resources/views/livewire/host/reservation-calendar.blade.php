<div>
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <h2 class="font-display text-2xl md:text-3xl font-bold">Reservations</h2>
        <div class="flex gap-3">
            <input type="date" wire:model.live="selectedDate" class="tebo-input tebo-touch text-lg py-4 w-auto flex-1 md:flex-none">
            <button wire:click="openForm" class="tebo-touch-lg tebo-btn-primary whitespace-nowrap">+ New</button>
        </div>
    </div>

    @if($showForm)
        <form wire:submit="saveReservation" class="tebo-card p-5 md:p-6 mb-6 grid sm:grid-cols-2 gap-4">
            <input type="text" wire:model="guestName" placeholder="Guest name *" class="tebo-input text-lg py-4">
            <input type="tel" wire:model="guestPhone" placeholder="Phone" class="tebo-input text-lg py-4" inputmode="tel">
            <input type="number" wire:model="partySize" min="1" max="20" class="tebo-input text-lg py-4" inputmode="numeric" placeholder="Party size">
            <input type="datetime-local" wire:model="reservedAt" class="tebo-input text-lg py-4">
            <select wire:model="tableId" class="tebo-input text-lg py-4 sm:col-span-2">
                <option value="">Assign table (optional)</option>
                @foreach($tables as $table)
                    <option value="{{ $table->id }}">Table {{ $table->number }} ({{ $table->area->name }}) — {{ $table->status->value }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="notes" placeholder="Notes" class="tebo-input text-lg py-4 sm:col-span-2">
            <div class="sm:col-span-2 flex gap-3">
                <button type="submit" class="flex-1 tebo-touch-lg tebo-btn-primary">Save</button>
                <button type="button" wire:click="$set('showForm', false)" class="flex-1 tebo-touch-lg tebo-btn-ghost">Cancel</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse($reservations as $reservation)
            <div class="tebo-card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="font-bold text-xl">{{ $reservation->guest_name }}</div>
                    <div class="text-base text-tebo-cream/50 mt-1">
                        {{ $reservation->reserved_at->format('g:i A') }} · {{ $reservation->party_size }} guests
                        @if($reservation->table) · Table {{ $reservation->table->number }} @endif
                    </div>
                    @if($reservation->notes)
                        <div class="text-sm text-tebo-cream/40 mt-2">{{ $reservation->notes }}</div>
                    @endif
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="px-3 py-2 rounded-xl text-sm font-medium capitalize bg-tebo-surface border border-tebo-border">{{ $reservation->status->value }}</span>
                    @if($reservation->status->value === 'confirmed')
                        <button wire:click="seatGuest({{ $reservation->id }})" class="tebo-touch-lg tebo-btn-primary">Seat Guest</button>
                        <button wire:click="cancelReservation({{ $reservation->id }})" class="tebo-touch px-4 py-3 text-tebo-red font-medium">Cancel</button>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-tebo-cream/30 text-center py-16 text-lg">No reservations for this date</p>
        @endforelse
    </div>
</div>

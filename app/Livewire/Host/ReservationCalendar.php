<?php

namespace App\Livewire\Host;

use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\Reservation;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.host')]
#[Title('Reservations')]
class ReservationCalendar extends Component
{
    public string $selectedDate;

    public bool $showForm = false;

    public string $guestName = '';

    public string $guestPhone = '';

    public int $partySize = 2;

    public string $reservedAt = '';

    public ?int $tableId = null;

    public string $notes = '';

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
        $this->reservedAt = now()->addHour()->format('Y-m-d\TH:i');
    }

    public function openForm(): void
    {
        $this->showForm = true;
    }

    public function saveReservation(): void
    {
        $this->validate([
            'guestName' => 'required|min:2',
            'partySize' => 'required|integer|min:1|max:20',
            'reservedAt' => 'required|date',
        ]);

        Reservation::query()->create([
            'guest_name' => $this->guestName,
            'guest_phone' => $this->guestPhone ?: null,
            'party_size' => $this->partySize,
            'reserved_at' => $this->reservedAt,
            'dining_table_id' => $this->tableId,
            'host_id' => Auth::id(),
            'status' => ReservationStatus::Confirmed,
            'notes' => $this->notes ?: null,
        ]);

        if ($this->tableId) {
            DiningTable::query()->find($this->tableId)?->update(['status' => TableStatus::Reserved]);
        }

        $this->reset(['guestName', 'guestPhone', 'partySize', 'tableId', 'notes', 'showForm']);
        $this->reservedAt = now()->addHour()->format('Y-m-d\TH:i');
        $this->dispatch('toast', message: 'Reservation created!', type: 'success');
    }

    public function seatGuest(int $reservationId, OrderService $orderService): void
    {
        $reservation = Reservation::query()->with('table')->findOrFail($reservationId);

        if (! $reservation->table) {
            $this->dispatch('toast', message: 'Assign a table first.', type: 'error');

            return;
        }

        $reservation->update(['status' => ReservationStatus::Seated]);
        $reservation->table->update(['status' => TableStatus::Occupied]);
        $orderService->createForTable($reservation->table, Auth::user());

        $this->dispatch('toast', message: 'Guest seated!', type: 'success');
    }

    public function cancelReservation(int $reservationId): void
    {
        $reservation = Reservation::query()->findOrFail($reservationId);
        $reservation->update(['status' => ReservationStatus::Cancelled]);

        if ($reservation->dining_table_id) {
            DiningTable::query()->find($reservation->dining_table_id)?->update(['status' => TableStatus::Free]);
        }
    }

    public function render()
    {
        $reservations = Reservation::query()
            ->with('table')
            ->whereDate('reserved_at', $this->selectedDate)
            ->orderBy('reserved_at')
            ->get();

        $tables = DiningTable::query()->with('area')->orderBy('number')->get();

        return view('livewire.host.reservation-calendar', compact('reservations', 'tables'));
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\InventoryItem;
use App\Services\InventoryService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Inventory')]
class InventoryManager extends Component
{
    public ?int $adjustItemId = null;

    public float $adjustQuantity = 0;

    public string $adjustNotes = '';

    public function openAdjust(int $id): void
    {
        $this->adjustItemId = $id;
        $this->adjustQuantity = 0;
        $this->adjustNotes = '';
    }

    public function applyAdjustment(InventoryService $inventory): void
    {
        $item = InventoryItem::findOrFail($this->adjustItemId);
        $inventory->adjust($item, $this->adjustQuantity, 'adjustment', Auth::id(), $this->adjustNotes);
        $this->adjustItemId = null;
        $this->dispatch('toast', message: 'Stock updated!', type: 'success');
    }

    public function delete(int $id): void
    {
        $item = InventoryItem::findOrFail($id);

        if ($this->adjustItemId === $id) {
            $this->adjustItemId = null;
        }

        $item->delete();

        $this->dispatch('toast', message: 'Inventory item deleted.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.inventory-manager', [
            'items' => InventoryItem::with('supplier')->orderBy('name')->get(),
            'lowStock' => app(InventoryService::class)->lowStockItems(),
        ]);
    }
}

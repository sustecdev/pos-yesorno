<?php

namespace App\Livewire\Admin;

use App\Models\KitchenStation;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Menu')]
class MenuManager extends Component
{
    public string $name = '';

    public int $priceCents = 0;

    public ?int $categoryId = null;

    public ?int $stationId = null;

    public ?int $editingId = null;

    public function edit(int $id): void
    {
        $item = MenuItem::findOrFail($id);
        $this->editingId = $id;
        $this->name = $item->name;
        $this->priceCents = $item->price_cents;
        $this->categoryId = $item->menu_category_id;
        $this->stationId = $item->kitchen_station_id;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|min:2',
            'priceCents' => 'required|integer|min:0',
            'categoryId' => 'required|exists:menu_categories,id',
        ]);

        MenuItem::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->name,
                'price_cents' => $this->priceCents,
                'menu_category_id' => $this->categoryId,
                'kitchen_station_id' => $this->stationId,
                'is_active' => true,
                'is_available' => true,
            ]
        );

        $this->reset(['name', 'priceCents', 'categoryId', 'stationId', 'editingId']);
        $this->dispatch('toast', message: 'Menu item saved!', type: 'success');
    }

    public function toggleAvailability(int $id): void
    {
        $item = MenuItem::findOrFail($id);
        $item->update(['is_available' => ! $item->is_available]);
    }

    public function delete(int $id): void
    {
        $item = MenuItem::findOrFail($id);

        if ($this->editingId === $id) {
            $this->reset(['name', 'priceCents', 'categoryId', 'stationId', 'editingId']);
        }

        $item->delete();

        $this->dispatch('toast', message: 'Menu item deleted.', type: 'success');
    }

    public function render()
    {
        return view('livewire.admin.menu-manager', [
            'categories' => MenuCategory::orderBy('sort_order')->get(),
            'stations' => KitchenStation::orderBy('sort_order')->get(),
            'items' => MenuItem::with(['category', 'kitchenStation'])->orderBy('name')->get(),
        ]);
    }
}

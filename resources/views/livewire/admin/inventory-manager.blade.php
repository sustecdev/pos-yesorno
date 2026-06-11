<div>
    <h2 class="font-display text-2xl font-bold mb-6">Inventory</h2>

    @if($lowStock->isNotEmpty())
        <div class="tebo-card p-4 mb-6 border-tebo-red/30">
            <h3 class="text-tebo-red font-bold mb-2">Low Stock Warning</h3>
            @foreach($lowStock as $item)
                <span class="inline-block mr-3 text-sm">{{ $item->name }} ({{ $item->quantity }} {{ $item->unit }})</span>
            @endforeach
        </div>
    @endif

    <div class="tebo-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tebo-darker">
                <tr class="text-tebo-cream/50 text-left">
                    <th class="p-4">Item</th>
                    <th class="p-4">SKU</th>
                    <th class="p-4">Quantity</th>
                    <th class="p-4">Reorder Level</th>
                    <th class="p-4">Unit Cost</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr class="border-t border-tebo-border/50 {{ $item->isLowStock() ? 'bg-tebo-red/5' : '' }}">
                        <td class="p-4 font-medium">{{ $item->name }}</td>
                        <td class="p-4 text-tebo-cream/50">{{ $item->sku }}</td>
                        <td class="p-4 {{ $item->isLowStock() ? 'text-tebo-red font-bold' : '' }}">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="p-4 text-tebo-cream/50">{{ $item->reorder_level }}</td>
                        <td class="p-4">{{ money($item->unit_cost_cents) }}</td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <button wire:click="openAdjust({{ $item->id }})" class="text-tebo-amber text-sm font-medium">Adjust</button>
                                <button wire:click="delete({{ $item->id }})"
                                    wire:confirm="Delete {{ $item->name }}? Linked recipes will also be removed."
                                    class="text-tebo-red text-sm font-medium">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($adjustItemId)
        <div class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 p-4">
            <form wire:submit="applyAdjustment" class="tebo-card p-6 w-full max-w-md space-y-4">
                <h3 class="font-display font-bold">Adjust Stock</h3>
                <input type="number" step="0.001" wire:model="adjustQuantity" placeholder="Quantity (+/-)" class="tebo-input">
                <input type="text" wire:model="adjustNotes" placeholder="Notes" class="tebo-input">
                <div class="flex gap-2">
                    <button type="submit" class="tebo-btn-primary flex-1">Apply</button>
                    <button type="button" wire:click="$set('adjustItemId', null)" class="tebo-btn-ghost flex-1">Cancel</button>
                </div>
            </form>
        </div>
    @endif
</div>

<div>
    <h2 class="font-display text-2xl font-bold mb-6">Menu Manager</h2>

    <form wire:submit="save" class="tebo-card p-5 mb-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <input type="text" wire:model="name" placeholder="Item name" class="tebo-input">
        <input type="number" wire:model="priceCents" placeholder="Price (ngwee)" class="tebo-input">
        <select wire:model="categoryId" class="tebo-input">
            <option value="">Category</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <select wire:model="stationId" class="tebo-input">
            <option value="">Kitchen station</option>
            @foreach($stations as $station)
                <option value="{{ $station->id }}">{{ $station->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="tebo-btn-primary sm:col-span-2 lg:col-span-4">{{ $editingId ? 'Update' : 'Add' }} Item</button>
    </form>

    <div class="tebo-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-tebo-darker">
                <tr class="text-tebo-cream/50 text-left">
                    <th class="p-4">Name</th>
                    <th class="p-4">Category</th>
                    <th class="p-4">Station</th>
                    <th class="p-4">Price</th>
                    <th class="p-4">Available</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr class="border-t border-tebo-border/50">
                        <td class="p-4 font-medium">{{ $item->name }}</td>
                        <td class="p-4 text-tebo-cream/50">{{ $item->category->name }}</td>
                        <td class="p-4 text-tebo-cream/50">{{ $item->kitchenStation?->name ?? '—' }}</td>
                        <td class="p-4 text-tebo-amber">{{ $item->formattedPrice() }}</td>
                        <td class="p-4">
                            <button wire:click="toggleAvailability({{ $item->id }})"
                                class="px-2 py-1 rounded text-xs {{ $item->is_available ? 'bg-tebo-green/20 text-tebo-green' : 'bg-tebo-red/20 text-tebo-red' }}">
                                {{ $item->is_available ? 'Yes' : 'No' }}
                            </button>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <button wire:click="edit({{ $item->id }})" class="text-tebo-amber text-sm font-medium">Edit</button>
                                <button wire:click="delete({{ $item->id }})"
                                    wire:confirm="Delete {{ $item->name }}? This cannot be undone."
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
</div>

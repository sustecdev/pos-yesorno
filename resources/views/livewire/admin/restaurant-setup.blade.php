<div>
    <h2 class="font-display text-2xl font-bold mb-6">Restaurant Setup</h2>

    <div class="grid lg:grid-cols-2 gap-6">
        <form wire:submit="save" class="tebo-card p-5 md:p-6 space-y-4">
            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Restaurant name *</label>
                <input type="text" wire:model="name" class="tebo-input" placeholder="e.g. My Restaurant & Bar">
                @error('name') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Tagline</label>
                <input type="text" wire:model="tagline" class="tebo-input" placeholder="e.g. Fine dining & grill">
                @error('tagline') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Address / location</label>
                <input type="text" wire:model="location" class="tebo-input" placeholder="Street address">
                @error('location') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">City</label>
                <input type="text" wire:model="city" class="tebo-input" placeholder="e.g. Lusaka">
                @error('city') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-tebo-cream/50 mb-1">Phone</label>
                    <input type="tel" wire:model="phone" class="tebo-input" placeholder="+260 ...">
                    @error('phone') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm text-tebo-cream/50 mb-1">Email</label>
                    <input type="email" wire:model="email" class="tebo-input" placeholder="info@restaurant.com">
                    @error('email') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <p class="text-sm text-tebo-cream/40">
                Tax rate and TPIN are configured under
                <a href="{{ route('admin.tax') }}" class="text-tebo-amber hover:underline">Admin → Tax</a>.
            </p>

            <div>
                <label class="block text-sm text-tebo-cream/50 mb-1">Logo</label>
                @if($currentLogoUrl)
                    <div class="flex items-center gap-4 mb-3">
                        <img src="{{ $currentLogoUrl }}" alt="Current logo" class="h-16 w-auto object-contain rounded-lg bg-tebo-darker border border-tebo-border p-2">
                        <button type="button" wire:click="removeLogo" class="text-tebo-red text-sm font-medium">Remove logo</button>
                    </div>
                @endif
                <input type="file" wire:model="logo" accept="image/*" class="tebo-input file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-tebo-amber file:text-tebo-dark file:font-medium">
                @error('logo') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                <div wire:loading wire:target="logo" class="text-sm text-tebo-cream/40 mt-1">Uploading…</div>
            </div>

            <button type="submit" class="tebo-btn-primary w-full" wire:loading.attr="disabled">Save restaurant</button>
        </form>

        <div class="tebo-card p-5 md:p-6">
            <h3 class="font-display font-bold mb-4">Bill preview</h3>
            <div class="bg-white text-gray-900 rounded-xl p-6 max-w-sm mx-auto shadow-inner">
                <x-restaurant.bill-header :profile="$preview" light />
                <hr class="my-4 border-gray-200">
                <p class="text-xs text-gray-500">Receipt R12345678</p>
                <p class="text-xs text-gray-500">Order: TABC123 · Table 5</p>
                <p class="text-xs text-gray-500 mb-3">Waiter: Wanda</p>
                <div class="flex justify-between text-sm py-1 border-b border-gray-100">
                    <span>2× Grilled Salmon</span>
                    <span>K 56.00</span>
                </div>
                <div class="flex justify-between font-bold text-sm mt-3 pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span>K 60.48</span>
                </div>
                <p class="text-[10px] text-gray-400 mt-4 text-center">Thank you for dining with us</p>
            </div>
        </div>
    </div>
</div>

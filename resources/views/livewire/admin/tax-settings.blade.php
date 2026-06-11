<div>
    <h2 class="font-display text-2xl font-bold mb-2">Tax Settings</h2>
    <p class="text-tebo-cream/50 text-sm mb-6">Configure how tax is calculated on orders, bills, and receipts.</p>

    <div class="grid lg:grid-cols-2 gap-6">
        <form wire:submit="save" class="tebo-card p-5 md:p-6 space-y-5">
            <label class="flex items-center justify-between gap-4 tebo-touch p-4 rounded-xl bg-tebo-darker border border-tebo-border cursor-pointer">
                <div>
                    <span class="font-medium">Charge tax on orders</span>
                    <p class="text-sm text-tebo-cream/40 mt-0.5">Turn off for tax-inclusive pricing or exempt sales</p>
                </div>
                <input type="checkbox" wire:model.live="taxEnabled" class="w-6 h-6 rounded border-tebo-border">
            </label>

            <div class="{{ $taxEnabled ? '' : 'opacity-50 pointer-events-none' }}">
                <div>
                    <label class="block text-sm text-tebo-cream/50 mb-1">Tax name on bills *</label>
                    <input type="text" wire:model="taxLabel" class="tebo-input" placeholder="e.g. VAT">
                    @error('taxLabel') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="mt-4">
                    <label class="block text-sm text-tebo-cream/50 mb-1">Tax rate (%) *</label>
                    <div class="flex gap-2 mb-2">
                        @foreach([0, 10, 16] as $preset)
                            <button type="button"
                                    wire:click="$set('taxRatePercent', '{{ $preset }}')"
                                    class="tebo-touch px-4 py-2 rounded-lg text-sm font-bold {{ (float) $taxRatePercent === (float) $preset ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
                                {{ $preset }}%
                            </button>
                        @endforeach
                    </div>
                    <input type="number" wire:model.live="taxRatePercent" step="0.01" min="0" max="100" class="tebo-input" placeholder="16">
                    @error('taxRatePercent') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-tebo-cream/40 mt-1">Zambia standard VAT is 16%. Tax is applied to the order subtotal.</p>
                </div>

                <div class="mt-4">
                    <label class="block text-sm text-tebo-cream/50 mb-1">TPIN / Tax registration number</label>
                    <input type="text" wire:model="taxId" class="tebo-input" placeholder="Shown on receipts">
                    @error('taxId') <p class="text-tebo-red text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="tebo-btn-primary w-full" wire:loading.attr="disabled">Save tax settings</button>
        </form>

        <div class="tebo-card p-5 md:p-6">
            <h3 class="font-display font-bold mb-4">Example calculation</h3>
            <div class="bg-white text-gray-900 rounded-xl p-6 max-w-sm mx-auto shadow-inner space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span>{{ money($sampleSubtotal) }}</span></div>
                @if($taxEnabled && (float) $taxRatePercent > 0)
                    <div class="flex justify-between text-gray-600">
                        <span>{{ $taxLabel }} ({{ $taxRatePercent }}%)</span>
                        <span>{{ money($sampleTax) }}</span>
                    </div>
                @else
                    <div class="flex justify-between text-gray-400"><span>Tax</span><span>{{ money(0) }}</span></div>
                @endif
                <div class="flex justify-between font-bold text-base pt-2 border-t border-gray-200">
                    <span>Total</span>
                    <span>{{ money($sampleTotal) }}</span>
                </div>
            </div>
            @if($taxId)
                <p class="text-center text-sm text-tebo-cream/40 mt-4">TPIN on receipt: {{ $taxId }}</p>
            @endif
        </div>
    </div>
</div>

@props(['discountType', 'discountValue', 'previewCents' => 0, 'live' => false])

<div class="space-y-3">
    <div class="flex gap-2">
        <button type="button"
                wire:click="$set('discountType', 'flat')"
                class="flex-1 tebo-touch py-3 rounded-xl text-sm font-bold {{ $discountType === 'flat' ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
            Flat (K)
        </button>
        <button type="button"
                wire:click="$set('discountType', 'percent')"
                class="flex-1 tebo-touch py-3 rounded-xl text-sm font-bold {{ $discountType === 'percent' ? 'bg-tebo-amber text-tebo-dark' : 'bg-tebo-darker border border-tebo-border' }}">
            Percent (%)
        </button>
    </div>
    <div>
        <label class="block text-sm text-tebo-cream/50 mb-2">
            {{ $discountType === 'percent' ? 'Discount (%)' : 'Discount (ngwee)' }}
        </label>
        <input type="number"
               wire:model{{ $live ? '.live' : '' }}="discountValue"
               min="0"
               max="{{ $discountType === 'percent' ? 100 : '' }}"
               step="{{ $discountType === 'percent' ? 1 : 1 }}"
               class="tebo-input text-lg py-4"
               inputmode="numeric"
               placeholder="{{ $discountType === 'percent' ? 'e.g. 10' : '0' }}">
    </div>
    @if($previewCents > 0)
        <div class="flex justify-between text-tebo-green text-sm">
            <span>
                @if($discountType === 'percent' && $discountValue > 0)
                    Discount ({{ $discountValue }}%)
                @else
                    Discount
                @endif
            </span>
            <span>-{{ money($previewCents) }}</span>
        </div>
    @endif
</div>

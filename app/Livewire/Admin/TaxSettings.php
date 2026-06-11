<?php

namespace App\Livewire\Admin;

use App\Support\RestaurantProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Tax')]
class TaxSettings extends Component
{
    public bool $taxEnabled = true;

    public string $taxLabel = 'VAT';

    public string $taxId = '';

    public string $taxRatePercent = '16';

    public function mount(): void
    {
        $this->taxEnabled = RestaurantProfile::isTaxEnabled();
        $this->taxLabel = RestaurantProfile::taxLabel();
        $this->taxId = (string) RestaurantProfile::get('tax_id');
        $this->taxRatePercent = (string) round(RestaurantProfile::taxRateDecimal() * 100, 2);
    }

    public function save(): void
    {
        $this->validate([
            'taxEnabled' => 'boolean',
            'taxLabel' => 'required|min:2|max:40',
            'taxId' => 'nullable|max:60',
            'taxRatePercent' => 'required|numeric|min:0|max:100',
        ]);

        RestaurantProfile::update([
            'tax_enabled' => $this->taxEnabled ? '1' : '0',
            'tax_label' => $this->taxLabel,
            'tax_id' => $this->taxId,
            'tax_rate' => (string) round((float) $this->taxRatePercent / 100, 4),
        ]);

        $this->dispatch('toast', message: 'Tax settings saved!', type: 'success');
    }

    public function render()
    {
        $sampleSubtotal = 10000;
        $rate = $this->taxEnabled ? (float) $this->taxRatePercent / 100 : 0;
        $sampleTax = (int) round($sampleSubtotal * $rate);
        $sampleTotal = $sampleSubtotal + $sampleTax;

        return view('livewire.admin.tax-settings', [
            'sampleSubtotal' => $sampleSubtotal,
            'sampleTax' => $sampleTax,
            'sampleTotal' => $sampleTotal,
        ]);
    }
}

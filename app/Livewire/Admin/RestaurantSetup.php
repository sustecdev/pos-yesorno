<?php

namespace App\Livewire\Admin;

use App\Support\RestaurantProfile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.admin')]
#[Title('Restaurant')]
class RestaurantSetup extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $tagline = '';

    public string $location = '';

    public string $city = '';

    public string $phone = '';

    public string $email = '';

    public $logo;

    public ?string $currentLogoUrl = null;

    public function mount(): void
    {
        $profile = RestaurantProfile::all();

        $this->name = $profile['name'];
        $this->tagline = $profile['tagline'];
        $this->location = $profile['location'];
        $this->city = $profile['city'];
        $this->phone = $profile['phone'];
        $this->email = $profile['email'];
        $this->currentLogoUrl = RestaurantProfile::logoUrl();
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|min:2|max:120',
            'tagline' => 'nullable|max:160',
            'location' => 'nullable|max:200',
            'city' => 'nullable|max:100',
            'phone' => 'nullable|max:40',
            'email' => 'nullable|email|max:120',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($this->logo) {
            $oldPath = RestaurantProfile::get('logo_path');
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $logoPath = $this->logo->store('restaurant', 'public');
            RestaurantProfile::update(['logo_path' => $logoPath]);
            $this->currentLogoUrl = RestaurantProfile::logoUrl();
            $this->logo = null;
        }

        RestaurantProfile::update([
            'name' => $this->name,
            'tagline' => $this->tagline,
            'location' => $this->location,
            'city' => $this->city,
            'phone' => $this->phone,
            'email' => $this->email,
        ]);

        $this->dispatch('toast', message: 'Restaurant settings saved!', type: 'success');
    }

    public function removeLogo(): void
    {
        $path = RestaurantProfile::get('logo_path');

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        RestaurantProfile::update(['logo_path' => '']);
        $this->currentLogoUrl = null;
        $this->dispatch('toast', message: 'Logo removed.', type: 'success');
    }

    public function render()
    {
        $preview = RestaurantProfile::forReceipt();
        $preview['logo_url'] = RestaurantProfile::logoUrl();

        return view('livewire.admin.restaurant-setup', [
            'preview' => $preview,
        ]);
    }
}

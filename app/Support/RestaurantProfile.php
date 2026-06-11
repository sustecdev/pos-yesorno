<?php

namespace App\Support;

use App\Models\RestaurantSetting;
use Illuminate\Support\Facades\Storage;

class RestaurantProfile
{
    public const KEYS = [
        'name',
        'tagline',
        'location',
        'city',
        'phone',
        'email',
        'tax_id',
        'logo_path',
        'tax_rate',
        'tax_enabled',
        'tax_label',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $defaults = [
            'name' => config('teboos.name'),
            'tagline' => '',
            'location' => '',
            'city' => '',
            'phone' => '',
            'email' => '',
            'tax_id' => '',
            'logo_path' => '',
            'tax_rate' => (string) config('teboos.tax_rate', 0.16),
            'tax_enabled' => '1',
            'tax_label' => 'VAT',
        ];

        return RestaurantSetting::get($key, $defaults[$key] ?? $default);
    }

    public static function all(): array
    {
        $profile = [];
        foreach (self::KEYS as $key) {
            $profile[$key] = self::get($key);
        }

        return $profile;
    }

    public static function update(array $data): void
    {
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $data)) {
                RestaurantSetting::set($key, (string) $data[$key]);
            }
        }
    }

    public static function logoUrl(): ?string
    {
        $path = self::get('logo_path');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public static function logoAbsolutePath(): ?string
    {
        $path = self::get('logo_path');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }

    public static function isTaxEnabled(): bool
    {
        return filter_var(self::get('tax_enabled', '1'), FILTER_VALIDATE_BOOLEAN);
    }

    public static function taxLabel(): string
    {
        $label = trim((string) self::get('tax_label', 'VAT'));

        return $label !== '' ? $label : 'Tax';
    }

    public static function taxRateDecimal(): float
    {
        if (! self::isTaxEnabled()) {
            return 0.0;
        }

        return (float) self::get('tax_rate', config('teboos.tax_rate', 0.16));
    }

    public static function forReceipt(): array
    {
        $profile = self::all();

        return [
            'name' => $profile['name'],
            'tagline' => $profile['tagline'],
            'location' => $profile['location'],
            'city' => $profile['city'],
            'phone' => $profile['phone'],
            'email' => $profile['email'],
            'tax_id' => $profile['tax_id'],
            'logo_path' => self::logoAbsolutePath(),
            'address_line' => trim(implode(', ', array_filter([
                $profile['location'],
                $profile['city'],
            ]))),
        ];
    }
}

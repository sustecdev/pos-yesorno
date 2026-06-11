@props(['profile', 'compact' => false, 'light' => false])

@php
    $address = $profile['address_line'] ?? trim(implode(', ', array_filter([$profile['location'] ?? '', $profile['city'] ?? ''])));
    $muted = $light ? 'text-gray-500' : 'text-tebo-cream/50';
    $mutedSm = $light ? 'text-gray-400' : 'text-tebo-cream/40';
@endphp

<div {{ $attributes->merge(['class' => 'text-center']) }}>
    @if(!empty($profile['logo_url']))
        <img src="{{ $profile['logo_url'] }}" alt="{{ $profile['name'] ?? 'Restaurant' }}" class="mx-auto mb-2 {{ $compact ? 'max-h-12' : 'max-h-[72px]' }} max-w-[200px] object-contain">
    @elseif(!empty($profile['logo_path']) && file_exists($profile['logo_path']))
        <img src="{{ $profile['logo_path'] }}" alt="{{ $profile['name'] ?? 'Restaurant' }}" style="max-height: {{ $compact ? '48px' : '72px' }}; max-width: 200px; margin: 0 auto 8px; display: block;">
    @endif

    <h2 class="{{ $compact ? 'text-lg' : 'text-xl' }} font-display font-bold {{ $light ? 'text-gray-900' : '' }}">{{ $profile['name'] ?? 'Restaurant' }}</h2>

    @if(!empty($profile['tagline']))
        <p class="text-sm {{ $muted }} mt-0.5">{{ $profile['tagline'] }}</p>
    @endif

    @if($address)
        <p class="text-sm {{ $muted }} mt-1">{{ $address }}</p>
    @endif

    @if(!empty($profile['phone']) || !empty($profile['email']))
        <p class="text-xs {{ $mutedSm }} mt-1">
            @if(!empty($profile['phone'])){{ $profile['phone'] }}@endif
            @if(!empty($profile['phone']) && !empty($profile['email'])) · @endif
            @if(!empty($profile['email'])){{ $profile['email'] }}@endif
        </p>
    @endif

    @if(!empty($profile['tax_id']))
        <p class="text-xs {{ $mutedSm }} mt-0.5">TPIN: {{ $profile['tax_id'] }}</p>
    @endif
</div>

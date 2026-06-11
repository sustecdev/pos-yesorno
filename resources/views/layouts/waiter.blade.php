@php
    $navActive = request()->routeIs('waiter.order') ? 'order' : (request()->get('filter') === 'active' ? 'active' : 'floor');
@endphp

<!DOCTYPE html>
<html lang="en" class="tablet-ui">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#080A0E">
    <meta name="restaurant-name" content="{{ $restaurantName }}">
    <title>{{ app_page_title($title ?? 'Waiter') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:400,600,700|dm-sans:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-dvh bg-tebo-dark text-tebo-cream font-sans antialiased waiter-body {{ request()->routeIs('waiter.order') ? 'waiter-body-order' : 'waiter-body-floor' }}"
      x-data="teboApp()"
      x-on:tebo-toast.window="showToast($event.detail)">

    @unless(request()->routeIs('waiter.order'))
        <header class="waiter-header sticky top-0 z-50 bg-tebo-darker/95 backdrop-blur border-b border-tebo-border">
            <div class="flex items-center justify-between px-4 py-3 gap-3" style="padding-top: max(0.75rem, env(safe-area-inset-top))">
                <a href="{{ route('waiter.floor') }}" class="flex items-center gap-2 shrink-0">
                    <span class="font-display text-xl font-bold text-tebo-amber truncate max-w-[10rem]">{{ $restaurantName }}</span>
                </a>
                <span class="text-sm text-tebo-cream/50 truncate">{{ auth()->user()->name }}</span>
            </div>
        </header>
    @endunless

    <main class="waiter-main {{ request()->routeIs('waiter.order') ? 'waiter-main-order' : '' }}">
        {{ $slot }}
    </main>

    @unless(request()->routeIs('waiter.order'))
        <x-waiter.bottom-nav :active="$navActive" />
    @endunless

    <div x-show="toast.show" x-transition
         class="fixed z-[100] px-6 py-4 rounded-2xl shadow-2xl font-medium text-base max-w-sm
                bottom-28 left-1/2 -translate-x-1/2 md:bottom-8 md:left-auto md:translate-x-0 md:right-6"
         :class="{
            'bg-tebo-green text-tebo-dark': toast.type === 'success',
            'bg-tebo-red text-white': toast.type === 'error',
            'bg-tebo-amber text-tebo-dark': toast.type === 'warning',
            'bg-tebo-surface border border-tebo-border': toast.type === 'info'
         }"
         x-text="toast.message"></div>

    <div wire:loading.flex class="fixed inset-0 z-[90] bg-black/20 backdrop-blur-[1px] items-center justify-center">
        <div class="w-14 h-14 border-4 border-tebo-amber/30 border-t-tebo-amber rounded-full animate-spin bg-tebo-darker/80"></div>
    </div>

    @livewireScripts
    <script>
        function teboApp() {
            return {
                toast: { show: false, message: '', type: 'info' },
                showToast({ message, type }) {
                    this.toast = { show: true, message, type: type || 'info' };
                    setTimeout(() => this.toast.show = false, 3000);
                }
            }
        }
    </script>
</body>
</html>

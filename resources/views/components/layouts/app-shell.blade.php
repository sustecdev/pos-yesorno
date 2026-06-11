@props(['title' => null, 'nav' => null, 'tablet' => false, 'fullscreen' => false])

<!DOCTYPE html>
<html lang="en" class="{{ $tablet ? 'tablet-ui' : '' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#080A0E">
    <meta name="restaurant-name" content="{{ $restaurantName }}">
    <title>{{ app_page_title($title) }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:400,600,700|dm-sans:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body {{ $attributes->merge(['class' => ($fullscreen ? 'h-dvh overflow-hidden ' : 'min-h-dvh ') . ($tablet ? 'tablet-safe-top ' : '')]) }}
      x-data="teboApp()"
      x-on:tebo-toast.window="showToast($event.detail)">
    @if($nav)
        <header class="sticky top-0 z-50 bg-tebo-darker/95 backdrop-blur border-b border-tebo-border tablet-safe-top">
            <div class="{{ $fullscreen ? 'px-4' : 'max-w-7xl mx-auto px-4' }} py-3 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="font-display text-xl md:text-2xl font-bold text-tebo-amber shrink-0 truncate max-w-[12rem] md:max-w-xs">{{ $restaurantName }}</span>
                    <span class="text-tebo-cream/50 text-sm md:text-base truncate">{{ $nav }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <x-workspace.switcher />
                    <span class="text-sm md:text-base text-tebo-cream/60 hidden sm:inline truncate max-w-[8rem]">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="tebo-touch px-4 py-2 rounded-xl text-sm font-medium bg-tebo-surface border border-tebo-border hover:border-tebo-amber/50">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>
    @endif

    <main class="{{ $fullscreen ? 'h-[calc(100dvh-57px)] overflow-hidden' : ($tablet ? 'tablet-shell-main' : '') }}">{{ $slot }}</main>

    <div x-show="toast.show"
         x-transition
         class="fixed z-[100] px-6 py-4 rounded-2xl shadow-2xl font-medium text-base max-w-sm
                bottom-24 md:bottom-8 left-1/2 -translate-x-1/2 md:left-auto md:translate-x-0 md:right-6"
         :class="{
            'bg-tebo-green text-tebo-dark': toast.type === 'success',
            'bg-tebo-red text-white': toast.type === 'error',
            'bg-tebo-amber text-tebo-dark': toast.type === 'warning',
            'bg-tebo-surface border border-tebo-border text-tebo-cream': toast.type === 'info'
         }"
         x-text="toast.message"></div>

    <div wire:loading.flex class="fixed inset-0 z-[90] bg-black/30 backdrop-blur-[2px] items-center justify-center pointer-events-none">
        <div class="w-12 h-12 border-4 border-tebo-amber/30 border-t-tebo-amber rounded-full animate-spin"></div>
    </div>

    @livewireScripts
    <script>
        function teboApp() {
            return {
                toast: { show: false, message: '', type: 'info' },
                showToast({ message, type }) {
                    this.toast = { show: true, message, type: type || 'info' };
                    setTimeout(() => this.toast.show = false, 3500);
                }
            }
        }
    </script>
</body>
</html>

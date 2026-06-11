<!DOCTYPE html>
<html lang="en" class="tablet-ui">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#080A0E">
    <meta name="restaurant-name" content="{{ $restaurantName }}">
    <title>{{ app_page_title($title ?? 'Kitchen') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=syne:400,600,700|dm-sans:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body id="kds-screen" class="kitchen-body min-h-dvh bg-tebo-darker text-tebo-cream font-sans antialiased"
      x-data="kitchenApp()"
      x-on:tebo-toast.window="showToast($event.detail)"
      x-init="startClock()">

    <main class="kitchen-main">
        {{ $slot }}
    </main>

    <div x-show="toast.show" x-transition
         class="fixed z-[100] px-6 py-4 rounded-2xl shadow-2xl font-bold text-lg max-w-md top-20 left-1/2 -translate-x-1/2"
         :class="{
            'bg-tebo-green text-tebo-dark': toast.type === 'success',
            'bg-tebo-red text-white': toast.type === 'error',
            'bg-tebo-amber text-tebo-dark': toast.type === 'warning',
            'bg-tebo-surface border-2 border-tebo-border': toast.type === 'info'
         }"
         x-text="toast.message"></div>

    <div wire:loading.flex class="fixed inset-0 z-[90] bg-black/40 items-center justify-center">
        <div class="w-16 h-16 border-4 border-tebo-amber/30 border-t-tebo-amber rounded-full animate-spin"></div>
    </div>

    @livewireScripts
    <script>
        function kitchenApp() {
            return {
                time: '',
                toast: { show: false, message: '', type: 'info' },
                startClock() {
                    const tick = () => {
                        this.time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    };
                    tick();
                    setInterval(tick, 10000);
                },
                showToast({ message, type }) {
                    this.toast = { show: true, message, type: type || 'info' };
                    setTimeout(() => this.toast.show = false, 3000);
                }
            }
        }
    </script>
</body>
</html>

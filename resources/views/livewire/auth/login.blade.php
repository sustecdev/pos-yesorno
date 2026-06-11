<div class="w-full max-w-lg p-6 md:p-10">
    <div class="text-center mb-10">
        <h1 class="font-display text-3xl md:text-5xl font-bold text-tebo-amber leading-tight">{{ $restaurantName }}</h1>
        <p class="text-tebo-cream/50 mt-3 text-lg">Restaurant Point of Sale</p>
    </div>

    <form wire:submit="login" class="tebo-card p-8 md:p-10 space-y-6">
        <div>
            <label class="block text-base text-tebo-cream/60 mb-2">Email</label>
            <input type="email" wire:model="email" class="tebo-input text-lg py-4" placeholder="your@email.com" autofocus inputmode="email" autocomplete="username">
            @error('email') <p class="text-tebo-red text-sm mt-2">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-base text-tebo-cream/60 mb-2">Password</label>
            <input type="password" wire:model="password" class="tebo-input text-lg py-4" placeholder="••••••••" autocomplete="current-password">
        </div>
        <label class="flex items-center gap-3 text-base text-tebo-cream/60 tebo-touch">
            <input type="checkbox" wire:model="remember" class="w-5 h-5 rounded border-tebo-border">
            Remember me
        </label>
        <button type="submit" class="tebo-touch-lg tebo-btn-primary w-full text-xl font-bold py-5">Sign In</button>
    </form>
</div>

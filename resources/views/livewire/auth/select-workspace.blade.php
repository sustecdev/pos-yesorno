<div class="w-full max-w-2xl p-6 md:p-10">
    <div class="text-center mb-10">
        <h1 class="font-display text-4xl md:text-5xl font-bold text-tebo-amber">Choose workspace</h1>
        <p class="text-tebo-cream/50 mt-3 text-lg">Signed in as {{ auth()->user()->name }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        @foreach($roles as $key => $role)
            <button type="button"
                    wire:click="select('{{ $key }}')"
                    class="tebo-card tebo-touch text-left p-6 hover:border-tebo-amber/50 active:scale-[0.98] transition-transform">
                <div class="font-display text-xl font-bold text-tebo-amber">{{ $role['label'] }}</div>
                <p class="text-sm text-tebo-cream/50 mt-2">{{ $role['description'] }}</p>
            </button>
        @endforeach
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-8 text-center">
        @csrf
        <button type="submit" class="text-sm text-tebo-cream/40 hover:text-tebo-cream">Sign out</button>
    </form>
</div>

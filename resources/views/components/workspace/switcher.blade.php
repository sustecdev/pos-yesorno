@php
    use App\Support\WorkspaceRoles;

    $current = WorkspaceRoles::current() ?? 'admin';
@endphp

@if(auth()->user()?->canSwitchWorkspace())
    <div x-data="{ open: false }" class="relative">
        <button type="button"
                @click="open = !open"
                class="tebo-touch px-3 py-2 rounded-xl text-sm font-medium bg-tebo-surface border border-tebo-border hover:border-tebo-amber/50 flex items-center gap-2">
            <span class="hidden sm:inline">{{ WorkspaceRoles::label($current) }}</span>
            <span class="sm:hidden">Role</span>
            <svg class="w-4 h-4 text-tebo-cream/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open"
             @click.outside="open = false"
             x-transition
             class="absolute right-0 mt-2 w-48 rounded-xl bg-tebo-darker border border-tebo-border shadow-xl z-50 py-1">
            @foreach(WorkspaceRoles::all() as $key => $role)
                <a href="{{ route($role['route']) }}"
                   class="block px-4 py-2.5 text-sm hover:bg-tebo-surface {{ $current === $key ? 'text-tebo-amber' : 'text-tebo-cream/80' }}">
                    {{ $role['label'] }}
                </a>
            @endforeach
            <hr class="border-tebo-border my-1">
            <a href="{{ route('workspace.select') }}" class="block px-4 py-2.5 text-sm text-tebo-cream/50 hover:bg-tebo-surface">
                Switch workspace
            </a>
        </div>
    </div>
@endif

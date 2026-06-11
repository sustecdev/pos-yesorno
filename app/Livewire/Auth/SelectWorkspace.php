<?php

namespace App\Livewire\Auth;

use App\Support\WorkspaceRoles;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Choose workspace')]
class SelectWorkspace extends Component
{
    public function mount(): void
    {
        if (! auth()->user()?->canSwitchWorkspace()) {
            $this->redirect(auth()->user()->dashboardRoute());
        }
    }

    public function select(string $role): void
    {
        if (! WorkspaceRoles::isValid($role)) {
            return;
        }

        WorkspaceRoles::set($role);

        $this->redirect(WorkspaceRoles::route($role));
    }

    public function render()
    {
        return view('livewire.auth.select-workspace', [
            'roles' => WorkspaceRoles::all(),
        ]);
    }
}

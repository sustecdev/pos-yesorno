<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Support\WorkspaceRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'pin'])]
#[Hidden(['password', 'remember_token', 'pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'waiter_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function canSwitchWorkspace(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('manager');
    }

    public function dashboardRoute(): string
    {
        if ($this->canSwitchWorkspace()) {
            $workspace = WorkspaceRoles::current();

            return $workspace
                ? WorkspaceRoles::route($workspace)
                : route('workspace.select');
        }

        return match (true) {
            $this->hasRole('waiter') => route('waiter.floor'),
            $this->hasRole('kitchen') => route('kitchen.kds'),
            $this->hasRole('cashier') => route('cashier.terminal'),
            $this->hasRole('host') => route('host.reservations'),
            default => route('login'),
        };
    }
}

<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.admin')]
#[Title('Staff')]
class StaffManager extends Component
{
    use WithPagination;

    public string $filterRole = 'all';

    public bool $showForm = false;

    public ?int $viewingOrdersForId = null;

    public string $orderFilter = 'all';

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'waiter';

    public function mount(): void
    {
        $this->ensureRolesExist();
    }

    public function openForm(): void
    {
        $this->closeOrders();
        $this->resetForm();
        $this->showForm = true;
    }

    public function viewOrders(int $id): void
    {
        $this->resetForm();
        $this->viewingOrdersForId = $id;
        $this->orderFilter = 'all';
        $this->resetPage('ordersPage');
    }

    public function closeOrders(): void
    {
        $this->viewingOrdersForId = null;
        $this->orderFilter = 'all';
    }

    public function updatedOrderFilter(): void
    {
        $this->resetPage('ordersPage');
    }

    public function edit(int $id): void
    {
        $this->closeOrders();
        $user = User::query()->with('roles')->findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'waiter';
        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|min:2|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::in($this->assignableRoles())],
        ];

        if ($this->editingId) {
            $rules['password'] = 'nullable|min:8';
        } else {
            $rules['password'] = 'required|min:8';
        }

        $this->validate($rules);

        if ($this->editingId) {
            $user = User::query()->findOrFail($this->editingId);
            $user->update([
                'name' => $this->name,
                'email' => $this->email,
                ...($this->password ? ['password' => $this->password] : []),
            ]);
            $user->syncRoles([$this->role]);
            $message = 'Staff member updated.';
        } else {
            $user = User::query()->create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => $this->password,
            ]);
            $user->assignRole($this->role);
            $message = 'Staff member added.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'waiter';
        $this->resetValidation();
    }

    protected function assignableRoles(): array
    {
        return ['admin', 'manager', 'waiter', 'kitchen', 'cashier', 'host'];
    }

    protected function ensureRolesExist(): void
    {
        foreach ($this->assignableRoles() as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function render()
    {
        $staff = User::query()
            ->with('roles')
            ->withCount('orders')
            ->when($this->filterRole !== 'all', fn ($q) => $q->role($this->filterRole))
            ->orderBy('name')
            ->get();

        $viewingUser = null;
        $orders = null;
        $orderStats = null;

        if ($this->viewingOrdersForId) {
            $viewingUser = User::query()->with('roles')->findOrFail($this->viewingOrdersForId);

            $baseQuery = Order::query()->where('waiter_id', $viewingUser->id);

            $orderStats = [
                'total' => (clone $baseQuery)->count(),
                'open' => (clone $baseQuery)->whereNotIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::Closed,
                    OrderStatus::Cancelled,
                ])->count(),
                'revenue' => (clone $baseQuery)->whereIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::Closed,
                ])->sum('total_cents'),
            ];

            $orders = (clone $baseQuery)
                ->with('table')
                ->withCount(['items as items_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
                ->when($this->orderFilter === 'open', fn ($q) => $q->whereNotIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::Closed,
                    OrderStatus::Cancelled,
                ]))
                ->when($this->orderFilter === 'paid', fn ($q) => $q->whereIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::Closed,
                ]))
                ->latest()
                ->paginate(15, pageName: 'ordersPage');
        }

        return view('livewire.admin.staff-manager', [
            'staff' => $staff,
            'roles' => $this->assignableRoles(),
            'currentUserId' => Auth::id(),
            'viewingUser' => $viewingUser,
            'orders' => $orders,
            'orderStats' => $orderStats,
        ]);
    }
}

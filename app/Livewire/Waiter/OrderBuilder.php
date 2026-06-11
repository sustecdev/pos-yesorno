<?php

namespace App\Livewire\Waiter;

use App\Enums\OrderStatus;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\DiscountCalculator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.waiter')]
#[Title('Order')]
class OrderBuilder extends Component
{
    public DiningTable $table;

    public Order $order;

    public ?int $selectedCategoryId = null;

    public string $search = '';

    public ?int $selectedMenuItemId = null;

    public int $quantity = 1;

    public array $selectedModifiers = [];

    public string $instructions = '';

    public bool $hasAllergy = false;

    public string $allergyNote = '';

    public string $activePanel = 'menu';

    public bool $showBillPanel = false;

    public string $discountType = DiscountCalculator::TYPE_FLAT;

    public int $discountValue = 0;

    public function mount(DiningTable $table, OrderService $orderService): void
    {
        $this->table = $table->load('area');
        $this->order = $orderService->getOrCreateActiveOrder($table, Auth::user())->load('items.modifiers');
        $this->selectedCategoryId = MenuCategory::query()->where('is_active', true)->value('id');
        $this->loadDiscountFromOrder();
    }

    public function updatedDiscountValue(mixed $value): void
    {
        $this->discountValue = max(0, (int) ($value ?? 0));

        if ($this->discountType === DiscountCalculator::TYPE_PERCENT) {
            $this->discountValue = min(100, $this->discountValue);
        }
    }

    protected function loadDiscountFromOrder(): void
    {
        $this->discountType = $this->order->discount_type ?? DiscountCalculator::TYPE_FLAT;
        $this->discountValue = (int) ($this->order->discount_value ?? $this->order->discount_cents ?? 0);

        if ($this->discountType === DiscountCalculator::TYPE_FLAT && ! $this->order->discount_value && $this->order->discount_cents) {
            $this->discountValue = (int) $this->order->discount_cents;
        }
    }

    public function selectItem(int $menuItemId): void
    {
        $this->selectedMenuItemId = $menuItemId;
        $this->selectedModifiers = [];
        $this->quantity = 1;
        $this->instructions = '';
        $this->hasAllergy = false;
        $this->allergyNote = '';
    }

    public function quickAddOrConfigure(int $menuItemId, OrderService $orderService): void
    {
        $menuItem = MenuItem::query()->with('modifierGroups')->findOrFail($menuItemId);

        if ($menuItem->modifierGroups->isEmpty()) {
            $orderService->addItem($this->order, $menuItem, 1);
            $this->order->refresh()->load('items.modifiers');
            $this->dispatch('toast', message: "Added {$menuItem->name}", type: 'success');

            return;
        }

        $this->selectItem($menuItemId);
    }

    public function closeItemPanel(): void
    {
        $this->selectedMenuItemId = null;
    }

    public function showCart(): void
    {
        $this->activePanel = 'cart';
    }

    public function showMenu(): void
    {
        $this->activePanel = 'menu';
    }

    public function addToOrder(OrderService $orderService): void
    {
        if (! $this->selectedMenuItemId) {
            return;
        }

        $menuItem = MenuItem::query()->findOrFail($this->selectedMenuItemId);

        $orderService->addItem(
            $this->order,
            $menuItem,
            $this->quantity,
            $this->selectedModifiers,
            $this->instructions ?: null,
            $this->hasAllergy,
            $this->allergyNote ?: null,
        );

        $this->order->refresh()->load('items.modifiers');
        $this->selectedMenuItemId = null;
        $this->activePanel = 'cart';
        $this->dispatch('order-updated');
    }

    public function removeItem(int $itemId, OrderService $orderService): void
    {
        if (! $this->order->canRemoveItems()) {
            $this->dispatch('toast', message: 'Cannot modify a paid or closed order.', type: 'error');

            return;
        }

        $item = $this->order->items()
            ->where('status', '!=', 'cancelled')
            ->findOrFail($itemId);

        $wasSent = $item->sent_at !== null;

        $orderService->cancelItem($item);
        $this->order->refresh()->load('items.modifiers');

        $this->dispatch('toast', message: $wasSent
            ? 'Item removed — kitchen notified.'
            : 'Item removed.', type: 'success');
    }

    public function sendToKitchen(OrderService $orderService): void
    {
        if ($this->order->items()->where('status', '!=', 'cancelled')->count() === 0) {
            $this->dispatch('toast', message: 'Add items before sending.', type: 'error');

            return;
        }

        $orderService->sendToKitchen($this->order);
        $this->order->refresh()->load('items.modifiers');
        $this->dispatch('toast', message: 'Order sent to kitchen!', type: 'success');
    }

    public function markRush(OrderService $orderService): void
    {
        $orderService->markRush($this->order);
        $this->order->refresh();
        $this->dispatch('toast', message: 'Marked as RUSH!', type: 'warning');
    }

    public function fireCourse(OrderService $orderService): void
    {
        $orderService->fireCourse($this->order);
        $this->order->refresh()->load('items.modifiers');
        $this->dispatch('toast', message: 'Next course fired!', type: 'success');
    }

    public function openBillPanel(): void
    {
        if ($this->order->status === OrderStatus::Draft) {
            $this->dispatch('toast', message: 'Send to kitchen first.', type: 'error');

            return;
        }

        if ($this->order->items()->where('status', '!=', 'cancelled')->count() === 0) {
            $this->dispatch('toast', message: 'Add items before requesting bill.', type: 'error');

            return;
        }

        $this->loadDiscountFromOrder();
        $this->showBillPanel = true;
        $this->activePanel = 'cart';
    }

    public function closeBillPanel(): void
    {
        $this->showBillPanel = false;
    }

    public function sendToCashier(OrderService $orderService, PaymentService $payments): void
    {
        if ($this->order->status === OrderStatus::Draft) {
            $this->dispatch('toast', message: 'Send to kitchen first.', type: 'error');

            return;
        }

        if ($this->order->items()->where('status', '!=', 'cancelled')->count() === 0) {
            $this->dispatch('toast', message: 'Add items before requesting bill.', type: 'error');

            return;
        }

        if ($this->discountValue < 0) {
            $this->dispatch('toast', message: 'Invalid discount amount.', type: 'error');

            return;
        }

        if ($this->discountType === DiscountCalculator::TYPE_PERCENT && $this->discountValue > 100) {
            $this->dispatch('toast', message: 'Percent discount cannot exceed 100%.', type: 'error');

            return;
        }

        $payments->applyDiscount($this->order, $this->discountType, $this->discountValue);
        $orderService->requestBill($this->order);
        $this->order->refresh()->load('items.modifiers');
        $this->showBillPanel = false;

        if (Auth::user()->hasAnyRole(['cashier', 'admin', 'manager'])) {
            $this->redirect(route('cashier.terminal', ['orderId' => $this->order->id]));

            return;
        }

        $this->dispatch('toast', message: 'Bill sent to cashier!', type: 'success');
    }

    public function render()
    {
        $categories = MenuCategory::query()->where('is_active', true)->orderBy('sort_order')->get();
        $menuItems = MenuItem::query()
            ->with('modifierGroups.modifiers')
            ->where('is_active', true)
            ->where('is_available', true)
            ->when($this->selectedCategoryId, fn ($q) => $q->where('menu_category_id', $this->selectedCategoryId))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('sort_order')
            ->get();

        $selectedItem = $this->selectedMenuItemId
            ? MenuItem::with('modifierGroups.modifiers')->find($this->selectedMenuItemId)
            : null;

        $previewDiscountCents = DiscountCalculator::calculateCents(
            $this->order,
            $this->discountType,
            $this->discountValue,
        );

        return view('livewire.waiter.order-builder', compact(
            'categories',
            'menuItems',
            'selectedItem',
            'previewDiscountCents',
        ));
    }
}

<?php

namespace App\Livewire\Cashier;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\PaymentService;
use App\Support\DiscountCalculator;
use App\Support\RestaurantProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.cashier')]
#[Title('Cashier')]
class PaymentTerminal extends Component
{
    public ?int $orderId = null;

    public string $search = '';

    public string $listTab = 'pending';

    public string $discountType = DiscountCalculator::TYPE_FLAT;

    public int $discountValue = 0;

    public string $paymentMethod = 'cash';

    public int $tipCents = 0;

    public function mount($orderId = null): void
    {
        $this->orderId = $orderId !== null ? (int) $orderId : null;
    }

    public function selectOrder(int $id): void
    {
        $order = Order::query()->find($id);
        $this->orderId = $id;
        $this->discountType = $order?->discount_type ?? DiscountCalculator::TYPE_FLAT;
        $this->discountValue = (int) ($order?->discount_value ?? $order?->discount_cents ?? 0);

        if ($this->discountType === DiscountCalculator::TYPE_FLAT && $order && ! $order->discount_value && $order->discount_cents) {
            $this->discountValue = (int) $order->discount_cents;
        }
    }

    public function setListTab(string $tab): void
    {
        if (! in_array($tab, ['pending', 'recent'], true)) {
            return;
        }

        $this->listTab = $tab;
        $this->orderId = null;
        $this->discountType = DiscountCalculator::TYPE_FLAT;
        $this->discountValue = 0;
        $this->tipCents = 0;
    }

    public function updatedDiscountValue(mixed $value): void
    {
        $this->discountValue = max(0, (int) ($value ?? 0));

        if ($this->discountType === DiscountCalculator::TYPE_PERCENT) {
            $this->discountValue = min(100, $this->discountValue);
        }
    }

    public function applyDiscount(PaymentService $payments): void
    {
        $order = $this->resolveOrder();

        if (! $order) {
            return;
        }

        $payments->applyDiscount($order, $this->discountType, $this->discountValue);
        $this->orderId = $order->id;
        $this->dispatch('toast', message: 'Discount applied.', type: 'success');
    }

    public function processPayment(PaymentService $payments): void
    {
        $order = $this->resolveOrder();

        if (! $order) {
            $this->dispatch('toast', message: 'No order selected.', type: 'error');

            return;
        }

        if ($order->total_cents <= 0) {
            $this->dispatch('toast', message: 'Invalid payment amount.', type: 'error');

            return;
        }

        $payments->recordPayment(
            $order,
            PaymentMethod::from($this->paymentMethod),
            $order->total_cents,
            Auth::user(),
            $this->tipCents,
        );

        $this->dispatch('toast', message: 'Payment complete!', type: 'success');
        $this->orderId = null;
        $this->discountType = DiscountCalculator::TYPE_FLAT;
        $this->discountValue = 0;
        $this->tipCents = 0;
    }

    protected function resolveOrder(): ?Order
    {
        if (! $this->orderId) {
            return null;
        }

        return Order::with(['table', 'waiter', 'items.modifiers', 'payments', 'receipts'])
            ->find($this->orderId);
    }

    protected function pendingOrdersQuery()
    {
        return Order::query()
            ->with('table')
            ->whereIn('status', [
                OrderStatus::Ready,
                OrderStatus::Served,
                OrderStatus::Sent,
                OrderStatus::Preparing,
            ])
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->orderByRaw("CASE WHEN status = 'served' THEN 0 ELSE 1 END")
            ->latest();
    }

    protected function recentOrdersQuery()
    {
        return Order::query()
            ->with('table')
            ->where('status', OrderStatus::Paid)
            ->where('paid_at', '>=', now()->startOfDay())
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->latest('paid_at');
    }

    public function render()
    {
        $order = $this->resolveOrder();

        $pendingOrders = $this->pendingOrdersQuery()->limit(20)->get();
        $recentOrders = $this->recentOrdersQuery()->limit(20)->get();

        $restaurant = RestaurantProfile::forReceipt();
        $restaurant['logo_url'] = RestaurantProfile::logoUrl();

        $previewDiscountCents = $order
            ? DiscountCalculator::calculateCents($order, $this->discountType, $this->discountValue)
            : 0;

        return view('livewire.cashier.payment-terminal', [
            'pendingOrders' => $pendingOrders,
            'recentOrders' => $recentOrders,
            'order' => $order,
            'restaurant' => $restaurant,
            'previewDiscountCents' => $previewDiscountCents,
        ]);
    }
}

<?php

namespace App\Services;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Events\Kitchen\TableStatusChanged;
use App\Models\ActivityLog;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\RestaurantProfile;
use App\Models\User;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        protected KitchenNotificationService $notifications,
        protected InventoryService $inventory,
    ) {}

    public function createForTable(DiningTable $table, User $waiter): Order
    {
        $order = Order::query()->create([
            'order_number' => $this->generateOrderNumber(),
            'dining_table_id' => $table->id,
            'waiter_id' => $waiter->id,
            'status' => OrderStatus::Draft,
            'course_number' => 1,
        ]);

        $table->update(['status' => TableStatus::Occupied]);
        broadcast(new TableStatusChanged($table->fresh()));

        return $order;
    }

    public function getOrCreateActiveOrder(DiningTable $table, User $waiter): Order
    {
        $existing = $table->activeOrder;

        if ($existing) {
            return $existing;
        }

        return $this->createForTable($table, $waiter);
    }

    public function addItem(Order $order, MenuItem $menuItem, int $quantity = 1, array $modifierIds = [], ?string $instructions = null, bool $hasAllergy = false, ?string $allergyNote = null): OrderItem
    {
        $modifiers = $menuItem->modifierGroups()
            ->with('modifiers')
            ->get()
            ->flatMap->modifiers
            ->whereIn('id', $modifierIds);

        $modifierTotal = $modifiers->sum('price_adjustment_cents');
        $unitPrice = $menuItem->price_cents + $modifierTotal;

        $item = $order->items()->create([
            'menu_item_id' => $menuItem->id,
            'kitchen_station_id' => $menuItem->kitchen_station_id,
            'name' => $menuItem->name,
            'quantity' => $quantity,
            'unit_price_cents' => $unitPrice,
            'total_cents' => $unitPrice * $quantity,
            'status' => OrderItemStatus::Queued,
            'course_number' => $order->course_number,
            'has_allergy' => $hasAllergy,
            'allergy_note' => $allergyNote,
            'special_instructions' => $instructions,
        ]);

        foreach ($modifiers as $modifier) {
            $item->modifiers()->create([
                'name' => $modifier->name,
                'price_adjustment_cents' => $modifier->price_adjustment_cents,
            ]);
        }

        $order->recalculateTotals(RestaurantProfile::taxRateDecimal());

        if ($hasAllergy && $order->status !== OrderStatus::Draft) {
            $this->notifications->notifyAllergy($item);
        }

        return $item;
    }

    public function sendToKitchen(Order $order): Order
    {
        $order->update([
            'status' => OrderStatus::Sent,
            'sent_to_kitchen_at' => now(),
        ]);

        $order->items()->whereNull('sent_at')->update([
            'sent_at' => now(),
            'status' => OrderItemStatus::Queued,
        ]);

        $this->inventory->deductForOrder($order);
        $this->notifications->notifyNewTicket($order->fresh(['items', 'table', 'waiter']));

        if ($order->table) {
            broadcast(new TableStatusChanged($order->table->fresh()));
        }

        ActivityLog::query()->create([
            'user_id' => $order->waiter_id,
            'action' => 'order.sent_to_kitchen',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
        ]);

        return $order;
    }

    public function fireCourse(Order $order): Order
    {
        $order->increment('course_number');
        $order->update(['status' => OrderStatus::Sent]);

        $this->notifications->notifyFireCourse($order->fresh(['items', 'table', 'waiter']));

        return $order;
    }

    public function markRush(Order $order): Order
    {
        $order->update(['is_rush' => true]);
        $this->notifications->notifyRush($order->fresh(['items', 'table', 'waiter']));

        return $order;
    }

    public function requestBill(Order $order): Order
    {
        $order->update(['status' => OrderStatus::Served]);

        if ($order->table) {
            broadcast(new TableStatusChanged($order->table->fresh()));
        }

        ActivityLog::query()->create([
            'user_id' => $order->waiter_id,
            'action' => 'order.bill_requested',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
        ]);

        return $order->fresh();
    }

    public function updateItemStatus(OrderItem $item, OrderItemStatus $status): void
    {
        $updates = ['status' => $status];

        if ($status === OrderItemStatus::Preparing) {
            $updates['started_at'] = now();
        }

        if ($status === OrderItemStatus::Ready) {
            $updates['ready_at'] = now();
        }

        $item->update($updates);

        $order = $item->order;
        $activeItems = $order->items()->where('status', '!=', OrderItemStatus::Cancelled)->get();

        if ($activeItems->every(fn ($i) => in_array($i->status, [OrderItemStatus::Ready, OrderItemStatus::Served]))) {
            $order->update(['status' => OrderStatus::Ready, 'ready_at' => now()]);
        } elseif ($activeItems->contains(fn ($i) => $i->status === OrderItemStatus::Preparing)) {
            $order->update(['status' => OrderStatus::Preparing]);
        }
    }

    public function cancelItem(OrderItem $item): void
    {
        $item->update(['status' => OrderItemStatus::Cancelled]);
        $order = $item->order->fresh();
        $order->recalculateTotals(RestaurantProfile::taxRateDecimal());

        if ($item->sent_at && $order->status !== OrderStatus::Draft) {
            $this->notifications->notifyCancelled($order, $item);
        }

        $remaining = $order->items()->where('status', '!=', OrderItemStatus::Cancelled)->count();

        if ($remaining === 0 && $order->canRemoveItems()) {
            $order->update([
                'status' => OrderStatus::Draft,
                'sent_to_kitchen_at' => null,
                'ready_at' => null,
            ]);
        }
    }

    protected function generateOrderNumber(): string
    {
        return 'T'.strtoupper(Str::random(6));
    }
}

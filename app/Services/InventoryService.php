<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;

class InventoryService
{
    public function deductForOrder(Order $order): void
    {
        $order->load('items.menuItem.recipes.inventoryItem');

        foreach ($order->items as $orderItem) {
            if (! $orderItem->menuItem) {
                continue;
            }

            foreach ($orderItem->menuItem->recipes as $recipe) {
                $required = $recipe->quantity_required * $orderItem->quantity;
                $inventory = $recipe->inventoryItem;

                if (! $inventory) {
                    continue;
                }

                $newBalance = $inventory->quantity - $required;

                $inventory->update(['quantity' => max(0, $newBalance)]);

                StockMovement::query()->create([
                    'inventory_item_id' => $inventory->id,
                    'order_id' => $order->id,
                    'type' => 'sale',
                    'quantity' => -$required,
                    'balance_after' => max(0, $newBalance),
                    'notes' => "Order {$order->order_number}",
                ]);
            }
        }
    }

    public function adjust(InventoryItem $item, float $quantity, string $type, ?int $userId = null, ?string $notes = null): void
    {
        $newBalance = $item->quantity + $quantity;
        $item->update(['quantity' => $newBalance]);

        StockMovement::query()->create([
            'inventory_item_id' => $item->id,
            'user_id' => $userId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $newBalance,
            'notes' => $notes,
        ]);
    }

    public function lowStockItems()
    {
        return InventoryItem::query()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->get();
    }
}

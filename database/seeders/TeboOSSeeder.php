<?php

namespace Database\Seeders;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\Order;
use App\Models\DiningArea;
use App\Models\DiningTable;
use App\Models\InventoryItem;
use App\Models\KitchenNotificationSetting;
use App\Models\KitchenStation;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Recipe;
use App\Models\RestaurantSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TeboOSSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'manager', 'waiter', 'kitchen', 'cashier', 'host'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        RestaurantSetting::set('name', 'yes or no restruant and bar');
        RestaurantSetting::set('tagline', 'Restaurant & Bar');
        RestaurantSetting::set('location', 'Plot 123, Cairo Road');
        RestaurantSetting::set('city', 'Lusaka');
        RestaurantSetting::set('phone', '+260 211 123 456');
        RestaurantSetting::set('email', 'info@teboos.com');
        RestaurantSetting::set('tax_id', '1001234567');
        RestaurantSetting::set('tax_rate', '0.16');
        RestaurantSetting::set('tax_enabled', '1');
        RestaurantSetting::set('tax_label', 'VAT');

        $users = [
            ['name' => 'Admin User', 'email' => 'admin@teboos.com', 'role' => 'admin'],
            ['name' => 'Manager Mike', 'email' => 'manager@teboos.com', 'role' => 'manager'],
            ['name' => 'Waiter Wanda', 'email' => 'waiter@teboos.com', 'role' => 'waiter'],
            ['name' => 'Chef Carlos', 'email' => 'kitchen@teboos.com', 'role' => 'kitchen'],
            ['name' => 'Cashier Chris', 'email' => 'cashier@teboos.com', 'role' => 'cashier'],
            ['name' => 'Host Hannah', 'email' => 'host@teboos.com', 'role' => 'host'],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            $user->syncRoles([$data['role']]);
        }

        $stations = collect([
            ['name' => 'Grill', 'slug' => 'grill', 'color' => '#F5A623'],
            ['name' => 'Fry', 'slug' => 'fry', 'color' => '#E85D4C'],
            ['name' => 'Cold', 'slug' => 'cold', 'color' => '#4ECDC4'],
            ['name' => 'Bar', 'slug' => 'bar', 'color' => '#9B59B6'],
            ['name' => 'Expo', 'slug' => 'expo', 'color' => '#2ECC71', 'is_expo' => true],
        ])->map(fn ($s, $i) => KitchenStation::query()->updateOrCreate(
            ['slug' => $s['slug']],
            array_merge($s, ['sort_order' => $i, 'is_expo' => $s['is_expo'] ?? false])
        ));

        foreach ($stations as $station) {
            KitchenNotificationSetting::query()->updateOrCreate(
                ['kitchen_station_id' => $station->id],
                ['sla_minutes' => 5, 'escalation_minutes' => 2, 'volume' => 80]
            );
        }

        $area = DiningArea::query()->updateOrCreate(['name' => 'Main Dining'], ['sort_order' => 1]);

        for ($i = 1; $i <= 12; $i++) {
            DiningTable::query()->updateOrCreate(
                ['dining_area_id' => $area->id, 'number' => (string) $i],
                [
                    'seats' => $i % 3 === 0 ? 6 : 4,
                    'status' => TableStatus::Free,
                    'position_x' => ($i % 4) * 2,
                    'position_y' => intdiv($i - 1, 4) * 2,
                ]
            );
        }

        $grill = $stations->firstWhere('slug', 'grill');
        $fry = $stations->firstWhere('slug', 'fry');
        $cold = $stations->firstWhere('slug', 'cold');
        $bar = $stations->firstWhere('slug', 'bar');

        $categories = [
            'starters' => 'Starters',
            'mains' => 'Mains',
            'desserts' => 'Desserts',
            'drinks' => 'Drinks',
        ];

        $menuData = [
            'starters' => [
                ['name' => 'Caesar Salad', 'price' => 1200, 'station' => $cold],
                ['name' => 'Soup of the Day', 'price' => 900, 'station' => $cold],
                ['name' => 'Garlic Bread', 'price' => 700, 'station' => $grill],
            ],
            'mains' => [
                ['name' => 'Grilled Salmon', 'price' => 2800, 'station' => $grill],
                ['name' => 'Ribeye Steak', 'price' => 3500, 'station' => $grill],
                ['name' => 'Chicken Burger', 'price' => 1800, 'station' => $grill],
                ['name' => 'Fish & Chips', 'price' => 2200, 'station' => $fry],
                ['name' => 'Pasta Carbonara', 'price' => 1900, 'station' => $grill],
            ],
            'desserts' => [
                ['name' => 'Chocolate Lava Cake', 'price' => 1100, 'station' => $grill],
                ['name' => 'Ice Cream Sundae', 'price' => 900, 'station' => $cold],
            ],
            'drinks' => [
                ['name' => 'Fresh Lemonade', 'price' => 600, 'station' => $bar],
                ['name' => 'Espresso', 'price' => 400, 'station' => $bar],
                ['name' => 'House Wine', 'price' => 800, 'station' => $bar],
            ],
        ];

        foreach ($categories as $slug => $name) {
            $cat = MenuCategory::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'sort_order' => array_search($slug, array_keys($categories))]
            );

            foreach ($menuData[$slug] as $idx => $item) {
                MenuItem::query()->updateOrCreate(
                    ['menu_category_id' => $cat->id, 'name' => $item['name']],
                    [
                        'kitchen_station_id' => $item['station']->id,
                        'price_cents' => $item['price'],
                        'sort_order' => $idx,
                        'description' => "Delicious {$item['name']}",
                    ]
                );
            }
        }

        $cooking = ModifierGroup::query()->updateOrCreate(['name' => 'Cooking'], ['max_selections' => 1]);
        foreach (['Rare', 'Medium', 'Well Done'] as $mod) {
            Modifier::query()->updateOrCreate(
                ['modifier_group_id' => $cooking->id, 'name' => $mod]
            );
        }

        MenuItem::query()->where('name', 'Ribeye Steak')->first()
            ?->modifierGroups()->syncWithoutDetaching([$cooking->id]);

        $supplier = Supplier::query()->updateOrCreate(
            ['name' => 'Fresh Farms Co'],
            ['contact_name' => 'John Supplier', 'phone' => '555-0100']
        );

        $inventory = [
            ['name' => 'Salmon Fillet', 'sku' => 'SAL-001', 'qty' => 50, 'reorder' => 10, 'cost' => 800],
            ['name' => 'Ribeye Cut', 'sku' => 'RIB-001', 'qty' => 30, 'reorder' => 8, 'cost' => 1200],
            ['name' => 'Chicken Breast', 'sku' => 'CHK-001', 'qty' => 40, 'reorder' => 10, 'cost' => 400],
            ['name' => 'Potatoes', 'sku' => 'POT-001', 'qty' => 100, 'reorder' => 20, 'cost' => 50],
            ['name' => 'Olive Oil', 'sku' => 'OIL-001', 'qty' => 20, 'reorder' => 5, 'cost' => 300],
        ];

        foreach ($inventory as $inv) {
            InventoryItem::query()->updateOrCreate(
                ['sku' => $inv['sku']],
                [
                    'supplier_id' => $supplier->id,
                    'name' => $inv['name'],
                    'quantity' => $inv['qty'],
                    'reorder_level' => $inv['reorder'],
                    'unit_cost_cents' => $inv['cost'],
                    'unit' => 'kg',
                ]
            );
        }

        $salmon = MenuItem::query()->where('name', 'Grilled Salmon')->first();
        $salmonInv = InventoryItem::query()->where('sku', 'SAL-001')->first();
        if ($salmon && $salmonInv) {
            Recipe::query()->updateOrCreate(
                ['menu_item_id' => $salmon->id, 'inventory_item_id' => $salmonInv->id],
                ['quantity_required' => 0.25]
            );
        }

        $this->seedCashierDemoData(app(OrderService::class));
    }

    private function seedCashierDemoData(OrderService $orderService): void
    {
        $waiter = User::query()->role('waiter')->first();

        if (! $waiter) {
            return;
        }

        $demoNumbers = ['DEMO-CASH-1', 'DEMO-CASH-2', 'DEMO-CASH-3'];

        Order::query()->whereIn('order_number', $demoNumbers)->each(function (Order $order) {
            $order->items()->delete();
            $order->splits()->delete();
            $order->payments()->delete();
            $order->receipts()->delete();
            $order->kitchenAlerts()->delete();
            $order->delete();
        });

        $scenarios = [
            [
                'order_number' => 'DEMO-CASH-1',
                'table' => '10',
                'items' => [
                    ['name' => 'Caesar Salad', 'qty' => 2],
                    ['name' => 'House Wine', 'qty' => 2],
                ],
                'bill' => true,
            ],
            [
                'order_number' => 'DEMO-CASH-2',
                'table' => '11',
                'items' => [
                    ['name' => 'Ribeye Steak', 'qty' => 2],
                    ['name' => 'Garlic Bread', 'qty' => 1],
                    ['name' => 'Fresh Lemonade', 'qty' => 2],
                ],
                'bill' => true,
            ],
            [
                'order_number' => 'DEMO-CASH-3',
                'table' => '12',
                'items' => [
                    ['name' => 'Fish & Chips', 'qty' => 1],
                    ['name' => 'Espresso', 'qty' => 2],
                ],
                'bill' => false,
            ],
        ];

        foreach ($scenarios as $scenario) {
            $table = DiningTable::query()->where('number', $scenario['table'])->first();

            if (! $table) {
                continue;
            }

            $table->update(['status' => TableStatus::Free]);

            $order = Order::query()->create([
                'order_number' => $scenario['order_number'],
                'dining_table_id' => $table->id,
                'waiter_id' => $waiter->id,
                'status' => OrderStatus::Draft,
                'course_number' => 1,
            ]);

            $table->update(['status' => TableStatus::Occupied]);

            foreach ($scenario['items'] as $line) {
                $menuItem = MenuItem::query()->where('name', $line['name'])->first();

                if ($menuItem) {
                    $orderService->addItem($order, $menuItem, $line['qty']);
                }
            }

            $orderService->sendToKitchen($order->fresh());

            $order->refresh();

            foreach ($order->items as $item) {
                $orderService->updateItemStatus($item, OrderItemStatus::Preparing);
                $orderService->updateItemStatus($item->fresh(), OrderItemStatus::Ready);

                if ($scenario['bill']) {
                    $orderService->updateItemStatus($item->fresh(), OrderItemStatus::Served);
                }
            }

            if ($scenario['bill']) {
                $orderService->requestBill($order->fresh());
            } else {
                $order->update(['status' => OrderStatus::Ready, 'ready_at' => now()]);
            }
        }
    }
}

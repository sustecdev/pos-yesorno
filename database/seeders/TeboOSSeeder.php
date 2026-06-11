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
        RestaurantSetting::set('email', 'info@yesorno.bar');
        RestaurantSetting::set('tax_id', '1001234567');
        RestaurantSetting::set('tax_rate', '0.16');
        RestaurantSetting::set('tax_enabled', '1');
        RestaurantSetting::set('tax_label', 'VAT');

        $users = [
            ['name' => 'Admin', 'email' => 'admin@yesorno.bar', 'role' => 'admin', 'password' => 'admin@2026'],
            ['name' => 'Waiter', 'email' => 'waiter@yesorno.bar', 'role' => 'waiter', 'password' => 'waiter@2026'],
            ['name' => 'Kitchen', 'email' => 'kitchen@yesorno.bar', 'role' => 'kitchen', 'password' => 'kitchen@2026'],
            ['name' => 'Cashier', 'email' => 'cashier@yesorno.bar', 'role' => 'cashier', 'password' => 'cashier@2026'],
        ];

        foreach ($users as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make($data['password'])]
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

        $bar = $stations->firstWhere('slug', 'bar');
        $kwacha = static fn (int $amount): int => $amount * 100;

        $categories = [
            'beer' => 'Beer',
            'cider-mix' => 'Cider / Mix',
            'soda' => 'Soda',
            'juice' => 'Juice',
            'water' => 'Water',
        ];

        $menuData = [
            'beer' => [
                ['name' => 'Castle', 'price' => $kwacha(50), 'station' => $bar],
                ['name' => 'Castle Light', 'price' => $kwacha(50), 'station' => $bar],
                ['name' => 'Corona', 'price' => $kwacha(60), 'station' => $bar],
                ['name' => 'Flying Fish', 'price' => $kwacha(50), 'station' => $bar],
                ['name' => 'Mosi', 'price' => $kwacha(40), 'station' => $bar],
            ],
            'cider-mix' => [
                ['name' => 'Savanna', 'price' => $kwacha(60), 'station' => $bar],
                ['name' => 'Hunters Dry', 'price' => $kwacha(60), 'station' => $bar],
                ['name' => 'Hunters Gold', 'price' => $kwacha(60), 'station' => $bar],
                ['name' => 'Brutal Fruit Can', 'price' => $kwacha(70), 'station' => $bar],
                ['name' => 'Belgravia Gin & Dry Lemon', 'price' => $kwacha(70), 'station' => $bar],
            ],
            'soda' => [
                ['name' => 'Coca Cola', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Coca Cola Zero', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Fanta Orange', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Mountain Dew', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Sprite', 'price' => $kwacha(25), 'station' => $bar],
            ],
            'juice' => [
                ['name' => 'Fruiticana Pineapple', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Fruiticana Mix Fruit', 'price' => $kwacha(25), 'station' => $bar],
                ['name' => 'Fruiticana Orange', 'price' => $kwacha(25), 'station' => $bar],
            ],
            'water' => [
                ['name' => 'Vatra', 'price' => $kwacha(10), 'station' => $bar],
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

        $supplier = Supplier::query()->updateOrCreate(
            ['name' => 'Beverage Distributors Ltd'],
            ['contact_name' => 'Supply Desk', 'phone' => '+260 211 000 000']
        );

        $inventory = [
            ['name' => 'Castle Lager Case', 'sku' => 'BEER-CAS-001', 'qty' => 48, 'reorder' => 12, 'cost' => $kwacha(35), 'unit' => 'case'],
            ['name' => 'Mosi Lager Case', 'sku' => 'BEER-MOS-001', 'qty' => 36, 'reorder' => 12, 'cost' => $kwacha(28), 'unit' => 'case'],
            ['name' => 'Coca Cola Crate', 'sku' => 'SODA-CC-001', 'qty' => 24, 'reorder' => 6, 'cost' => $kwacha(18), 'unit' => 'crate'],
            ['name' => 'Vatra Water Case', 'sku' => 'WAT-VAT-001', 'qty' => 60, 'reorder' => 15, 'cost' => $kwacha(7), 'unit' => 'case'],
            ['name' => 'Fruiticana Carton', 'sku' => 'JUI-FRU-001', 'qty' => 30, 'reorder' => 8, 'cost' => $kwacha(18), 'unit' => 'carton'],
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
                    'unit' => $inv['unit'],
                ]
            );
        }

        $castle = MenuItem::query()->where('name', 'Castle')->first();
        $castleStock = InventoryItem::query()->where('sku', 'BEER-CAS-001')->first();
        if ($castle && $castleStock) {
            Recipe::query()->updateOrCreate(
                ['menu_item_id' => $castle->id, 'inventory_item_id' => $castleStock->id],
                ['quantity_required' => 1]
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
                    ['name' => 'Castle', 'qty' => 2],
                    ['name' => 'Savanna', 'qty' => 2],
                ],
                'bill' => true,
            ],
            [
                'order_number' => 'DEMO-CASH-2',
                'table' => '11',
                'items' => [
                    ['name' => 'Corona', 'qty' => 2],
                    ['name' => 'Coca Cola', 'qty' => 3],
                    ['name' => 'Fanta Orange', 'qty' => 2],
                ],
                'bill' => true,
            ],
            [
                'order_number' => 'DEMO-CASH-3',
                'table' => '12',
                'items' => [
                    ['name' => 'Mosi', 'qty' => 2],
                    ['name' => 'Vatra', 'qty' => 3],
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

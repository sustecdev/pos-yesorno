<?php

namespace Database\Seeders;

use App\Enums\TableStatus;
use App\Models\DiningArea;
use App\Models\DiningTable;
use App\Models\KitchenNotificationSetting;
use App\Models\KitchenStation;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use App\Models\User;
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

        $tableCount = 32;
        $tableColumns = 8;

        for ($i = 1; $i <= $tableCount; $i++) {
            DiningTable::query()->updateOrCreate(
                ['dining_area_id' => $area->id, 'number' => (string) $i],
                [
                    'seats' => $i % 3 === 0 ? 6 : 4,
                    'status' => TableStatus::Free,
                    'position_x' => (($i - 1) % $tableColumns) * 2,
                    'position_y' => intdiv($i - 1, $tableColumns) * 2,
                ]
            );
        }

        DiningTable::query()
            ->where('dining_area_id', $area->id)
            ->whereNotIn('number', collect(range(1, $tableCount))->map(fn ($n) => (string) $n))
            ->delete();

        $grill = $stations->firstWhere('slug', 'grill');
        $fry = $stations->firstWhere('slug', 'fry');
        $bar = $stations->firstWhere('slug', 'bar');
        $kwacha = static fn (int $amount): int => $amount * 100;

        $categories = [
            'grill-plates' => 'Grill Plates',
            'snacks' => 'Snacks',
            'beer' => 'Beer',
            'cider-mix' => 'Cider / Mix',
            'soda' => 'Soda',
            'juice' => 'Juice',
            'water' => 'Water',
        ];

        $menuData = [
            'grill-plates' => [
                ['name' => 'Beef Meat Ball Kebabs with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Goat Meat with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Lamb Kofta with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Hungarian Sausage with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Borewors Sausage with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Pork Chops with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Beef Blades with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Beef Short Ribs with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
                ['name' => 'Sliced Beef Briskets with Vegetables', 'price' => $kwacha(120), 'station' => $grill],
            ],
            'snacks' => [
                ['name' => 'Chips', 'price' => $kwacha(50), 'station' => $fry],
                ['name' => 'Chicken Samosas', 'price' => $kwacha(50), 'station' => $fry],
                ['name' => 'Spring Rolls', 'price' => $kwacha(50), 'station' => $fry],
                ['name' => 'Fried Chicken per Piece', 'price' => $kwacha(50), 'station' => $fry],
                ['name' => 'Chicken Wings 2 Pieces', 'price' => $kwacha(50), 'station' => $fry],
            ],
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

    }
}

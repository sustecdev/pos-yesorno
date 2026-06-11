<?php

namespace Tests\Feature;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\InventoryManager;
use App\Livewire\Admin\MenuManager;
use App\Livewire\Admin\RestaurantSetup;
use App\Livewire\Admin\StaffManager;
use App\Livewire\Admin\TaxSettings;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\SelectWorkspace;
use App\Livewire\Cashier\PaymentTerminal;
use App\Livewire\Host\ReservationCalendar;
use App\Livewire\Kitchen\KdsBoard;
use App\Livewire\Reports\SalesDashboard;
use App\Livewire\Waiter\FloorPlan;
use App\Livewire\Waiter\OrderBuilder;
use App\Models\DiningTable;
use App\Models\InventoryItem;
use App\Models\KitchenAlert;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Support\RestaurantProfile;
use Database\Seeders\TeboOSSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeboOSFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeboOSSeeder::class);
    }

    protected function user(string $role): User
    {
        return User::query()->role($role)->firstOrFail();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();
    }

    public function test_public_prefix_is_stripped_from_urls(): void
    {
        $this->get('/public/login')
            ->assertRedirect('/login');

        $this->get('/public')
            ->assertRedirect('/');
    }

    public function test_each_role_can_login_and_reach_dashboard(): void
    {
        $routes = [
            'admin' => 'admin.dashboard',
            'manager' => 'admin.dashboard',
            'waiter' => 'waiter.floor',
            'kitchen' => 'kitchen.kds',
            'cashier' => 'cashier.terminal',
            'host' => 'host.reservations',
        ];

        foreach ($routes as $role => $route) {
            $component = Livewire::test(Login::class)
                ->set('email', "{$role}@teboos.com")
                ->set('password', 'password')
                ->call('login');

            if (in_array($role, ['admin', 'manager'], true)) {
                $component->assertRedirect(route('workspace.select'));
            } else {
                $component->assertRedirect(route($route));
            }
        }
    }

    public function test_admin_can_login_into_any_workspace(): void
    {
        $admin = $this->user('admin');

        foreach (['admin', 'waiter', 'kitchen', 'cashier', 'host'] as $workspace) {
            Livewire::actingAs($admin)
                ->test(SelectWorkspace::class)
                ->call('select', $workspace)
                ->assertRedirect(route(match ($workspace) {
                    'admin' => 'admin.dashboard',
                    'waiter' => 'waiter.floor',
                    'kitchen' => 'kitchen.kds',
                    'cashier' => 'cashier.terminal',
                    'host' => 'host.reservations',
                }));

            $this->actingAs($admin)
                ->get(route(match ($workspace) {
                    'admin' => 'admin.dashboard',
                    'waiter' => 'waiter.floor',
                    'kitchen' => 'kitchen.kds',
                    'cashier' => 'cashier.terminal',
                    'host' => 'host.reservations',
                }))
                ->assertOk();
        }
    }

    public function test_role_middleware_blocks_unauthorized_access(): void
    {
        $waiter = $this->user('waiter');

        $this->actingAs($waiter)
            ->get(route('kitchen.kds'))
            ->assertForbidden();

        $this->actingAs($waiter)
            ->get(route('cashier.terminal'))
            ->assertForbidden();

        $this->actingAs($waiter)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_waiter_floor_plan_and_filters(): void
    {
        Livewire::actingAs($this->user('waiter'))
            ->test(FloorPlan::class)
            ->assertOk()
            ->call('setFilter', 'active')
            ->assertSet('filter', 'active')
            ->call('setFilter', 'ready')
            ->assertSet('filter', 'ready');
    }

    public function test_waiter_full_order_flow(): void
    {
        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '1')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();
        $soup = MenuItem::query()->where('name', 'Soup of the Day')->firstOrFail();

        $component = Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->assertSet('order.status', OrderStatus::Sent)
            ->call('markRush')
            ->assertSet('order.is_rush', true)
            ->call('fireCourse')
            ->assertSet('order.course_number', 2)
            ->call('quickAddOrConfigure', $soup->id)
            ->call('sendToCashier')
            ->assertSet('order.status', OrderStatus::Served);

        $order = Order::query()->find($component->get('order')->id);
        $this->assertNotNull($order->sent_to_kitchen_at);
        $this->assertTrue($order->items()->whereNotNull('sent_at')->exists());

        $this->assertDatabaseHas('kitchen_alerts', [
            'order_id' => $order->id,
        ]);
    }

    public function test_waiter_can_apply_discount_when_sending_bill(): void
    {
        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '6')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('openBillPanel')
            ->assertSet('showBillPanel', true)
            ->set('discountType', 'flat')
            ->set('discountValue', 200)
            ->call('sendToCashier')
            ->assertSet('order.status', OrderStatus::Served)
            ->assertDispatched('toast');

        $order = Order::query()->where('dining_table_id', $table->id)->firstOrFail();
        $this->assertSame('flat', $order->discount_type);
        $this->assertSame(200, $order->discount_value);
        $this->assertSame(200, $order->discount_cents);
    }

    public function test_waiter_can_apply_percent_discount_when_sending_bill(): void
    {
        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '8')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('openBillPanel')
            ->set('discountType', 'percent')
            ->set('discountValue', 10)
            ->call('sendToCashier');

        $order = Order::query()->where('dining_table_id', $table->id)->firstOrFail();
        $this->assertSame('percent', $order->discount_type);
        $this->assertSame(10, $order->discount_value);
        $this->assertSame((int) round($order->subtotal_cents * 0.10), $order->discount_cents);
    }

    public function test_waiter_can_remove_items_before_payment(): void
    {
        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '2')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        $component = Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen');

        $itemId = $component->get('order')->items->first()->id;

        $component
            ->call('removeItem', $itemId)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('order_items', [
            'id' => $itemId,
            'status' => OrderItemStatus::Cancelled->value,
        ]);
    }

    public function test_kitchen_kds_page_renders_with_active_tickets(): void
    {
        $waiter = $this->user('waiter');
        $kitchen = $this->user('kitchen');
        $table = DiningTable::query()->where('number', '8')->firstOrFail();
        $garlicBread = MenuItem::query()->where('name', 'Garlic Bread')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $garlicBread->id)
            ->call('sendToKitchen');

        $this->actingAs($kitchen)
            ->get(route('kitchen.kds'))
            ->assertOk()
            ->assertSee('T'.$table->number);
    }

    public function test_kitchen_kds_item_lifecycle(): void
    {
        $waiter = $this->user('waiter');
        $kitchen = $this->user('kitchen');
        $table = DiningTable::query()->where('number', '3')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen');

        $item = Order::query()->where('dining_table_id', $table->id)->first()->items()->first();

        Livewire::actingAs($kitchen)
            ->test(KdsBoard::class)
            ->call('startItem', $item->id);

        $item->refresh();
        $this->assertSame(OrderItemStatus::Preparing, $item->status);

        Livewire::actingAs($kitchen)
            ->test(KdsBoard::class)
            ->call('readyItem', $item->id);

        $item->refresh();
        $this->assertSame(OrderItemStatus::Ready, $item->status);

        Livewire::actingAs($kitchen)
            ->test(KdsBoard::class)
            ->call('bumpItem', $item->id);

        $item->refresh();
        $this->assertSame(OrderItemStatus::Served, $item->status);
    }

    public function test_kitchen_can_acknowledge_alerts(): void
    {
        $waiter = $this->user('waiter');
        $kitchen = $this->user('kitchen');
        $table = DiningTable::query()->where('number', '4')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('markRush');

        $alert = KitchenAlert::query()->whereNull('acknowledged_at')->first();
        $this->assertNotNull($alert);

        Livewire::actingAs($kitchen)
            ->test(KdsBoard::class)
            ->call('acknowledge', $alert->id);

        $this->assertNotNull($alert->fresh()->acknowledged_at);
    }

    public function test_seeder_includes_cashier_demo_orders(): void
    {
        $this->assertTrue(
            Order::query()->where('order_number', 'DEMO-CASH-1')->where('status', OrderStatus::Served)->exists()
        );
        $this->assertTrue(
            Order::query()->where('order_number', 'DEMO-CASH-2')->where('status', OrderStatus::Served)->exists()
        );
        $this->assertTrue(
            Order::query()->where('order_number', 'DEMO-CASH-3')->where('status', OrderStatus::Ready)->exists()
        );

        Livewire::actingAs($this->user('cashier'))
            ->test(PaymentTerminal::class)
            ->assertSee('DEMO-CASH-1')
            ->assertSee('DEMO-CASH-2')
            ->assertSee('DEMO-CASH-3');
    }

    public function test_cashier_can_see_recent_paid_orders(): void
    {
        $waiter = $this->user('waiter');
        $cashier = $this->user('cashier');
        $table = DiningTable::query()->where('number', '4')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('sendToCashier');

        $order = Order::query()->where('dining_table_id', $table->id)->firstOrFail();

        Livewire::actingAs($cashier)
            ->test(PaymentTerminal::class)
            ->call('selectOrder', $order->id)
            ->set('paymentMethod', 'cash')
            ->call('processPayment');

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);

        Livewire::actingAs($cashier)
            ->test(PaymentTerminal::class)
            ->call('setListTab', 'recent')
            ->assertSee($order->order_number)
            ->call('selectOrder', $order->id)
            ->assertSee('Paid');
    }

    public function test_cashier_payment_discount_and_pay(): void
    {
        $waiter = $this->user('waiter');
        $cashier = $this->user('cashier');
        $table = DiningTable::query()->where('number', '5')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('sendToCashier');

        $order = Order::query()->where('dining_table_id', $table->id)->first();

        Livewire::actingAs($cashier)
            ->test(PaymentTerminal::class)
            ->call('selectOrder', $order->id)
            ->set('discountType', 'flat')
            ->set('discountValue', 100)
            ->call('applyDiscount')
            ->set('paymentMethod', 'cash')
            ->set('tipCents', 200)
            ->call('processPayment')
            ->assertDispatched('toast');

        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertTrue($order->payments()->exists());
        $this->assertSame(TableStatus::Dirty, $order->table->fresh()->status);
    }

    public function test_host_reservation_lifecycle(): void
    {
        $host = $this->user('host');
        $table = DiningTable::query()->where('number', '6')->firstOrFail();

        Livewire::actingAs($host)
            ->test(ReservationCalendar::class)
            ->call('openForm')
            ->set('guestName', 'Jane Doe')
            ->set('guestPhone', '555-1234')
            ->set('partySize', 4)
            ->set('reservedAt', now()->addHours(2)->format('Y-m-d\TH:i'))
            ->set('tableId', $table->id)
            ->call('saveReservation')
            ->assertDispatched('toast');

        $reservation = Reservation::query()->where('guest_name', 'Jane Doe')->first();
        $this->assertNotNull($reservation);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(TableStatus::Reserved, $table->fresh()->status);

        Livewire::actingAs($host)
            ->test(ReservationCalendar::class)
            ->call('seatGuest', $reservation->id)
            ->assertDispatched('toast');

        $reservation->refresh();
        $this->assertSame(ReservationStatus::Seated, $reservation->status);
        $this->assertSame(TableStatus::Occupied, $table->fresh()->status);
        $this->assertTrue($table->fresh()->activeOrder()->exists());

        $cancelTable = DiningTable::query()->where('number', '7')->firstOrFail();

        Livewire::actingAs($host)
            ->test(ReservationCalendar::class)
            ->set('guestName', 'Cancel Me')
            ->set('partySize', 2)
            ->set('reservedAt', now()->addHour()->format('Y-m-d\TH:i'))
            ->set('tableId', $cancelTable->id)
            ->call('saveReservation');

        $toCancel = Reservation::query()->where('guest_name', 'Cancel Me')->first();

        Livewire::actingAs($host)
            ->test(ReservationCalendar::class)
            ->call('cancelReservation', $toCancel->id);

        $this->assertSame(ReservationStatus::Cancelled, $toCancel->fresh()->status);
        $this->assertSame(TableStatus::Free, $cancelTable->fresh()->status);
    }

    public function test_admin_dashboard_broadcast_and_pages(): void
    {
        $admin = $this->user('admin');

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertOk()
            ->set('kitchenBroadcast', 'Kitchen meeting in 5 minutes')
            ->call('sendBroadcast')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('kitchen_alerts', [
            'type' => 'kitchen_broadcast',
        ]);

        $this->actingAs($admin)->get(route('admin.tax'))->assertOk();
        $this->actingAs($admin)->get(route('admin.menu'))->assertOk();
        $this->actingAs($admin)->get(route('admin.staff'))->assertOk();
        $this->actingAs($admin)->get(route('admin.inventory'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reports'))->assertOk();
    }

    public function test_admin_can_add_waiter_from_staff_page(): void
    {
        $admin = $this->user('admin');

        Livewire::actingAs($admin)
            ->test(StaffManager::class)
            ->set('name', 'Waiter John')
            ->set('email', 'john@teboos.com')
            ->set('password', 'password123')
            ->set('role', 'waiter')
            ->call('save')
            ->assertDispatched('toast');

        $waiter = User::query()->where('email', 'john@teboos.com')->first();
        $this->assertNotNull($waiter);
        $this->assertTrue($waiter->hasRole('waiter'));
    }

    public function test_admin_can_view_orders_for_a_waiter(): void
    {
        $admin = $this->user('admin');
        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '1')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen');

        $order = Order::query()->where('waiter_id', $waiter->id)->first();
        $this->assertNotNull($order);

        Livewire::actingAs($admin)
            ->test(StaffManager::class)
            ->call('viewOrders', $waiter->id)
            ->assertSet('viewingOrdersForId', $waiter->id)
            ->assertSee($order->order_number)
            ->assertSee('Orders — '.$waiter->name);
    }

    public function test_admin_menu_manager_crud(): void
    {
        $admin = $this->user('admin');
        $category = MenuCategory::query()->firstOrFail();

        Livewire::actingAs($admin)
            ->test(MenuManager::class)
            ->set('name', 'Test Special')
            ->set('priceCents', 1500)
            ->set('categoryId', $category->id)
            ->call('save')
            ->assertDispatched('toast');

        $item = MenuItem::query()->where('name', 'Test Special')->first();
        $this->assertNotNull($item);

        Livewire::actingAs($admin)
            ->test(MenuManager::class)
            ->call('toggleAvailability', $item->id);

        $this->assertFalse($item->fresh()->is_available);

        Livewire::actingAs($admin)
            ->test(MenuManager::class)
            ->call('delete', $item->id)
            ->assertDispatched('toast');

        $this->assertNull(MenuItem::query()->find($item->id));
    }

    public function test_admin_inventory_adjustment(): void
    {
        $admin = $this->user('admin');
        $item = InventoryItem::query()->firstOrFail();
        $before = $item->quantity;

        Livewire::actingAs($admin)
            ->test(InventoryManager::class)
            ->call('openAdjust', $item->id)
            ->set('adjustQuantity', 5)
            ->set('adjustNotes', 'Stock delivery')
            ->call('applyAdjustment')
            ->assertDispatched('toast');

        $this->assertEqualsWithDelta($before + 5, (float) $item->fresh()->quantity, 0.001);

        $toDelete = InventoryItem::query()->create([
            'supplier_id' => $item->supplier_id,
            'name' => 'Temp Delete Item',
            'sku' => 'TMP-DELETE-001',
            'unit' => 'kg',
            'quantity' => 1,
            'reorder_level' => 0,
            'unit_cost_cents' => 100,
        ]);

        Livewire::actingAs($admin)
            ->test(InventoryManager::class)
            ->call('delete', $toDelete->id)
            ->assertDispatched('toast');

        $this->assertNull(InventoryItem::query()->find($toDelete->id));
    }

    public function test_sales_dashboard_renders(): void
    {
        Livewire::actingAs($this->user('admin'))
            ->test(SalesDashboard::class)
            ->assertOk();
    }

    public function test_admin_can_save_restaurant_settings(): void
    {
        Livewire::actingAs($this->user('admin'))
            ->test(RestaurantSetup::class)
            ->set('name', 'Lusaka Bistro')
            ->set('tagline', 'Fresh local cuisine')
            ->set('location', '123 Independence Ave')
            ->set('city', 'Lusaka')
            ->set('phone', '+260 977 000 111')
            ->set('email', 'hello@bistro.zm')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertSame('Lusaka Bistro', RestaurantProfile::get('name'));
        $this->assertSame('123 Independence Ave', RestaurantProfile::get('location'));
        $this->assertSame('Lusaka', RestaurantProfile::get('city'));
        $this->assertSame('+260 977 000 111', RestaurantProfile::get('phone'));
    }

    public function test_admin_can_configure_tax_settings(): void
    {
        Livewire::actingAs($this->user('admin'))
            ->test(TaxSettings::class)
            ->set('taxEnabled', true)
            ->set('taxLabel', 'VAT')
            ->set('taxRatePercent', '16')
            ->set('taxId', '3001112222')
            ->call('save')
            ->assertDispatched('toast');

        $this->assertTrue(RestaurantProfile::isTaxEnabled());
        $this->assertSame('VAT', RestaurantProfile::taxLabel());
        $this->assertEqualsWithDelta(0.16, RestaurantProfile::taxRateDecimal(), 0.0001);
        $this->assertSame('3001112222', RestaurantProfile::get('tax_id'));

        $waiter = $this->user('waiter');
        $table = DiningTable::query()->where('number', '9')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id);

        $order = Order::query()->where('dining_table_id', $table->id)->firstOrFail();
        $expectedTax = (int) round($order->subtotal_cents * 0.16);
        $this->assertSame($expectedTax, $order->tax_cents);
    }

    public function test_receipt_includes_restaurant_profile_on_bill(): void
    {
        RestaurantProfile::update([
            'name' => 'Bill Test Cafe',
            'location' => 'Market Street',
            'city' => 'Ndola',
            'phone' => '+260 212 555 000',
        ]);

        $waiter = $this->user('waiter');
        $cashier = $this->user('cashier');
        $table = DiningTable::query()->where('number', '3')->firstOrFail();
        $salad = MenuItem::query()->where('name', 'Caesar Salad')->firstOrFail();

        Livewire::actingAs($waiter)
            ->test(OrderBuilder::class, ['table' => $table])
            ->call('quickAddOrConfigure', $salad->id)
            ->call('sendToKitchen')
            ->call('sendToCashier');

        $order = Order::query()->where('dining_table_id', $table->id)->firstOrFail();

        Livewire::actingAs($cashier)
            ->test(PaymentTerminal::class)
            ->call('selectOrder', $order->id)
            ->set('paymentMethod', 'cash')
            ->call('processPayment');

        $receipt = Receipt::query()->where('order_id', $order->id)->firstOrFail();
        $data = $receipt->data;

        $this->assertSame('Bill Test Cafe', $data['name']);
        $this->assertSame('Market Street, Ndola', $data['address_line']);
        $this->assertSame('+260 212 555 000', $data['phone']);
    }
}

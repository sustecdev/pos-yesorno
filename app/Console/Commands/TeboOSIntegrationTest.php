<?php

namespace App\Console\Commands;

use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Enums\TableStatus;
use App\Models\DiningTable;
use App\Models\InventoryItem;
use App\Models\KitchenAlert;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Models\User;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Support\RestaurantProfile;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class TeboOSIntegrationTest extends Command
{
    protected $signature = 'teboos:integration-test {--restaurant=yes or no restruant and bar}';

    protected $description = 'Run a full integration test against the live database with real data';

    private int $passed = 0;

    private int $failed = 0;

    private array $failures = [];

    public function handle(OrderService $orderService, PaymentService $paymentService): int
    {
        $restaurantName = $this->option('restaurant');

        $this->info('TeboOS Real-Data Integration Test');
        $this->info('Database: '.config('database.default').' / '.config('database.connections.'.config('database.default').'.database'));
        $this->newLine();

        RestaurantProfile::update([
            'name' => $restaurantName,
            'tagline' => 'Restaurant & Bar',
            'location' => 'Plot 45, Great East Road',
            'city' => 'Lusaka',
            'phone' => '+260 977 123 456',
            'email' => 'info@yesornobar.zm',
            'tax_id' => '1009876543',
        ]);

        $this->runCheck('Database connection', fn () => DB::connection()->getPdo() !== null);
        $this->runCheck('Restaurant name saved', fn () => RestaurantProfile::get('name') === $restaurantName);
        $this->runCheck('Staff accounts exist', fn () => User::role('admin')->exists() && User::role('waiter')->exists());

        foreach (['admin', 'manager', 'waiter', 'kitchen', 'cashier', 'host'] as $role) {
            $user = User::role($role)->first();
            $this->runCheck("User role: {$role}", fn () => $user !== null, "{$role}@teboos.com");
        }

        $routes = [
            'admin.dashboard' => 'admin',
            'admin.restaurant' => 'admin',
            'admin.menu' => 'admin',
            'admin.staff' => 'admin',
            'admin.inventory' => 'admin',
            'admin.reports' => 'admin',
            'waiter.floor' => 'waiter',
            'kitchen.kds' => 'kitchen',
            'cashier.terminal' => 'cashier',
            'host.reservations' => 'host',
        ];

        foreach ($routes as $route => $role) {
            $user = User::role($role)->first();
            $response = $this->getRoute($route, $user);
            $this->runCheck("Route {$route}", fn () => $response['status'] === 200);
        }

        $waiter = User::role('waiter')->first();
        $cashier = User::role('cashier')->first();
        $kitchen = User::role('kitchen')->first();
        $host = User::role('host')->first();
        $admin = User::role('admin')->first();

        $table = DiningTable::query()->where('status', TableStatus::Free)->orderBy('number')->first()
            ?? DiningTable::query()->orderBy('number')->first();

        $this->runCheck('Free dining table available', fn () => $table !== null, "Table {$table?->number}");

        $salad = MenuItem::query()->where('name', 'Caesar Salad')->where('is_available', true)->first()
            ?? MenuItem::query()->where('is_available', true)->first();

        $this->runCheck('Menu item available', fn () => $salad !== null, $salad?->name);

        $order = null;
        $this->runCheck('Waiter creates order', function () use ($orderService, $waiter, $table, $salad, &$order) {
            $order = $orderService->createForTable($table, $waiter);
            $orderService->addItem($order, $salad, 1);
            $orderService->sendToKitchen($order);

            return $order->fresh()->status === OrderStatus::Sent;
        }, $order?->order_number);

        $this->runCheck('Kitchen alert created', fn () => KitchenAlert::query()->where('order_id', $order->id)->exists());

        $item = $order->items()->first();
        $this->runCheck('Kitchen prepares item', function () use ($orderService, $item) {
            $orderService->updateItemStatus($item, OrderItemStatus::Preparing);
            $orderService->updateItemStatus($item->fresh(), OrderItemStatus::Ready);
            $orderService->updateItemStatus($item->fresh(), OrderItemStatus::Served);

            return $item->fresh()->status === OrderItemStatus::Served;
        });

        $this->runCheck('Waiter sends bill to cashier', function () use ($orderService, $order) {
            $orderService->requestBill($order);

            return $order->fresh()->status === OrderStatus::Served;
        });

        $cashierResponse = $this->getRoute('cashier.terminal', $cashier, ['orderId' => $order->id]);
        $this->runCheck('Cashier bill shows restaurant name', function () use ($cashierResponse, $restaurantName) {
            return str_contains($cashierResponse['content'], $restaurantName);
        });

        $this->runCheck('Cashier payment & receipt', function () use ($paymentService, $order, $cashier, $restaurantName) {
            $paymentService->applyDiscount($order->fresh(), 'flat', 100);
            $payment = $paymentService->recordPayment(
                $order->fresh(),
                PaymentMethod::Cash,
                $order->fresh()->total_cents,
                $cashier,
            );

            $receipt = Receipt::query()->where('payment_id', $payment->id)->first();

            return $order->fresh()->status === OrderStatus::Paid
                && $receipt !== null
                && ($receipt->data['name'] ?? '') === $restaurantName
                && str_contains($receipt->data['address_line'] ?? '', 'Lusaka');
        }, "Receipt for order {$order->order_number}");

        $resTable = DiningTable::query()
            ->where('status', TableStatus::Free)
            ->where('id', '!=', $table->id)
            ->orderBy('number')
            ->first();

        $this->runCheck('Host creates reservation', function () use ($host, $resTable) {
            if (! $resTable) {
                return false;
            }

            $reservation = Reservation::query()->create([
                'guest_name' => 'Integration Test Guest',
                'guest_phone' => '+260 999 000 111',
                'party_size' => 2,
                'reserved_at' => now()->addHours(3),
                'dining_table_id' => $resTable->id,
                'host_id' => $host->id,
                'status' => ReservationStatus::Confirmed,
            ]);

            $resTable->update(['status' => TableStatus::Reserved]);

            return $reservation->exists();
        });

        $this->runCheck('Admin menu has categories', fn () => MenuCategory::query()->count() > 0);
        $this->runCheck('Inventory items exist', fn () => InventoryItem::query()->count() > 0);
        $this->runCheck('Kwacha money helper', fn () => money(12500) === 'K 125.00');

        $restaurantPage = $this->getRoute('admin.restaurant', $admin);
        $this->runCheck('Admin restaurant page shows name', function () use ($restaurantPage, $restaurantName) {
            return str_contains($restaurantPage['content'], $restaurantName);
        });

        $this->newLine();
        $this->info("Results: {$this->passed} passed, {$this->failed} failed");

        if ($this->failed > 0) {
            $this->error('Failures:');
            foreach ($this->failures as $failure) {
                $this->line("  - {$failure}");
            }

            return self::FAILURE;
        }

        $this->info('All integration checks passed with real data.');

        return self::SUCCESS;
    }

    private function runCheck(string $label, callable $check, ?string $detail = null): void
    {
        try {
            $ok = (bool) $check();
        } catch (\Throwable $e) {
            $ok = false;
            $detail = ($detail ? "{$detail} — " : '').$e->getMessage();
        }

        if ($ok) {
            $this->passed++;
            $suffix = $detail ? " ({$detail})" : '';
            $this->line("<fg=green>PASS</> {$label}{$suffix}");
        } else {
            $this->failed++;
            $suffix = $detail ? " — {$detail}" : '';
            $this->failures[] = $label.$suffix;
            $this->line("<fg=red>FAIL</> {$label}{$suffix}");
        }
    }

    private function getRoute(string $name, User $user, array $params = []): array
    {
        auth()->login($user);

        $url = Route::has($name) ? route($name, $params) : '/';
        $request = Request::create($url, 'GET');
        $request->setLaravelSession(app('session')->driver());
        app('session')->start();
        $request->setUserResolver(fn () => $user);

        $response = app(Kernel::class)->handle($request);

        return [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ];
    }
}

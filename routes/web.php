<?php

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
use App\Http\Controllers\ReceiptController;
use App\Livewire\Waiter\OrderBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect(auth()->user()->dashboardRoute())
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/workspace', SelectWorkspace::class)->name('workspace.select');
});

Route::middleware(['auth', 'workspace.sync'])->group(function () {
    Route::middleware('role:waiter,admin,manager')->prefix('waiter')->name('waiter.')->group(function () {
        Route::get('/floor', FloorPlan::class)->name('floor');
        Route::get('/order/{table}', OrderBuilder::class)->name('order');
    });

    Route::middleware('role:kitchen,admin,manager')->prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/kds', KdsBoard::class)->name('kds');
    });

    Route::middleware('role:cashier,admin,manager')->prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/terminal/{orderId?}', PaymentTerminal::class)->name('terminal');
    });

    Route::middleware('role:host,admin,manager')->prefix('host')->name('host.')->group(function () {
        Route::get('/reservations', ReservationCalendar::class)->name('reservations');
    });

    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])
        ->middleware('role:cashier,admin,manager')
        ->name('receipts.show');

    Route::middleware('role:admin,manager')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/restaurant', RestaurantSetup::class)->name('restaurant');
        Route::get('/tax', TaxSettings::class)->name('tax');
        Route::get('/menu', MenuManager::class)->name('menu');
        Route::get('/staff', StaffManager::class)->name('staff');
        Route::get('/inventory', InventoryManager::class)->name('inventory');
        Route::get('/reports', SalesDashboard::class)->name('reports');
    });
});

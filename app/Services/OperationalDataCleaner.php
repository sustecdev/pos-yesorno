<?php

namespace App\Services;

use App\Enums\TableStatus;
use App\Models\DiningTable;
use Illuminate\Support\Facades\DB;

class OperationalDataCleaner
{
    public function clear(): void
    {
        DB::transaction(function () {
            DB::table('receipts')->delete();
            DB::table('payments')->delete();
            DB::table('order_split_items')->delete();
            DB::table('order_splits')->delete();
            DB::table('order_item_modifiers')->delete();
            DB::table('kitchen_alerts')->delete();
            DB::table('order_items')->delete();
            DB::table('stock_movements')->delete();
            DB::table('orders')->delete();
            DB::table('reservations')->delete();
            DB::table('recipes')->delete();
            DB::table('inventory_items')->delete();
            DB::table('suppliers')->delete();
            DB::table('activity_logs')->delete();
            DB::table('shifts')->delete();

            DiningTable::query()->update(['status' => TableStatus::Free]);
        });
    }
}

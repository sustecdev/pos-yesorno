<?php

namespace App\Console\Commands;

use App\Services\OperationalDataCleaner;
use Illuminate\Console\Command;

class ClearOperationalData extends Command
{
    protected $signature = 'tebo:clear-data {--force : Run without confirmation}';

    protected $description = 'Clear orders, reservations, inventory, and logs while keeping users and menus';

    public function handle(OperationalDataCleaner $cleaner): int
    {
        if (! $this->option('force') && ! $this->confirm('Delete all orders, reservations, inventory, and activity logs? Run deploy/backup-db.sh on the server first.')) {
            return self::SUCCESS;
        }

        $cleaner->clear();

        $this->info('Operational data cleared. Users and menus were kept.');

        return self::SUCCESS;
    }
}

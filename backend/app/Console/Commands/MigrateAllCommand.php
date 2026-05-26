<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateAllCommand extends Command
{
    protected $signature = 'migrate:all
                            {--database= : Connection name (default: default)}
                            {--force : Force in production}';

    protected $description = 'Alias voor php artisan migrate (bundelmigratie + eventuele nieuwe bestanden in database/migrations/).';

    public function handle(): int
    {
        $connection = $this->option('database') ?? config('database.default');
        $exitCode = Artisan::call('migrate', [
            '--database' => $connection,
            '--force' => $this->option('force'),
        ]);
        $this->output->write(Artisan::output());
        if ($exitCode !== 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

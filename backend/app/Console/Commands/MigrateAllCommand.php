<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateAllCommand extends Command
{
    protected $signature = 'migrate:all
                            {--database= : Connection name (default: default)}
                            {--force : Force in production}';

    protected $description = 'Run all migration paths for the main database (core, shared, taxiroyaal, skillmatching). Use for fresh/initial install on nexa DB.';

    public function handle(): int
    {
        $connection = $this->option('database') ?? config('database.default');
        $paths = [
            'database/migrations/core',
            'database/migrations/shared',
            'database/migrations/modules/taxiroyaal',
            'database/migrations/modules/skillmatching',
        ];

        foreach ($paths as $path) {
            if (!is_dir(base_path($path))) {
                continue;
            }
            $this->info("Migrating: {$path}");
            $exitCode = Artisan::call('migrate', [
                '--database' => $connection,
                '--path' => $path,
                '--force' => $this->option('force'),
            ]);
            if ($exitCode !== 0) {
                $this->error(trim(Artisan::output()));
                return self::FAILURE;
            }
        }

        $this->info('All migrations completed.');
        return self::SUCCESS;
    }
}

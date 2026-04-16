<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use App\Services\ModuleSchemaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ModuleInstallVerifyCommand extends Command
{
    protected $signature = 'modules:install-verify
                            {module : Module name (e.g. taxi)}
                            {--run : Actually run the install (drops and recreates module DB)}';

    protected $description = 'Run module install and verify: module record on main DB + superadmin user in module DB.';

    public function handle(ModuleManager $moduleManager, ModuleDatabaseService $dbService): int
    {
        $moduleName = $this->argument('module');
        $mainConn = config('database.main_connection', config('database.default'));

        if (!$dbService->supportsModuleDatabases()) {
            $this->error('Module databases are only supported for MySQL or PostgreSQL.');
            return self::FAILURE;
        }

        if ($this->option('run')) {
            $this->info("Running install for {$moduleName}...");
            try {
                $moduleManager->installModule($moduleName);
                $this->info('Install completed without exception.');
            } catch (\Throwable $e) {
                $this->error('Install failed: ' . $e->getMessage());
                return self::FAILURE;
            }
        }

        $moduleConn = $dbService->getModuleConnectionName($moduleName);

        $this->info('Verifying...');
        $ok = true;

        $row = DB::connection($mainConn)->table('modules')->where('name', $moduleName)->first();
        if (!$row) {
            $this->error("  [FAIL] modules table (on {$mainConn}): no row for name={$moduleName}");
            $ok = false;
        } elseif (!(bool) $row->installed) {
            $this->error("  [FAIL] modules table: installed is false for {$moduleName}");
            $ok = false;
        } else {
            $this->info("  [OK] modules table ({$mainConn}): {$moduleName} has installed=1");
        }

        try {
            $userCount = DB::connection($moduleConn)->table('users')->count();
            $this->line("  users table ({$moduleConn}): {$userCount} row(s)");
            $user = User::on($moduleConn)->where('email', ModuleSchemaService::SUPERADMIN_EMAIL)->first();
            if (!$user) {
                $this->error("  [FAIL] users table (on {$moduleConn}): no user " . ModuleSchemaService::SUPERADMIN_EMAIL);
                $ok = false;
            } else {
                $this->info("  [OK] users table ({$moduleConn}): superadmin user exists (id={$user->id})");
            }
        } catch (\Throwable $e) {
            $this->error("  [FAIL] users table ({$moduleConn}): " . $e->getMessage());
            $ok = false;
        }

        if ($ok) {
            $this->info('All checks passed.');
            return self::SUCCESS;
        }
        $this->warn('Some checks failed.');
        return self::FAILURE;
    }
}

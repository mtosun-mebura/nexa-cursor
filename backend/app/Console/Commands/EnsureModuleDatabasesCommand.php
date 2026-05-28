<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Services\ModuleDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnsureModuleDatabasesCommand extends Command
{
    protected $signature = 'modules:ensure-databases
                            {module? : Optioneel: alleen deze module (bijv. taxi of skillmatching)}';

    protected $description = 'Zorg dat elke geïnstalleerde module zijn database of schema heeft, afhankelijk van MODULE_DATABASE_STRATEGY.';

    public function handle(ModuleDatabaseService $dbService): int
    {
        $strategy = $dbService->getStrategy();

        if ($strategy === 'single') {
            $this->info('Strategy=single: alle tabellen staan in de hoofddatabase. Niets te doen.');

            return self::SUCCESS;
        }

        $moduleName = $this->argument('module');
        $modules = $moduleName
            ? (Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower($moduleName)])->pluck('name')->toArray())
            : Module::where('installed', true)->pluck('name')->toArray();

        if (empty($modules)) {
            $this->warn($moduleName ? "Geen geïnstalleerde module gevonden met naam: {$moduleName}." : 'Geen geïnstalleerde modules.');

            return self::SUCCESS;
        }

        foreach ($modules as $name) {
            if ($strategy === 'schema') {
                $this->ensureSchema($dbService, $name);
            } elseif ($strategy === 'database') {
                $this->ensureDatabase($dbService, $name);
            }
        }

        return self::SUCCESS;
    }

    private function ensureSchema(ModuleDatabaseService $dbService, string $name): void
    {
        $schemaName = $dbService->getModuleSchemaName($name);

        if ($dbService->moduleSchemaExists($name)) {
            $this->info("Schema \"{$schemaName}\" bestaat al in de hoofddatabase.");
        } else {
            $this->info("Schema \"{$schemaName}\" aanmaken voor module {$name}...");
            try {
                $dbService->setupModuleSchema($name);
                $this->info('  → Schema aangemaakt.');
            } catch (\Throwable $e) {
                $this->error('  → Fout: '.$e->getMessage());

                return;
            }
        }

        try {
            $dbService->ensureModuleStorageReady($name);
            $this->info('  → Vereiste module-tabellen gecontroleerd/aangevuld.');
        } catch (\Throwable $e) {
            $this->error('  → Tabellen/migraties: '.$e->getMessage());
        }
    }

    private function ensureDatabase(ModuleDatabaseService $dbService, string $name): void
    {
        $dbName = $dbService->getModuleDatabaseName($name);
        $exists = false;
        if (config('database.default') === 'pgsql') {
            $exists = DB::selectOne('SELECT 1 FROM pg_database WHERE datname = ?', [$dbName]) !== null;
        } elseif (in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            $r = DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);
            $exists = ! empty($r);
        }

        if ($exists) {
            $this->info("Database {$dbName} bestaat al.");
        } else {
            $this->info("Database {$dbName} aanmaken voor module {$name}...");
            try {
                $dbService->setupModuleDatabase($name);
                $this->info('  → Database aangemaakt.');
            } catch (\Throwable $e) {
                $this->error('  → Fout: '.$e->getMessage());

                return;
            }
        }

        try {
            $dbService->ensureModuleStorageReady($name);
            $this->info('  → Vereiste module-tabellen gecontroleerd/aangevuld.');
        } catch (\Throwable $e) {
            $this->error('  → Tabellen/migraties: '.$e->getMessage());
        }
    }
}

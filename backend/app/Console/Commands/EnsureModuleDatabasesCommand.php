<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Services\ModuleDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EnsureModuleDatabasesCommand extends Command
{
    protected $signature = 'modules:ensure-databases
                            {module? : Optioneel: alleen deze module (bijv. taxiroyaal of skillmatching)}';

    protected $description = 'Zorg dat elke geïnstalleerde module een eigen database heeft (nexa_<module>). Maakt ontbrekende DBs aan.';

    public function handle(ModuleDatabaseService $dbService): int
    {
        if (config('module_database.use_single_database', false)) {
            $this->info('Single-database mode is aan (MODULE_USE_SINGLE_DATABASE). Geen aparte module-databases; alle tabellen staan in de hoofddatabase.');

            return self::SUCCESS;
        }
        if (! $dbService->supportsModuleDatabases()) {
            $this->error('Module-databases worden alleen ondersteund bij MySQL of PostgreSQL.');

            return self::FAILURE;
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

                continue;
            }

            $this->info("Database {$dbName} aanmaken voor module {$name}...");
            try {
                $dbService->setupModuleDatabase($name);
                $this->info('  → Klaar.');
            } catch (\Throwable $e) {
                $this->error('  → Fout: '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}

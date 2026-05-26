<?php

namespace App\Console\Commands;

use App\Models\Module as ModuleModel;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ModulesMigrateCommand extends Command
{
    protected $signature = 'modules:migrate
                            {module=taxi : Module-key (zoals in modules.name / getName(), bv. taxi)}
                            {--status : Alleen overzicht tonen, geen migrate}';

    protected $description = 'Draait incrementele Laravel-migraties op de module-connection en toont waar tabellen staan.';

    public function handle(ModuleManager $moduleManager, ModuleDatabaseService $dbService): int
    {
        $strategy = $dbService->getStrategy();

        if ($strategy === 'single') {
            $this->warn('Strategy=single: module-tabellen staan in de hoofddatabase. Gebruik `php artisan migrate` voor de default connection.');

            return self::SUCCESS;
        }

        $arg = (string) $this->argument('module');
        $module = $moduleManager->loadModule($arg);
        if ($module === null) {
            $this->error("Module \"{$arg}\" niet gevonden (map App\\Modules\\…\\Module).");

            return self::FAILURE;
        }

        $key = strtolower(trim($module->getName()));
        $row = ModuleModel::whereRaw('LOWER(name) = ?', [$key])->first();
        if (! $row || ! (bool) $row->installed) {
            $this->error("Module \"{$key}\" is niet geïnstalleerd (modules.installed).");

            return self::FAILURE;
        }

        $conn = $dbService->getModuleConnectionName($key);
        $this->line('');
        $this->info("Strategy: <fg=cyan>{$strategy}</>");

        if ($strategy === 'schema') {
            $schemaName = $dbService->getModuleSchemaName($key);
            $mainDb = config('database.connections.'.config('database.default').'.database');
            $this->info("Database: <fg=cyan>{$mainDb}</> (hoofd-DB)");
            $this->info("Schema:   <fg=cyan>{$schemaName}</>");
        } else {
            $dbName = $dbService->getModuleDatabaseName($key);
            $this->info("Database: <fg=cyan>{$dbName}</>");
        }

        $this->line("Connection: <fg=cyan>{$conn}</>");
        $this->line('');

        $dbService->registerConnection($key);

        if ($this->option('status')) {
            $this->printTableInventory($conn, $strategy, $dbService, $key);

            return self::SUCCESS;
        }

        $moduleManager->syncModuleMigrationsToDisk($module);

        try {
            $message = $moduleManager->runModuleMigrationsNow($key);
            if ($message !== null) {
                $this->warn($message);
            } else {
                $this->info('Incrementele module-migraties uitgevoerd.');
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->printTableInventory($conn, $strategy, $dbService, $key);

        return self::SUCCESS;
    }

    private function printTableInventory(string $conn, string $strategy, ModuleDatabaseService $dbService, string $moduleKey): void
    {
        if ($strategy === 'schema') {
            $schemaName = $dbService->getModuleSchemaName($moduleKey);
            $this->info("Tabellen in schema \"{$schemaName}\":");
            try {
                $rows = DB::connection($conn)->select("
                    SELECT table_name
                    FROM information_schema.tables
                    WHERE table_schema = ? AND table_type = 'BASE TABLE'
                    ORDER BY table_name
                ", [$schemaName]);
            } catch (\Throwable $e) {
                $this->error('Kon tabellen niet ophalen: '.$e->getMessage());

                return;
            }
            if ($rows === []) {
                $this->warn('Geen tabellen gevonden. Voer uit: php artisan modules:ensure-databases '.$moduleKey);

                return;
            }
            foreach ($rows as $r) {
                $this->line("  {$schemaName}.{$r->table_name}");
            }
        } else {
            $this->info("Tabellen op module-connection \"{$conn}\":");
            try {
                $rows = DB::connection($conn)->select("
                    SELECT table_schema, table_name
                    FROM information_schema.tables
                    WHERE table_type = 'BASE TABLE'
                      AND table_schema NOT IN ('pg_catalog', 'information_schema')
                    ORDER BY table_schema, table_name
                ");
            } catch (\Throwable $e) {
                $this->error('Kon tabellen niet ophalen: '.$e->getMessage());

                return;
            }
            if ($rows === []) {
                $this->warn('Geen tabellen gevonden.');

                return;
            }
            foreach ($rows as $r) {
                $this->line(sprintf('  %s.%s', $r->table_schema, $r->table_name));
            }
        }
        $this->line('');
        $this->info('Totaal: '.count($rows).' tabel(len).');
    }
}

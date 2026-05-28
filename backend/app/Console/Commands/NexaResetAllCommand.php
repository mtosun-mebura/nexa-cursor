<?php

namespace App\Console\Commands;

use App\Services\MainDatabaseModuleTablePruner;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use Database\Seeders\ApplicationBootstrapSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Volledige lokale/reset-omgeving: alle nexa_* module-databases droppen,
 * hoofddatabase opnieuw migreren, ApplicationBootstrapSeeder (superadmin + dropdowns).
 */
class NexaResetAllCommand extends Command
{
    protected $signature = 'nexa:reset-all
                            {--force : Bevestig zonder prompt (verplicht)}
                            {--install=* : Module(na) om na reset te installeren (bijv. --install=taxi --install=skillmatching)}
                            {--no-seed : Alleen migrate:fresh, geen ApplicationBootstrapSeeder}';

    protected $description = 'Schone lei: dropt alle nexa_* module-DB\'s, migrate:fresh op hoofddatabase, seed superadmin + referentiedata.';

    public function handle(ModuleDatabaseService $dbService, ModuleManager $moduleManager): int
    {
        if (! $this->option('force')) {
            $this->error('Destructief. Voeg --force toe om te bevestigen.');

            return self::FAILURE;
        }

        $driver = config('database.default');
        $mainDb = (string) config("database.connections.{$driver}.database");

        $this->warn('Dit wist alle data in de hoofddatabase en alle ontdekte nexa_* module-databases.');
        $this->line('Hoofddatabase (migrate:fresh): '.$mainDb);

        Artisan::call('optimize:clear');
        $this->line(trim(Artisan::output()));

        if ($dbService->usesSingleStrategy()) {
            $this->warn('MODULE_DATABASE_STRATEGY=single: er worden geen aparte module-databases gedropt.');

            return $this->runMigrateFreshSeedAndInstall($dbService, $moduleManager);
        }

        if ($dbService->usesSchemaStrategy()) {
            $this->warn('MODULE_DATABASE_STRATEGY=schema: module-data staat in PG-schema\'s in de hoofddatabase; alleen migrate:fresh op hoofd-DB.');

            return $this->runMigrateFreshSeedAndInstall($dbService, $moduleManager);
        }

        if (! $dbService->supportsModuleDatabases()) {
            $this->warn('Geen ondersteunde module-databases voor driver '.$driver.'; alleen migrate:fresh + seed op hoofd-DB.');

            return $this->runMigrateFreshSeedAndInstall($dbService, $moduleManager);
        }

        $toDrop = $this->discoverModuleDatabaseNames($mainDb, $driver);
        if ($toDrop === []) {
            $this->info('Geen extra nexa_* databases gevonden om te droppen (alleen hoofd: '.$mainDb.').');
        } else {
            $this->info('Te verwijderen module-database(s):');
            foreach ($toDrop as $name) {
                $this->line('  - '.$name);
            }
            foreach ($toDrop as $dbName) {
                try {
                    $dbService->dropStandaloneDatabaseIfExists($dbName);
                    $this->info('Verwijderd: '.$dbName);
                } catch (\Throwable $e) {
                    $this->error('Kon '.$dbName.' niet droppen: '.$e->getMessage());

                    return self::FAILURE;
                }
            }
        }

        return $this->runMigrateFreshSeedAndInstall($dbService, $moduleManager);
    }

    /**
     * @return list<string>
     */
    private function discoverModuleDatabaseNames(string $mainDb, string $driver): array
    {
        if ($driver === 'pgsql') {
            $rows = DB::select(
                "SELECT datname FROM pg_database WHERE datistemplate = false AND datname LIKE 'nexa\\_%' ESCAPE '\\' AND datname <> ? ORDER BY datname",
                [$mainDb]
            );

            return array_values(array_map(static fn ($r) => $r->datname, $rows));
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $rows = DB::select(
                'SELECT SCHEMA_NAME AS n FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME LIKE ? AND SCHEMA_NAME <> ? ORDER BY SCHEMA_NAME',
                ['nexa\_%', $mainDb]
            );

            return array_values(array_map(static fn ($r) => $r->n, $rows));
        }

        return [];
    }

    private function runMigrateFreshSeedAndInstall(ModuleDatabaseService $dbService, ModuleManager $moduleManager): int
    {
        if ($dbService->usesDatabaseStrategy()) {
            $pruned = app(MainDatabaseModuleTablePruner::class)->prune();
            if ($pruned > 0) {
                $this->info("Hoofd-DB: {$pruned} overbodige module-tabel(len) verwijderd (zie config module_database.main_database_prune_tables).");
            }
        }

        $this->info('php artisan migrate:fresh --force …');
        $exit = Artisan::call('migrate:fresh', ['--force' => true]);
        $this->line(trim(Artisan::output()));
        if ($exit !== 0) {
            $this->error('migrate:fresh mislukt (exit '.$exit.').');

            return self::FAILURE;
        }

        if (! $this->option('no-seed')) {
            $this->info('Database\\Seeders\\ApplicationBootstrapSeeder (rollen, superadmin, categorieën, pipeline, thema\'s, formulieren, betalingen) …');
            Artisan::call('db:seed', [
                '--class' => ApplicationBootstrapSeeder::class,
                '--force' => true,
            ]);
            $this->line(trim(Artisan::output()));
        }

        $install = $this->option('install');
        if ($install !== []) {
            if (! $dbService->supportsModuleDatabases() || ! $dbService->usesDatabaseStrategy()) {
                $this->warn('--install wordt overgeslagen (alleen bij MODULE_DATABASE_STRATEGY=database).');

                return self::SUCCESS;
            }
            foreach ($install as $moduleName) {
                $moduleName = trim((string) $moduleName);
                if ($moduleName === '') {
                    continue;
                }
                $this->info('Module installeren: '.$moduleName);
                try {
                    $moduleManager->installModule($moduleName);
                    $this->info('  → '.$moduleName.' geïnstalleerd.');
                } catch (\Throwable $e) {
                    $this->error('Installatie '.$moduleName.' mislukt: '.$e->getMessage());

                    return self::FAILURE;
                }
            }
        }

        $this->info('Klaar. Superadmin: zie App\\Services\\ModuleSchemaService (e-mail/wachtwoord).');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\ModuleDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetupSkillmatchingDatabaseCommand extends Command
{
    protected $signature = 'modules:setup-skillmatching-database
                            {--fresh : Bestaande nexa_skillmatching eerst droppen en opnieuw aanmaken}';

    protected $description = 'Richt nexa_skillmatching in en kopieer alle tabelinhoud uit nexa (public) naar nexa_skillmatching.';

    public function handle(ModuleDatabaseService $dbService): int
    {
        if (config('database.default') !== 'pgsql') {
            $this->error('Deze command is alleen voor PostgreSQL.');
            return self::FAILURE;
        }

        $moduleName = 'skillmatching';
        $dbName = $dbService->getModuleDatabaseName($moduleName);

        $exists = DB::selectOne("SELECT 1 FROM pg_database WHERE datname = ?", [$dbName]) !== null;

        if ($exists && $this->option('fresh')) {
            $this->warn("Database {$dbName} wordt verwijderd en opnieuw aangemaakt...");
            $dbService->dropDatabase($moduleName);
            $exists = false;
        }

        if (!$exists) {
            $this->info("Database {$dbName} aanmaken en migraties draaien...");
            $dbService->createDatabase($moduleName);
            $dbService->registerConnection($moduleName);
            $dbService->runMigrations($moduleName);

            $this->info("Data kopiëren van nexa (public) naar {$dbName}...");
            try {
                $dbService->copyDataFromDefaultToModule($moduleName);
            } catch (\Throwable $e) {
                $this->error("Kopiëren mislukt: " . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->info("Database {$dbName} bestaat al. Gebruik --fresh om deze te droppen en opnieuw in te richten met data uit nexa.");
            $dbService->registerConnection($moduleName);
        }

        $this->info("Superadmin (m.tosun@mebura.nl) seeden in {$dbName}...");
        $dbService->seedSuperAdmin($moduleName);

        $this->info("Klaar. Database {$dbName} is ingericht.");
        return self::SUCCESS;
    }
}

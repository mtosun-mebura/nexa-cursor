<?php

namespace App\Console\Commands;

use App\Services\MainDatabaseModuleTablePruner;
use Illuminate\Console\Command;

/**
 * Verwijdert op de hoofd-DB (nexa) tabellen die bij losse module-DB's horen
 * (MODULE_DATABASE_STRATEGY=database). Zie config/module_database.php → main_database_prune_tables.
 */
class NexaPruneMainModuleTablesCommand extends Command
{
    protected $signature = 'nexa:prune-main-module-tables
                            {--force : Bevestig zonder prompt (verplicht)}';

    protected $description = 'Drop module-only tabellen (taxi/skillmatching) op de hoofddatabase bij losse module-DB\'s.';

    public function handle(MainDatabaseModuleTablePruner $pruner): int
    {
        if (! $this->option('force')) {
            $this->error('Destructief. Voeg --force toe om te bevestigen.');

            return self::FAILURE;
        }

        $dbService = app(\App\Services\ModuleDatabaseService::class);
        if (! $dbService->usesDatabaseStrategy()) {
            $this->warn('Alleen relevant bij MODULE_DATABASE_STRATEGY=database; bij schema/single is er niets te schonen op public.');

            return self::SUCCESS;
        }

        $count = $pruner->prune();
        if ($count === 0) {
            $this->info('Geen van de geconfigureerde tabellen aanwezig op de hoofd-DB (al opgeschoond of nooit aangemaakt).');

            return self::SUCCESS;
        }

        $this->info("Verwijderd: {$count} tabel(len).");

        return self::SUCCESS;
    }
}

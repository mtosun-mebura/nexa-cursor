<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Verwijdert tabellen op de hoofd-DB die alleen bij module-sets horen (taxiroyaal, skillmatching)
 * bij MODULE_DATABASE_STRATEGY=database. Bij schema horen module-tabellen in nexa_* schema's.
 *
 * Niet droppen: tabellen die ook door aparte migraties of door shared op de hoofd-DB blijven
 * (o.a. job_titles via 2025_12_11).
 */
class MainDatabaseModuleTablePruner
{
    /**
     * @return int Aantal verwijderde tabellen
     */
    public function prune(): int
    {
        $dbService = app(ModuleDatabaseService::class);
        if ($dbService->usesSingleStrategy() || $dbService->usesSchemaStrategy()) {
            return 0;
        }

        $driver = config('database.default');
        if (! in_array($driver, ['pgsql', 'mysql', 'mariadb'], true)) {
            return 0;
        }

        $tables = config('module_database.main_database_prune_tables', []);
        if ($tables === []) {
            return 0;
        }

        $existing = [];
        foreach ($tables as $t) {
            if (Schema::hasTable($t)) {
                $existing[] = $t;
            }
        }

        if ($existing === []) {
            return 0;
        }

        $this->dropTables($driver, $existing);
        Log::info('MainDatabaseModuleTablePruner: verwijderd van hoofd-DB: '.implode(', ', $existing));

        return count($existing);
    }

    /**
     * @param  list<string>  $tables
     */
    protected function dropTables(string $driver, array $tables): void
    {
        if ($driver === 'pgsql') {
            $quoted = array_map(static fn (string $t): string => '"'.str_replace('"', '""', $t).'"', $tables);
            DB::statement('DROP TABLE IF EXISTS '.implode(', ', $quoted).' CASCADE');

            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $quoted = array_map(static fn (string $t): string => '`'.str_replace('`', '``', $t).'`', $tables);
            DB::statement('DROP TABLE IF EXISTS '.implode(', ', $quoted));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}

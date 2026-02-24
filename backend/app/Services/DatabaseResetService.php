<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Leegt alle tabellen en herstelt alleen de super admin (m.tosun@mebura.nl) met alle rechten.
 */
class DatabaseResetService
{
    public function resetAndRestoreSuperAdmin(): void
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        Schema::disableForeignKeyConstraints();

        try {
            $tables = $this->getTableNames($connection, $driver);
            foreach ($tables as $tableName) {
                $connection->getSchemaBuilder()->disableForeignKeyConstraints();
                $connection->statement($this->truncateStatement($driver, $tableName));
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        Artisan::call('db:seed', [
            '--class' => \Database\Seeders\RoleSeeder::class,
            '--force' => true,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function getTableNames($connection, string $driver): array
    {
        if ($driver === 'mysql') {
            $result = $connection->select('SHOW TABLES');
            $key = 'Tables_in_' . $connection->getDatabaseName();
            return array_map(fn ($row) => $row->{$key}, $result);
        }
        if ($driver === 'pgsql') {
            $result = $connection->select(
                "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT LIKE 'pg_%' ORDER BY tablename"
            );
            return array_map(fn ($row) => $row->tablename, $result);
        }
        return [];
    }

    private function truncateStatement(string $driver, string $tableName): string
    {
        $connection = DB::connection();
        $wrapped = $connection->getSchemaGrammar()->wrapTable($tableName);
        if ($driver === 'pgsql') {
            return 'TRUNCATE TABLE ' . $wrapped . ' RESTART IDENTITY CASCADE';
        }
        return 'TRUNCATE TABLE ' . $wrapped;
    }
}

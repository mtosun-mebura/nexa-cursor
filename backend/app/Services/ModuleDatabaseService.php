<?php

namespace App\Services;

use App\Database\Pre2026Baseline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Beheert per-module standalone databases: aanmaken, migraties, superadmin-seed.
 * Elke module krijgt een eigen database (bijv. nexa_taxiroyaal) met alle standaard
 * tabellen en de superadmin m.tosun@mebura.nl.
 */
class ModuleDatabaseService
{
    /**
     * Database-naam voor een module (bijv. TaxiRoyaal -> nexa_taxiroyaal).
     */
    public function getModuleDatabaseName(string $moduleName): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));

        return 'nexa_'.$name;
    }

    /**
     * Laravel connection naam voor een module (bijv. TaxiRoyaal -> module_taxiroyaal).
     */
    public function getModuleConnectionName(string $moduleName): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));

        return 'module_'.$name;
    }

    /**
     * Slug voor upload-paden en asset-mappen (bijv. TaxiRoyaal -> taxiroyaal).
     * Gebruik in storage-paden: modules/{slug}/website/hero, etc.
     */
    public function getModuleUploadSlug(string $moduleName): string
    {
        return strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));
    }

    /**
     * Of de huidige driver ondersteund wordt (mysql, pgsql) én we geen single-DB mode gebruiken.
     * Bij single-DB (MODULE_USE_SINGLE_DATABASE=true) blijven alle tabellen in de hoofddatabase.
     */
    public function supportsModuleDatabases(): bool
    {
        if (config('module_database.use_single_database', false)) {
            return false;
        }
        $driver = config('database.default');

        return in_array($driver, ['mysql', 'mariadb', 'pgsql'], true);
    }

    /**
     * Maak een nieuwe database aan voor de module.
     * Als de database al bestaat (bijv. na een mislukte install), wordt deze eerst verwijderd en opnieuw aangemaakt.
     */
    public function createDatabase(string $moduleName): void
    {
        $dbName = $this->getModuleDatabaseName($moduleName);
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            $exists = DB::selectOne('SELECT 1 FROM pg_database WHERE datname = ?', [$dbName]);
            if ($exists) {
                $this->terminateAndDropDatabase($moduleName);
            }
            $quoted = '"'.str_replace('"', '""', $dbName).'"';
            DB::statement("CREATE DATABASE {$quoted}");
            Log::info("Module database created: {$dbName} (pgsql)");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $r = DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);
            if (! empty($r)) {
                $this->terminateAndDropDatabase($moduleName);
            }
            $quoted = '`'.str_replace('`', '``', $dbName).'`';
            DB::statement("CREATE DATABASE {$quoted}");
            Log::info("Module database created: {$dbName} (mysql)");

            return;
        }

        throw new \RuntimeException("Module databases are not supported for driver: {$driver}");
    }

    /**
     * Registreer een Laravel-connection voor de module-database.
     */
    public function registerConnection(string $moduleName): void
    {
        $connName = $this->getModuleConnectionName($moduleName);
        $dbName = $this->getModuleDatabaseName($moduleName);
        $default = config('database.default');
        $config = config("database.connections.{$default}");

        if (! is_array($config)) {
            throw new \RuntimeException("Default database connection '{$default}' not found.");
        }

        $config['database'] = $dbName;
        Config::set("database.connections.{$connName}", $config);
        Log::info("Module connection registered: {$connName} -> {$dbName}");
    }

    /**
     * Draai alleen de migraties die bij deze module horen: core + shared + module-specifiek.
     * Zo krijgt nexa_taxiroyaal geen skillmatching-tabellen en nexa_skillmatching geen vehicles/ride_requests.
     */
    public function runMigrations(string $moduleName): void
    {
        $conn = $this->getModuleConnectionName($moduleName);
        $sets = config('module_migrations.module_migration_sets', [])[$moduleName]
            ?? config('module_migrations.default_set', ['core', 'shared']);

        Pre2026Baseline::runForSetsOnConnection($sets, $conn);

        Log::info("Migrations run on module database: {$conn}");
    }

    /**
     * Seed superadmin (m.tosun@mebura.nl) in de module-database: rechten/rollen via RoleSeeder, gebruiker direct op de module-connection.
     */
    public function seedSuperAdmin(string $moduleName): void
    {
        $conn = $this->getModuleConnectionName($moduleName);
        $previousDefault = config('database.default');

        try {
            Config::set('database.default', $conn);
            Config::set('module_database.seeding_module', $moduleName);
            $seeder = app()->make(\Database\Seeders\RoleSeeder::class);
            $seeder->run();
        } finally {
            Config::set('database.default', $previousDefault);
            Config::set('module_database.seeding_module', null);
        }

        $this->ensureSuperAdminUserOnConnection($conn);
        Log::info("Superadmin seeded in module database: {$conn}");
    }

    /**
     * Zorg dat de superadmin-gebruiker op de gegeven connection bestaat (direct op die DB).
     */
    protected function ensureSuperAdminUserOnConnection(string $conn): void
    {
        $email = \App\Services\ModuleSchemaService::SUPERADMIN_EMAIL;
        $exists = DB::connection($conn)->table('users')->where('email', $email)->exists();
        if ($exists) {
            return;
        }
        $now = now();
        DB::connection($conn)->table('users')->insert([
            'email' => $email,
            'password' => Hash::make(\App\Services\ModuleSchemaService::SUPERADMIN_PASSWORD),
            'first_name' => 'Mehmet',
            'last_name' => 'Tosun',
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = DB::connection($conn)->table('users')->where('email', $email)->value('id');
        $roleId = DB::connection($conn)->table('roles')->where('name', 'super-admin')->where('guard_name', 'web')->value('id');
        if ($roleId) {
            DB::connection($conn)->table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => \App\Models\User::class,
                'model_id' => $userId,
            ]);
        }
    }

    /**
     * Volledige setup: database aanmaken, connection registreren, migraties, superadmin. Aanroepen bij module install.
     */
    public function setupModuleDatabase(string $moduleName): void
    {
        if (! $this->supportsModuleDatabases()) {
            throw new \RuntimeException('Module databases are only supported for MySQL/MariaDB or PostgreSQL.');
        }

        $this->createDatabase($moduleName);
        $this->registerConnection($moduleName);
        $this->runMigrations($moduleName);
        $this->seedSuperAdmin($moduleName);
    }

    /**
     * Kopieer alle data uit de standaard-database (nexa public) naar de module-database.
     * Alleen PostgreSQL. Gebruik na runMigrations() voor een module die de inhoud van nexa moet overnemen (bijv. skillmatching).
     */
    public function copyDataFromDefaultToModule(string $moduleName): void
    {
        if (config('database.default') !== 'pgsql') {
            throw new \RuntimeException('copyDataFromDefaultToModule is alleen ondersteund voor PostgreSQL.');
        }

        $conn = $this->getModuleConnectionName($moduleName);
        $this->registerConnection($moduleName);

        $result = DB::connection()->select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename");
        $tables = array_column($result, 'tablename');

        DB::connection($conn)->statement('SET session_replication_role = replica');

        try {
            foreach ($tables as $table) {
                if ($table === 'migrations') {
                    continue;
                }
                $count = DB::connection()->table($table)->count();
                if ($count === 0) {
                    continue;
                }
                $chunkSize = 500;
                $source = DB::connection()->table($table);
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'id')) {
                    $source = $source->orderBy('id');
                } else {
                    $source = $source->orderBy(DB::raw('ctid'));
                }
                $source->chunk($chunkSize, function ($rows) use ($conn, $table) {
                    $values = $rows->map(fn ($row) => (array) $row)->toArray();
                    if (! empty($values)) {
                        DB::connection($conn)->table($table)->insert($values);
                    }
                });
                Log::info("Module DB copy: {$table} copied to {$conn}");
            }
        } finally {
            DB::connection($conn)->statement('SET session_replication_role = DEFAULT');
        }

        $this->resetSequences($conn);
        Log::info("Data copy to module database {$conn} completed.");
    }

    /**
     * Reset PostgreSQL sequences in de gegeven connection na data-copy.
     */
    protected function resetSequences(string $conn): void
    {
        $columns = DB::connection($conn)->select("
            SELECT table_name, column_name, column_default
            FROM information_schema.columns
            WHERE table_schema = 'public' AND column_default LIKE 'nextval(%'
        ");
        foreach ($columns as $col) {
            $table = $col->table_name;
            $columnName = $col->column_name;
            $max = DB::connection($conn)->table($table)->max($columnName);
            if ($max !== null) {
                $seq = DB::connection($conn)->selectOne('SELECT pg_get_serial_sequence(?, ?) as seq', ['public.'.$table, $columnName]);
                if (! empty($seq->seq)) {
                    DB::connection($conn)->statement('SELECT setval(?, ?)', [$seq->seq, $max + 1]);
                }
            }
        }
    }

    /**
     * Verwijder de module-database (bij uninstall). Wordt altijd aangeroepen bij Verwijderen van een module.
     */
    public function dropDatabase(string $moduleName): void
    {
        $connName = $this->getModuleConnectionName($moduleName);
        Config::set("database.connections.{$connName}", null);

        $this->terminateAndDropDatabase($moduleName);
    }

    /**
     * Alleen de fysieke drop uitvoeren (zonder config te clearen). Gebruikt bij uninstall en bij createDatabase als DB al bestaat.
     */
    protected function terminateAndDropDatabase(string $moduleName): void
    {
        $dbName = $this->getModuleDatabaseName($moduleName);
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            DB::statement('SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()', [$dbName]);
            $quoted = '"'.str_replace('"', '""', $dbName).'"';
            DB::statement("DROP DATABASE IF EXISTS {$quoted}");
            Log::info("Module database dropped: {$dbName} (pgsql)");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $quoted = '`'.str_replace('`', '``', $dbName).'`';
            DB::statement("DROP DATABASE IF EXISTS {$quoted}");
            Log::info("Module database dropped: {$dbName} (mysql)");

            return;
        }

        throw new \RuntimeException("Module databases are not supported for driver: {$driver}");
    }
}

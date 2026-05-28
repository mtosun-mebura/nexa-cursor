<?php

namespace App\Services;

use App\Database\Pre2026Baseline;
use App\Support\ModuleMigrationPathResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Beheert per-module database-isolatie:
 *
 *  strategy=single   → alles in de hoofd-DB (public schema)
 *  strategy=schema   → 1 DB, per module een eigen PG-schema (nexa_taxi, …) + connection met search_path
 *  strategy=database → per module een eigen database (nexa_taxi, nexa_skillmatching, …) [legacy]
 */
class ModuleDatabaseService
{
    /** @var array<string, true> */
    private array $storageReadyChecked = [];

    // ------------------------------------------------------------------
    //  Helpers: namen, slugs
    // ------------------------------------------------------------------

    public function getModuleDatabaseName(string $moduleName): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));

        return 'nexa_'.$name;
    }

    public function getModuleConnectionName(string $moduleName): string
    {
        $name = strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));

        return 'module_'.$name;
    }

    public function getModuleSchemaName(string $moduleName): string
    {
        $module = app(ModuleManager::class)->loadModule($moduleName);

        if ($module !== null && $module->getSchemaName() !== null) {
            return $module->getSchemaName();
        }

        return 'nexa_'.strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));
    }

    public function getModuleUploadSlug(string $moduleName): string
    {
        return strtolower(preg_replace('/[^a-z0-9_]/i', '_', $moduleName));
    }

    // ------------------------------------------------------------------
    //  Strategy helpers
    // ------------------------------------------------------------------

    public function getStrategy(): string
    {
        return (string) config('module_database.strategy', 'schema');
    }

    /** Alles in 1 database, module-tabellen in aparte PG-schemas? */
    public function usesSchemaStrategy(): bool
    {
        return $this->getStrategy() === 'schema';
    }

    /** Per module een eigen database? (legacy) */
    public function usesDatabaseStrategy(): bool
    {
        return $this->getStrategy() === 'database';
    }

    /** Alles in de hoofd-DB, geen isolatie? */
    public function usesSingleStrategy(): bool
    {
        return $this->getStrategy() === 'single';
    }

    /**
     * Backward compat: oude code checkt supportsModuleDatabases().
     * True als we schema- of database-strategy gebruiken op een ondersteunde driver.
     */
    public function supportsModuleDatabases(): bool
    {
        if ($this->usesSingleStrategy()) {
            return false;
        }
        $driver = config('database.default');

        return in_array($driver, ['mysql', 'mariadb', 'pgsql'], true);
    }

    // ------------------------------------------------------------------
    //  Connection registratie
    // ------------------------------------------------------------------

    public function registerConnection(string $moduleName): void
    {
        $connName = $this->getModuleConnectionName($moduleName);
        if (Config::has("database.connections.{$connName}")) {
            return;
        }

        $default = config('database.default');
        $config = config("database.connections.{$default}");

        if (! is_array($config)) {
            throw new \RuntimeException("Default database connection '{$default}' not found.");
        }

        if ($this->usesSchemaStrategy()) {
            // Zelfde database, maar eigen schema via search_path
            $schemaName = $this->getModuleSchemaName($moduleName);
            $config['search_path'] = $schemaName.',public';
        } elseif ($this->usesDatabaseStrategy()) {
            // Eigen database
            $dbName = $this->getModuleDatabaseName($moduleName);
            $config['database'] = $dbName;

            if (($config['driver'] ?? null) === 'pgsql') {
                $config = $this->applyPgsqlModuleSearchPath($moduleName, $connName, $config);
            }
        }

        Config::set("database.connections.{$connName}", $config);
        DB::purge($connName);
    }

    // ------------------------------------------------------------------
    //  Schema-strategy: setup / drop
    // ------------------------------------------------------------------

    /**
     * Maak het PG-schema aan in de hoofd-database en draai alleen module-specifieke migraties.
     * Core+shared tabellen (users, companies, …) staan al in public en zijn bereikbaar via search_path.
     */
    public function setupModuleSchema(string $moduleName): void
    {
        $schemaName = $this->getModuleSchemaName($moduleName);
        $quoted = '"'.str_replace('"', '""', $schemaName).'"';
        DB::statement("CREATE SCHEMA IF NOT EXISTS {$quoted}");
        Log::info("Module schema created: {$schemaName}");

        $this->registerConnection($moduleName);
        $this->runModuleOnlyMigrations($moduleName);
        $this->applySchemaFixups($moduleName);
    }

    /**
     * Draai alleen de module-specifieke Pre2026Baseline-set (bv. taxiroyaal),
     * zonder core+shared (die staan al in public schema).
     *
     * tolerateErrors=true vangt fouten op van migraties die shared-tabellen (users, invoices, …)
     * proberen te ALTERen — die kolommen bestaan al in public.
     */
    public function runModuleOnlyMigrations(string $moduleName): void
    {
        $conn = $this->getModuleConnectionName($moduleName);
        $sets = config('module_migrations.schema_only_sets', [])[$moduleName] ?? [];

        if ($sets === []) {
            Log::info("Geen schema_only_sets voor module {$moduleName}; overslaan Pre2026Baseline.");

            return;
        }

        $skipped = Pre2026Baseline::runForSetsOnConnection($sets, $conn, tolerateErrors: true);

        if ($skipped !== []) {
            $names = array_column($skipped, 'basename');
            Log::info('Module baseline: '.count($skipped).' stap(pen) overgeslagen op '.$conn.': '.implode(', ', $names));
        }

        Log::info("Module-only migrations run on {$conn} (sets: ".implode(',', $sets).')');
    }

    /**
     * Post-processing na de baseline: hernoem kolommen, verwijder stub-tabellen, etc.
     * Nodig omdat sommige shared-set migraties (bv. categories→branches rename) niet draaien in schema-only mode.
     */
    private function applySchemaFixups(string $moduleName): void
    {
        $conn = $this->getModuleConnectionName($moduleName);
        $db = DB::connection($conn);
        $schemaName = $this->getModuleSchemaName($moduleName);

        if ($moduleName === 'skillmatching') {
            // categories→branches rename: in de shared set wordt category_id→branch_id hernoemd.
            // In schema-only mode draait die migratie niet, dus doen we het hier.
            if ($this->columnExistsInSchema($db, $schemaName, 'vacancies', 'category_id')
                && ! $this->columnExistsInSchema($db, $schemaName, 'vacancies', 'branch_id')) {
                $db->statement('ALTER TABLE "'.$schemaName.'".vacancies RENAME COLUMN category_id TO branch_id');
                Log::info("Schema fixup ({$moduleName}): category_id → branch_id in vacancies");
            }
        }
    }

    private function columnExistsInSchema($db, string $schema, string $table, string $column): bool
    {
        $result = $db->selectOne(
            'SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$schema, $table, $column]
        );

        return $result !== null;
    }

    public function dropModuleSchema(string $moduleName): void
    {
        $schemaName = $this->getModuleSchemaName($moduleName);
        $quoted = '"'.str_replace('"', '""', $schemaName).'"';
        DB::statement("DROP SCHEMA IF EXISTS {$quoted} CASCADE");

        $connName = $this->getModuleConnectionName($moduleName);
        Config::set("database.connections.{$connName}", null);
        Log::info("Module schema dropped: {$schemaName}");
    }

    public function moduleSchemaExists(string $moduleName): bool
    {
        $schemaName = $this->getModuleSchemaName($moduleName);

        return DB::selectOne(
            'SELECT 1 FROM information_schema.schemata WHERE schema_name = ?',
            [$schemaName]
        ) !== null;
    }

    // ------------------------------------------------------------------
    //  Database-strategy (legacy): setup / drop
    // ------------------------------------------------------------------

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

    public function setupModuleDatabase(string $moduleName): void
    {
        if (! $this->usesDatabaseStrategy()) {
            throw new \RuntimeException('setupModuleDatabase is only for strategy=database.');
        }

        $this->createDatabase($moduleName);
        $this->registerConnection($moduleName);
        $this->runMigrations($moduleName);
        $this->seedSuperAdmin($moduleName);
    }

    public function dropDatabase(string $moduleName): void
    {
        $connName = $this->getModuleConnectionName($moduleName);
        Config::set("database.connections.{$connName}", null);
        $this->terminateAndDropDatabase($moduleName);
    }

    protected function terminateAndDropDatabase(string $moduleName): void
    {
        $this->dropStandaloneDatabaseIfExists($this->getModuleDatabaseName($moduleName));
    }

    public function dropStandaloneDatabaseIfExists(string $dbName): void
    {
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            DB::statement('SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()', [$dbName]);
            $quoted = '"'.str_replace('"', '""', $dbName).'"';
            DB::statement("DROP DATABASE IF EXISTS {$quoted}");
            Log::info("Standalone database dropped: {$dbName} (pgsql)");

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $quoted = '`'.str_replace('`', '``', $dbName).'`';
            DB::statement("DROP DATABASE IF EXISTS {$quoted}");
            Log::info("Standalone database dropped: {$dbName} (mysql)");

            return;
        }

        throw new \RuntimeException("Standalone database drop not supported for driver: {$driver}");
    }

    // ------------------------------------------------------------------
    //  Migrations
    // ------------------------------------------------------------------

    public function runMigrations(string $moduleName): void
    {
        $conn = $this->getModuleConnectionName($moduleName);
        $sets = config('module_migrations.module_migration_sets', [])[$moduleName]
            ?? config('module_migrations.default_set', ['core', 'shared']);

        Pre2026Baseline::runForSetsOnConnection($sets, $conn);

        Log::info("Migrations run on module connection: {$conn}");
    }

    /**
     * Zorg dat vereiste module-tabellen bestaan (schema + incrementele migraties).
     * Wordt o.a. aangeroepen vóór taxi-admin requests als vehicles/ride_requests ontbreken.
     *
     * @param  list<string>|null  $requiredTables
     */
    public function ensureModuleStorageReady(string $moduleName, ?array $requiredTables = null): void
    {
        $slug = strtolower(trim($moduleName));
        if (isset($this->storageReadyChecked[$slug])) {
            return;
        }
        $this->storageReadyChecked[$slug] = true;

        if ($this->usesSingleStrategy()) {
            return;
        }

        $requiredTables ??= config("module_migrations.required_tables.{$slug}", []);
        if (! is_array($requiredTables) || $requiredTables === []) {
            return;
        }

        $this->registerConnection($slug);
        $conn = $this->getModuleConnectionName($slug);

        $missing = array_values(array_filter(
            $requiredTables,
            fn (string $table) => ! Schema::connection($conn)->hasTable($table)
        ));
        if ($missing === []) {
            return;
        }

        Log::info('Module storage: ontbrekende tabellen, migraties starten', [
            'module' => $slug,
            'connection' => $conn,
            'missing' => $missing,
        ]);

        $module = app(ModuleManager::class)->loadModule($slug);
        if ($module !== null) {
            app(ModuleManager::class)->syncModuleMigrationsToDisk($module);
        }

        if ($this->usesSchemaStrategy()) {
            if (! $this->moduleSchemaExists($slug)) {
                $this->setupModuleSchema($slug);
            } else {
                try {
                    $this->runModuleOnlyMigrations($slug);
                } catch (\Throwable $e) {
                    Log::warning("Module baseline retry ({$slug}): ".$e->getMessage());
                }
            }
        } elseif ($this->usesDatabaseStrategy()) {
            if (! $this->moduleStandaloneDatabaseExists($slug)) {
                $this->setupModuleDatabase($slug);
            }
        }

        $this->runIncrementalModuleMigrations($slug);

        $stillMissing = array_values(array_filter(
            $requiredTables,
            fn (string $table) => ! Schema::connection($conn)->hasTable($table)
        ));
        if ($stillMissing !== []) {
            throw new \RuntimeException(
                'Module-tabellen ontbreken op '.$conn.': '.implode(', ', $stillMissing)
                .'. Voer uit: php artisan modules:ensure-databases '.$slug
                .' of php artisan modules:migrate '.$slug
            );
        }
    }

    public function moduleStandaloneDatabaseExists(string $moduleName): bool
    {
        $dbName = $this->getModuleDatabaseName($moduleName);
        $driver = config('database.default');

        if ($driver === 'pgsql') {
            return DB::selectOne('SELECT 1 FROM pg_database WHERE datname = ?', [$dbName]) !== null;
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $r = DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);

            return ! empty($r);
        }

        return false;
    }

    public function runIncrementalModuleMigrations(string $moduleName): bool
    {
        $this->registerConnection($moduleName);
        $conn = $this->getModuleConnectionName($moduleName);
        $canonical = strtolower(trim($moduleName));
        $relative = ModuleMigrationPathResolver::pathForModule($canonical);
        $fullPath = base_path($relative);
        if (! is_dir($fullPath)) {
            Log::info("Geen module-migratiemap voor incrementele run: {$relative}");

            return false;
        }
        $files = glob($fullPath.'/*.php');
        if ($files === false || $files === []) {
            Log::info("Geen migratiebestanden in {$relative}; niets te draaien.");

            return false;
        }
        $exitCode = Artisan::call('migrate', [
            '--database' => $conn,
            '--path' => $relative,
            '--force' => true,
        ]);
        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()));
        }
        Log::info("Incrementele module-migraties op {$conn}: {$relative}");

        return true;
    }

    // ------------------------------------------------------------------
    //  Superadmin seed
    // ------------------------------------------------------------------

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
        Log::info("Superadmin seeded in module connection: {$conn}");
    }

    protected function ensureSuperAdminUserOnConnection(string $conn): void
    {
        $email = ModuleSchemaService::SUPERADMIN_EMAIL;
        $exists = DB::connection($conn)->table('users')->where('email', $email)->exists();
        if ($exists) {
            return;
        }
        $now = now();
        DB::connection($conn)->table('users')->insert([
            'email' => $email,
            'password' => Hash::make(ModuleSchemaService::SUPERADMIN_PASSWORD),
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

    // ------------------------------------------------------------------
    //  Data copy (legacy, database strategy)
    // ------------------------------------------------------------------

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
        Log::info("Data copy to module connection {$conn} completed.");
    }

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

    // ------------------------------------------------------------------
    //  Legacy PG helper (database strategy)
    // ------------------------------------------------------------------

    protected function applyPgsqlModuleSearchPath(string $moduleName, string $connName, array $config): array
    {
        $module = app(ModuleManager::class)->loadModule($moduleName);
        $schemaName = $module !== null ? $module->getSchemaName() : null;
        if (! $schemaName) {
            $config['search_path'] = $config['search_path'] ?? 'public';

            return $config;
        }

        Config::set("database.connections.{$connName}", $config);
        DB::purge($connName);

        try {
            $legacyPublicCore = DB::connection($connName)->selectOne(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users' LIMIT 1"
            ) !== null;

            if ($legacyPublicCore) {
                $config['search_path'] = 'public';
                Log::info("Module PG search_path=public (legacy) voor {$connName}");
            } else {
                $quoted = '"'.str_replace('"', '""', $schemaName).'"';
                DB::connection($connName)->statement("CREATE SCHEMA IF NOT EXISTS {$quoted}");
                $config['search_path'] = $schemaName.',public';
                Log::info("Module PG search_path={$schemaName},public voor {$connName}");
            }
        } catch (\Throwable $e) {
            Log::warning("Module PG search_path fallback naar public: {$e->getMessage()}");
            $config['search_path'] = $config['search_path'] ?? 'public';
        }

        return $config;
    }
}

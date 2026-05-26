<?php

namespace App\Services;

use App\Models\Module as ModuleModel;
use App\Modules\Base\Module;
use App\Support\ModuleMigrationPathResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class ModuleManager
{
    protected array $modules = [];

    protected array $loadedModules = [];

    /**
     * Discover alle modules
     */
    public function discoverModules(): array
    {
        $modules = [];

        // 1. Discover modules in app/Modules
        $appModulesPath = app_path('Modules');
        if (File::exists($appModulesPath)) {
            $modules = array_merge($modules, $this->scanDirectory($appModulesPath, 'internal'));
        }

        // 2. Discover modules in modules/ directory (external plugins)
        $modulesPath = base_path('modules');
        if (File::exists($modulesPath)) {
            $modules = array_merge($modules, $this->scanDirectory($modulesPath, 'external'));
        }

        // 3. Clean up database records for modules that no longer exist
        $this->cleanupOrphanedModules($modules);

        return $modules;
    }

    /**
     * Remove database records for modules that no longer exist on disk
     */
    protected function cleanupOrphanedModules(array $discoveredModules): void
    {
        if (! Schema::hasTable('modules')) {
            return;
        }

        $discoveredNames = array_column($discoveredModules, 'name');

        // Find modules in database that are not in discovered modules
        $orphanedModules = ModuleModel::whereNotIn('name', $discoveredNames)->get();

        foreach ($orphanedModules as $orphaned) {
            // Only remove if the directory doesn't exist
            $possiblePaths = [
                app_path('Modules/'.$orphaned->name),
                app_path('Modules/'.ucfirst($orphaned->name)),
                base_path('modules/'.$orphaned->name),
                base_path('modules/'.ucfirst($orphaned->name)),
            ];

            $exists = false;
            foreach ($possiblePaths as $path) {
                if (File::exists($path) && File::exists($path.'/Module.php')) {
                    $exists = true;
                    break;
                }
            }

            // If directory doesn't exist, remove from database
            if (! $exists) {
                $orphaned->delete();
                Log::info("Removed orphaned module record: {$orphaned->name}");
            }
        }
    }

    /**
     * Scan directory voor modules
     */
    protected function scanDirectory(string $path, string $type): array
    {
        $modules = [];

        if (! File::exists($path)) {
            return $modules;
        }

        $directories = File::directories($path);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);

            // Skip Base directory
            if ($moduleName === 'Base') {
                continue;
            }

            $moduleFile = $directory.'/Module.php';

            if (File::exists($moduleFile)) {
                try {
                    // Try to load with original name first
                    $module = $this->loadModule($moduleName);
                    if (! $module) {
                        // Try with lowercase
                        $module = $this->loadModule(strtolower($moduleName));
                    }

                    if ($module) {
                        // Use the actual module name from the module itself
                        $actualModuleName = $module->getName();
                        $modules[] = [
                            'name' => $actualModuleName,
                            'display_name' => $module->getDisplayName(),
                            'version' => $module->getVersion(),
                            'description' => $module->getDescription(),
                            'icon' => $module->getIcon(),
                            'type' => $type,
                            'path' => $directory,
                            'installed' => $this->isInstalled($actualModuleName),
                            'active' => $this->isActive($actualModuleName),
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Error loading module {$moduleName}: ".$e->getMessage());
                }
            }
        }

        return $modules;
    }

    /**
     * Resolveer PSR-4 class voor App\Modules\{Dir}\Module wanneer mapnaam ≠ module key
     * (bijv. map NexaTaxi, {@see Module::getName()} `taxi`).
     */
    protected function resolveInternalModuleClass(string $moduleName): ?string
    {
        $base = app_path('Modules');
        if (! File::isDirectory($base)) {
            return null;
        }
        foreach (File::directories($base) as $dir) {
            $folder = basename($dir);
            if (strcasecmp($folder, $moduleName) !== 0) {
                continue;
            }
            $class = "App\\Modules\\{$folder}\\Module";
            if (class_exists($class)) {
                return $class;
            }
        }

        $needle = strtolower($moduleName);
        foreach (File::directories($base) as $dir) {
            $folder = basename($dir);
            if ($folder === 'Base') {
                continue;
            }
            $class = "App\\Modules\\{$folder}\\Module";
            if (! class_exists($class)) {
                continue;
            }
            try {
                $instance = new $class;
                if (strtolower($instance->getName()) === $needle) {
                    return $class;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Load een module
     */
    public function loadModule(string $moduleName): ?Module
    {
        // Normalize module name (try both original and capitalized)
        $normalizedName = ucfirst($moduleName);

        if (isset($this->loadedModules[$moduleName])) {
            return $this->loadedModules[$moduleName];
        }

        if (isset($this->loadedModules[$normalizedName])) {
            return $this->loadedModules[$normalizedName];
        }

        // Try internal modules first with original name
        $moduleClass = "App\\Modules\\{$moduleName}\\Module";
        if (! class_exists($moduleClass)) {
            // Try with capitalized name
            $moduleClass = "App\\Modules\\{$normalizedName}\\Module";
        }

        if (! class_exists($moduleClass)) {
            $resolved = $this->resolveInternalModuleClass($moduleName);
            if ($resolved !== null) {
                $moduleClass = $resolved;
            }
        }

        if (! class_exists($moduleClass)) {
            // Try external modules
            $moduleClass = "Modules\\{$moduleName}\\Module";
            if (! class_exists($moduleClass)) {
                $moduleClass = "Modules\\{$normalizedName}\\Module";
            }
        }

        if (! class_exists($moduleClass)) {
            return null;
        }

        try {
            $module = new $moduleClass;
            $this->loadedModules[$moduleName] = $module;
            $this->modules[$moduleName] = $module;

            return $module;
        } catch (\Exception $e) {
            Log::error("Error instantiating module {$moduleName}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get alle geïnstalleerde modules
     */
    public function getInstalledModules(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return [];
        }

        return ModuleModel::where('installed', true)
            ->get()
            ->map(function ($module) {
                return $this->loadModule($module->name);
            })
            ->filter()
            ->toArray();
    }

    /**
     * Get alle geactiveerde modules
     */
    public function getActiveModules(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return [];
        }

        return ModuleModel::where('installed', true)
            ->where('active', true)
            ->get()
            ->map(function ($module) {
                return $this->loadModule($module->name);
            })
            ->filter()
            ->toArray();
    }

    /**
     * Of er minstens één actieve module is (frontend wordt dan getoond i.p.v. coming soon)
     */
    public function hasAnyActiveModule(): bool
    {
        $active = $this->getActiveModules();

        return count($active) > 0;
    }

    /**
     * Check of module geïnstalleerd is
     */
    public function isInstalled(string $moduleName): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return false;
        }

        return ModuleModel::where('name', $moduleName)
            ->where('installed', true)
            ->exists();
    }

    /**
     * Check of module actief is
     */
    public function isActive(string $moduleName): bool
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return false;
        }

        return ModuleModel::where('name', $moduleName)
            ->where('installed', true)
            ->where('active', true)
            ->exists();
    }

    /**
     * Kopieer migraties uit app/Modules/…/Migrations naar database/migrations/modules/{slug}.
     * De modulemap is de bron; bestaande bestanden worden overschreven zodat updates meekomen.
     */
    public function syncModuleMigrationsToDisk(Module $module): void
    {
        $migrationsPath = $module->getMigrationsPath();
        if (! $migrationsPath || ! File::isDirectory($migrationsPath)) {
            return;
        }
        $slug = strtolower(trim($module->getName()));
        $targetDir = database_path("migrations/modules/{$slug}");
        if (! File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }
        $migrationFiles = File::glob($migrationsPath.'/*.php');
        foreach ($migrationFiles as $migrationFile) {
            $targetFile = $targetDir.'/'.basename($migrationFile);
            File::copy($migrationFile, $targetFile);
        }
    }

    /**
     * Install een module
     */
    public function installModule(string $moduleName): bool
    {
        $mainConnection = config('database.main_connection', config('database.default'));

        $module = $this->loadModule($moduleName);
        if (! $module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        // Check dependencies
        $dependencies = $module->getDependencies();
        foreach ($dependencies as $dep) {
            if (! $this->isInstalled($dep)) {
                throw new \Exception("Dependency {$dep} is niet geïnstalleerd");
            }
        }

        // Module-migraties eerst kopiëren zodat ze in de module-DB (of schema) gedraaid kunnen worden
        $this->syncModuleMigrationsToDisk($module);

        // Eerst op hoofddatabase zetten: module als geïnstalleerd (zodat UI direct "Geïnstalleerd" toont na refresh)
        $this->saveModuleAsInstalled($moduleName, $module, $mainConnection);

        $dbService = app(ModuleDatabaseService::class);
        $strategy = $dbService->getStrategy();

        if ($strategy === 'single') {
            try {
                $this->runModuleMigrationsOnDefaultConnection($moduleName);
            } catch (\Throwable $e) {
                $this->saveModuleAsUninstalled($moduleName, $mainConnection);
                throw new \Exception("Module-migraties op hoofddatabase mislukt voor {$moduleName}: ".$e->getMessage(), 0, $e);
            }
        } elseif ($strategy === 'schema') {
            try {
                $dbService->setupModuleSchema($moduleName);
            } catch (\Throwable $e) {
                $this->saveModuleAsUninstalled($moduleName, $mainConnection);
                throw new \Exception("Module-schema kon niet worden opgezet voor {$moduleName}: ".$e->getMessage(), 0, $e);
            }
            try {
                $migrationSlug = strtolower(trim($module->getName()));
                if (! $dbService->runIncrementalModuleMigrations($migrationSlug)) {
                    Log::info("Geen incrementele migratiebestanden voor module {$migrationSlug}.");
                }
            } catch (\Throwable $e) {
                $this->saveModuleAsUninstalled($moduleName, $mainConnection);
                throw new \Exception("Incrementele module-migraties mislukt voor {$moduleName}: ".$e->getMessage(), 0, $e);
            }
        } elseif ($strategy === 'database') {
            try {
                $dbService->setupModuleDatabase($moduleName);
            } catch (\Throwable $e) {
                $this->saveModuleAsUninstalled($moduleName, $mainConnection);
                throw new \Exception("Module-database kon niet worden opgezet voor {$moduleName}: ".$e->getMessage(), 0, $e);
            }
            try {
                $migrationSlug = strtolower(trim($module->getName()));
                if (! $dbService->runIncrementalModuleMigrations($migrationSlug)) {
                    Log::info("Geen incrementele migratiebestanden voor module {$migrationSlug}.");
                }
            } catch (\Throwable $e) {
                $this->saveModuleAsUninstalled($moduleName, $mainConnection);
                throw new \Exception("Incrementele module-migraties mislukt voor {$moduleName}: ".$e->getMessage(), 0, $e);
            }
        }

        // Thema's uit backend/themas/ kopiëren naar public en naar deze module (frontend direct zichtbaar bij activatie)
        $themeCopy = app(ThemeCopyService::class);
        if ($themeCopy->hasThemasSource()) {
            $themeCopy->copyThemesToPublic();
            $themeCopy->copyThemesToModule($moduleName);
        }

        // Register permissions
        // Bij strategy=database: op de module-connection (eigen permissions-tabel).
        // Bij strategy=schema of single: op de default connection (public.permissions).
        $permissions = $module->registerPermissions();
        if ($strategy === 'database' && $permissions !== []) {
            $conn = $dbService->getModuleConnectionName($moduleName);
            $previousDefault = Config::get('database.default');
            try {
                Config::set('database.default', $conn);
                foreach ($permissions as $permission) {
                    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
                }
            } finally {
                Config::set('database.default', $previousDefault);
            }
        } elseif ($permissions !== []) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
            }
        }

        // Call module install
        $module->install();

        // Record nogmaals op hoofddatabase bijwerken (zelfde connection)
        $this->saveModuleAsInstalled($moduleName, $module, $mainConnection);

        return true;
    }

    /**
     * Module-record op de hoofddatabase opslaan als geïnstalleerd (directe DB-write op de gegeven connection).
     */
    protected function saveModuleAsInstalled(string $moduleName, Module $module, string $connection): void
    {
        $table = DB::connection($connection)->table('modules');
        $row = $table->where('name', $moduleName)->first();
        $data = [
            'display_name' => $module->getDisplayName(),
            'version' => $module->getVersion(),
            'description' => $module->getDescription(),
            'icon' => $module->getIcon(),
            'installed' => true,
            'active' => false,
            'updated_at' => now(),
        ];
        if ($row) {
            $existingConfig = [];
            if (isset($row->configuration) && $row->configuration !== null && $row->configuration !== '') {
                $decoded = is_string($row->configuration) ? json_decode($row->configuration, true) : $row->configuration;
                $existingConfig = is_array($decoded) ? $decoded : [];
            }
            if (! array_key_exists('dashboard_link_visible', $existingConfig)) {
                $existingConfig['dashboard_link_visible'] = '0';
            }
            $data['configuration'] = json_encode($existingConfig);
            $table->where('name', $moduleName)->update($data);
        } else {
            $table->insert(array_merge($data, [
                'name' => $moduleName,
                'configuration' => json_encode(['dashboard_link_visible' => '0']),
                'created_at' => now(),
            ]));
        }
    }

    /**
     * Module-record op hoofddatabase op niet-geïnstalleerd zetten (bijv. na mislukte DB-setup).
     */
    protected function saveModuleAsUninstalled(string $moduleName, string $connection): void
    {
        DB::connection($connection)->table('modules')
            ->where('name', $moduleName)
            ->update(['installed' => false, 'active' => false, 'updated_at' => now()]);
    }

    /**
     * Activate een module
     */
    public function activateModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (! $module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        if (! $this->isInstalled($moduleName)) {
            throw new \Exception("Module {$moduleName} is niet geïnstalleerd");
        }

        $module->activate();

        ModuleModel::where('name', $moduleName)->update(['active' => true]);

        // Clear cache
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');

        return true;
    }

    /**
     * Deactivate een module
     */
    public function deactivateModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (! $module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        $module->deactivate();

        ModuleModel::where('name', $moduleName)->update(['active' => false]);

        // Clear cache
        \Artisan::call('config:clear');
        \Artisan::call('route:clear');
        \Artisan::call('view:clear');

        return true;
    }

    /**
     * Uninstall een module
     */
    public function uninstallModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (! $module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        // Deactivate first
        if ($this->isActive($moduleName)) {
            $this->deactivateModule($moduleName);
        }

        // Call module uninstall
        $module->uninstall();

        $dbService = app(ModuleDatabaseService::class);
        $strategy = $dbService->getStrategy();

        if ($strategy === 'single') {
            // Geen aparte DB/schema om te droppen; module-tabellen blijven in hoofddatabase
        } elseif ($strategy === 'schema') {
            try {
                $dbService->dropModuleSchema($moduleName);
            } catch (\Throwable $e) {
                Log::warning('Module schema drop bij uninstall: '.$e->getMessage(), ['module' => $moduleName]);
                throw new \Exception('Module verwijderd, maar schema kon niet worden gedropt: '.$e->getMessage(), 0, $e);
            }
        } elseif ($strategy === 'database') {
            try {
                $dbService->dropDatabase($moduleName);
            } catch (\Throwable $e) {
                Log::warning('Module database drop bij uninstall: '.$e->getMessage(), ['module' => $moduleName]);
                throw new \Exception('Module verwijderd, maar database kon niet worden gedropt: '.$e->getMessage(), 0, $e);
            }
        }

        // Update database (module als niet geïnstalleerd gemarkeerd)
        ModuleModel::where('name', $moduleName)->update([
            'installed' => false,
            'active' => false,
        ]);

        return true;
    }

    /**
     * Voer module-migraties opnieuw uit (incrementeel waar mogelijk; geen volledige Pre2026-baseline op module-DB).
     * Gebruik na wijziging van MODULE_USE_SINGLE_DATABASE in .env (cache legen: php artisan config:clear).
     *
     * @return string|null  Informatieve melding voor de UI (bijv. geen incrementele migraties); null bij succes zonder extra tekst.
     */
    public function runModuleMigrationsNow(string $moduleName): ?string
    {
        $module = $this->loadModule($moduleName);
        if (! $module) {
            throw new \InvalidArgumentException("Module {$moduleName} niet gevonden.");
        }
        $moduleModel = ModuleModel::whereRaw('LOWER(name) = ?', [strtolower($moduleName)])->first();
        if (! $moduleModel || ! $moduleModel->installed) {
            throw new \RuntimeException('Module is niet geïnstalleerd.');
        }

        $dbService = app(ModuleDatabaseService::class);
        $strategy = $dbService->getStrategy();

        if ($strategy === 'single') {
            $this->runModuleMigrationsOnDefaultConnection($moduleName);

            return null;
        }

        if ($strategy === 'schema') {
            if (! $dbService->moduleSchemaExists($moduleName)) {
                $schemaName = $dbService->getModuleSchemaName($moduleName);
                throw new \RuntimeException(
                    "Module-schema \"{$schemaName}\" bestaat nog niet. Voer eerst uit: php artisan modules:ensure-databases {$moduleName}"
                );
            }
            $dbService->registerConnection($moduleName);
            $this->syncModuleMigrationsToDisk($module);
            $migrationSlug = strtolower(trim($module->getName()));
            if (! $dbService->runIncrementalModuleMigrations($migrationSlug)) {
                return "Geen incrementele migraties gevonden (map database/migrations/modules/{$migrationSlug} ontbreekt of is leeg).";
            }

            return null;
        }

        if ($strategy === 'database') {
            $dbName = $dbService->getModuleDatabaseName($moduleName);
            if (! $this->moduleStandaloneDatabaseExists($dbName)) {
                throw new \RuntimeException(
                    "Module-database \"{$dbName}\" bestaat nog niet. Voer eerst uit: php artisan modules:ensure-databases {$moduleName}"
                );
            }
            $this->syncModuleMigrationsToDisk($module);
            $migrationSlug = strtolower(trim($module->getName()));
            if (! $dbService->runIncrementalModuleMigrations($migrationSlug)) {
                return "Geen incrementele migraties gevonden (map database/migrations/modules/{$migrationSlug} ontbreekt of is leeg).";
            }

            return null;
        }

        $this->runModuleMigrationsOnDefaultConnection($moduleName);

        return null;
    }

    /**
     * Of een losse module-database (nexa_*) al bestaat op de server.
     */
    private function moduleStandaloneDatabaseExists(string $dbName): bool
    {
        if (config('database.default') === 'pgsql') {
            return DB::selectOne('SELECT 1 FROM pg_database WHERE datname = ?', [$dbName]) !== null;
        }
        if (in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            $r = DB::select('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$dbName]);

            return ! empty($r);
        }

        return false;
    }

    /**
     * Run alleen de module-migraties (database/migrations/modules/{slug}) op de standaard-DB.
     * Gebruikt bij MODULE_USE_SINGLE_DATABASE: alle tabellen in één database.
     */
    protected function runModuleMigrationsOnDefaultConnection(string $moduleName): void
    {
        $module = $this->loadModule($moduleName);
        if ($module) {
            $this->syncModuleMigrationsToDisk($module);
        }
        $canonical = strtolower(trim($module ? $module->getName() : $moduleName));
        $relative = ModuleMigrationPathResolver::pathForModule($canonical);
        $fullPath = base_path($relative);
        if (! is_dir($fullPath)) {
            Log::info("No module migrations path for single-DB mode: {$relative}");

            return;
        }
        $exitCode = Artisan::call('migrate', [
            '--path' => $relative,
            '--force' => true,
        ]);
        if ($exitCode !== 0) {
            throw new \RuntimeException('Module-migraties mislukt: '.trim(Artisan::output()));
        }
        Log::info("Module migrations run on default connection for: {$moduleName}");
    }

    /**
     * Run module-migraties (database/migrations/modules/{name}/*.php) binnen het module-schema (alleen pgsql).
     *
     * @param  string  $schemaName  Schema waarin de migraties draaien (uit Module::getSchemaName() of module_{naam})
     */
    protected function runModuleMigrationsInSchema(string $moduleName, string $schemaName): void
    {
        $schemaService = app(ModuleSchemaService::class);
        if (! $schemaService->supportsModuleSchemas()) {
            return;
        }
        $module = $this->loadModule($moduleName);
        if ($module) {
            $this->syncModuleMigrationsToDisk($module);
        }
        $canonical = strtolower(trim($module ? $module->getName() : (string) $moduleName));
        $relative = ModuleMigrationPathResolver::pathForModule($canonical);
        $targetDir = base_path($relative);
        if (! File::exists($targetDir)) {
            return;
        }
        $files = File::glob($targetDir.'/*.php');
        sort($files);
        foreach ($files as $file) {
            $schemaService->runInSchema($schemaName, function () use ($file) {
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                }
            });
        }
    }
}

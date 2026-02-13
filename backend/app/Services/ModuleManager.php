<?php

namespace App\Services;

use App\Models\Module as ModuleModel;
use App\Modules\Base\Module;
use App\Services\ModuleSchemaService;
use App\Services\ThemeCopyService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
        if (!Schema::hasTable('modules')) {
            return;
        }

        $discoveredNames = array_column($discoveredModules, 'name');
        
        // Find modules in database that are not in discovered modules
        $orphanedModules = ModuleModel::whereNotIn('name', $discoveredNames)->get();
        
        foreach ($orphanedModules as $orphaned) {
            // Only remove if the directory doesn't exist
            $possiblePaths = [
                app_path('Modules/' . $orphaned->name),
                app_path('Modules/' . ucfirst($orphaned->name)),
                base_path('modules/' . $orphaned->name),
                base_path('modules/' . ucfirst($orphaned->name)),
            ];
            
            $exists = false;
            foreach ($possiblePaths as $path) {
                if (File::exists($path) && File::exists($path . '/Module.php')) {
                    $exists = true;
                    break;
                }
            }
            
            // If directory doesn't exist, remove from database
            if (!$exists) {
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
        
        if (!File::exists($path)) {
            return $modules;
        }

        $directories = File::directories($path);

        foreach ($directories as $directory) {
            $moduleName = basename($directory);
            
            // Skip Base directory
            if ($moduleName === 'Base') {
                continue;
            }
            
            $moduleFile = $directory . '/Module.php';

            if (File::exists($moduleFile)) {
                try {
                    // Try to load with original name first
                    $module = $this->loadModule($moduleName);
                    if (!$module) {
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
                    Log::error("Error loading module {$moduleName}: " . $e->getMessage());
                }
            }
        }

        return $modules;
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
        if (!class_exists($moduleClass)) {
            // Try with capitalized name
            $moduleClass = "App\\Modules\\{$normalizedName}\\Module";
        }
        
        if (!class_exists($moduleClass)) {
            // Try external modules
            $moduleClass = "Modules\\{$moduleName}\\Module";
            if (!class_exists($moduleClass)) {
                $moduleClass = "Modules\\{$normalizedName}\\Module";
            }
        }

        if (!class_exists($moduleClass)) {
            return null;
        }

        try {
            $module = new $moduleClass();
            $this->loadedModules[$moduleName] = $module;
            $this->modules[$moduleName] = $module;

            return $module;
        } catch (\Exception $e) {
            Log::error("Error instantiating module {$moduleName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get alle geïnstalleerde modules
     */
    public function getInstalledModules(): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
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
        if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
            return false;
        }

        return ModuleModel::where('name', $moduleName)
            ->where('installed', true)
            ->where('active', true)
            ->exists();
    }

    /**
     * Install een module
     */
    public function installModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (!$module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        // Check dependencies
        $dependencies = $module->getDependencies();
        foreach ($dependencies as $dep) {
            if (!$this->isInstalled($dep)) {
                throw new \Exception("Dependency {$dep} is niet geïnstalleerd");
            }
        }

        // PostgreSQL: per-module schema met basis-tabellen en superadmin m.tosun@mebura.nl / wachtwoord !
        $schemaService = app(ModuleSchemaService::class);
        if ($schemaService->supportsModuleSchemas()) {
            $schemaService->setupModuleSchema($moduleName);
        }

        // Thema's uit backend/themas/ kopiëren naar public en naar deze module (frontend direct zichtbaar bij activatie)
        $themeCopy = app(ThemeCopyService::class);
        if ($themeCopy->hasThemasSource()) {
            $themeCopy->copyThemesToPublic();
            $themeCopy->copyThemesToModule($moduleName);
        }

        // Run migrations (kopieer naar database/migrations/modules/{moduleName}/; module-migraties in schema bij pgsql)
        $migrationsPath = $module->getMigrationsPath();
        if ($migrationsPath && File::exists($migrationsPath)) {
            $targetDir = database_path("migrations/modules/{$moduleName}");
            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }
            $migrationFiles = File::glob($migrationsPath . '/*.php');
            foreach ($migrationFiles as $migrationFile) {
                $targetFile = $targetDir . '/' . basename($migrationFile);
                if (!File::exists($targetFile)) {
                    File::copy($migrationFile, $targetFile);
                }
            }
            if ($schemaService->supportsModuleSchemas()) {
                $this->runModuleMigrationsInSchema($moduleName);
            }
        }

        // Register permissions
        $permissions = $module->registerPermissions();
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Call module install
        $module->install();

        // Save to database
        ModuleModel::updateOrCreate(
            ['name' => $moduleName],
            [
                'display_name' => $module->getDisplayName(),
                'version' => $module->getVersion(),
                'description' => $module->getDescription(),
                'icon' => $module->getIcon(),
                'installed' => true,
                'active' => false, // Not active by default
            ]
        );

        return true;
    }

    /**
     * Activate een module
     */
    public function activateModule(string $moduleName): bool
    {
        $module = $this->loadModule($moduleName);
        if (!$module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        if (!$this->isInstalled($moduleName)) {
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
        if (!$module) {
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
        if (!$module) {
            throw new \Exception("Module {$moduleName} niet gevonden");
        }

        // Deactivate first
        if ($this->isActive($moduleName)) {
            $this->deactivateModule($moduleName);
        }

        // Call module uninstall
        $module->uninstall();

        // PostgreSQL: schema verwijderen
        $schemaService = app(ModuleSchemaService::class);
        if ($schemaService->supportsModuleSchemas()) {
            $schemaService->dropSchema($moduleName);
        }

        // Update database
        ModuleModel::where('name', $moduleName)->update([
            'installed' => false,
            'active' => false,
        ]);

        return true;
    }

    /**
     * Run module-migraties (database/migrations/modules/{name}/*.php) binnen het module-schema (alleen pgsql).
     */
    protected function runModuleMigrationsInSchema(string $moduleName): void
    {
        $schemaService = app(ModuleSchemaService::class);
        if (!$schemaService->supportsModuleSchemas()) {
            return;
        }
        $targetDir = database_path("migrations/modules/{$moduleName}");
        if (!File::exists($targetDir)) {
            return;
        }
        $schema = $schemaService->getSchemaName($moduleName);
        $files = File::glob($targetDir . '/*.php');
        sort($files);
        foreach ($files as $file) {
            $schemaService->runInSchema($schema, function () use ($file) {
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                }
            });
        }
    }
}

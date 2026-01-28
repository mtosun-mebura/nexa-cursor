<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Services\ModuleManager;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            // Check if modules table exists
            if (!\Illuminate\Support\Facades\Schema::hasTable('modules')) {
                return;
            }

            $moduleManager = app(ModuleManager::class);
            
            // Load alle actieve modules en registreer hun routes
            $activeModules = $moduleManager->getActiveModules();

            foreach ($activeModules as $module) {
                if ($module) {
                    $this->registerModuleRoutes($module);
                    $this->registerModuleViews($module);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if modules table doesn't exist yet
            // This allows migrations to run without errors
        }
    }

    protected function registerModuleRoutes($module): void
    {
        $moduleName = $module->getName();
        $routesPath = $module->getRoutesPath();

        if (!$routesPath || !is_dir($routesPath)) {
            return;
        }

        // Register web routes
        if (file_exists($routesPath . '/web.php')) {
            Route::middleware(['web', 'admin'])
                ->prefix("admin/{$moduleName}")
                ->name("admin.{$moduleName}.")
                ->group($routesPath . '/web.php');
        }

        // Register API routes
        if (file_exists($routesPath . '/api.php')) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix("api/{$moduleName}")
                ->name("api.{$moduleName}.")
                ->group($routesPath . '/api.php');
        }
    }

    protected function registerModuleViews($module): void
    {
        $viewsPath = $module->getViewsPath();
        
        if ($viewsPath && is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $module->getName());
        }
    }
}

<?php

namespace App\Providers;

use App\Models\Module as ModuleModel;
use App\Models\WebsitePage;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use App\Notifications\Channels\SmsChannel;
use App\Services\EnvService;
use App\Services\ModuleManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerModuleDatabaseConnections();
        $this->registerWebsitePageRouteBinding();
        $this->loadGoogleMapsApiKeyFromRootEnv();

        if ($this->app->environment('testing')) {
            $this->loadMigrationsFrom(database_path('migrations/shared'));
            if (is_dir(database_path('migrations/core'))) {
                $this->loadMigrationsFrom(database_path('migrations/core'));
            }
        }

        View::composer('frontend.layouts.website', function ($view) {
            if (!isset($view->getData()['googleMapsApiKey']) || $view->getData()['googleMapsApiKey'] === '') {
                $key = trim((string) (config('maps.api_key') ?? ''));
                if ($key === '') {
                    $key = app(EnvService::class)->getGoogleMapsApiKey();
                }
                $view->with('googleMapsApiKey', $key);
            }
            if (!array_key_exists('googleMapsMapId', $view->getData())) {
                $view->with('googleMapsMapId', app(EnvService::class)->getGoogleMapsMapId());
            }
            if (!array_key_exists('showSkillmatchingAppLinks', $view->getData())) {
                $view->with('showSkillmatchingAppLinks', app(ModuleManager::class)->isActive('skillmatching'));
            }
        });

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
        // Ensure Sanctum uses api guard by default for SPA/API
        config(['auth.defaults.guard' => 'api']);
        
        // Register SMS notification channel
        Notification::extend('sms', function ($app) {
            return new SmsChannel();
        });
        
        // Register ModuleServiceProvider
        $this->app->register(\App\Providers\ModuleServiceProvider::class);
    }

    /**
     * WebsitePage uit module-DB oplossen wanneer ?module= in de request staat (zelfstandige module-databases).
     */
    private function registerWebsitePageRouteBinding(): void
    {
        Route::bind('website_page', function (string $value) {
            $module = request()->query('module');
            $dbService = $this->app->make(ModuleDatabaseService::class);
            if ($module && is_string($module) && trim($module) !== '' && $dbService->supportsModuleDatabases()) {
                $conn = $dbService->getModuleConnectionName(trim($module));
                return WebsitePage::on($conn)->with('theme')->findOrFail($value);
            }
            return WebsitePage::with('theme')->findOrFail($value);
        });
    }

    /**
     * Registreer database-connections voor alle geïnstalleerde modules (nexa_taxiroyaal, nexa_skillmatching, …)
     * zodat o.a. TaxiRoyaal-admin op de module-DB kan werken.
     */
    private function registerModuleDatabaseConnections(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }
        $dbService = $this->app->make(ModuleDatabaseService::class);
        if (!$dbService->supportsModuleDatabases()) {
            return;
        }
        foreach (ModuleModel::where('installed', true)->pluck('name') as $name) {
            try {
                $dbService->registerConnection($name);
            } catch (\Throwable $e) {
                // Bij eerste request na install kan DB nog niet bestaan; negeer
            }
        }
    }

    /**
     * Laad GOOGLE_MAPS_API_KEY uit de root .env (projectroot) in config, zodat de key overal beschikbaar is.
     */
    private function loadGoogleMapsApiKeyFromRootEnv(): void
    {
        if (config('maps.api_key')) {
            return;
        }
        $rootEnv = \App\Services\EnvService::getRootEnvPath();
        if (!is_readable($rootEnv)) {
            return;
        }
        $lines = @file($rootEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }
            [$k, $value] = explode('=', $line, 2);
            if (trim($k) === 'GOOGLE_MAPS_API_KEY') {
                $value = trim($value);
                if (strlen($value) >= 2 && ($value[0] === '"' && $value[strlen($value) - 1] === '"' || $value[0] === "'" && $value[strlen($value) - 1] === "'")) {
                    $value = substr($value, 1, -1);
                }
                config(['maps.api_key' => trim($value)]);
                return;
            }
        }
    }
}

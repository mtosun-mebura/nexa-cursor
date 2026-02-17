<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use App\Notifications\Channels\SmsChannel;
use App\Services\EnvService;

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
        $this->loadGoogleMapsApiKeyFromRootEnv();

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

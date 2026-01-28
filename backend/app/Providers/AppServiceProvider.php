<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use App\Notifications\Channels\SmsChannel;

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
}

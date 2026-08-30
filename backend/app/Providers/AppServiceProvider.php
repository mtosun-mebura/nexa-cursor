<?php

namespace App\Providers;

use App\Models\Module as ModuleModel;
use App\Models\User;
use App\Models\WebsitePage;
use App\Notifications\Channels\SmsChannel;
use App\Services\EnvService;
use App\Services\ModuleDatabaseService;
use App\Services\WebsiteBuilderService;
use App\Support\Admin\AdminTenantScope;
use App\Support\DestructiveDatabaseGuard;
use App\Support\Tenancy\CentralDomains;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
        $this->usePublishedPostgresWhenDockerHostnameIsUnreachable();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Super-admin: tweede Gate::before (Spatie registreert de eerste bij het resolven van Gate).
        // Laravel voert before-callbacks in registratievolgorde uit; eerste die niet-null teruggeeft wint.
        // Spatie’s callback roept checkPermissionTo aan; bij geen match (null) volgt deze super-admin-bypass.
        $this->app->booted(function () {
            Gate::before(function ($user, $ability) {
                if ($user instanceof User && $user->isSuperAdmin()) {
                    return true;
                }

                return null;
            });
        });

        $this->registerDestructiveDatabaseGuard();
        $this->registerModuleDatabaseConnections();
        $this->registerWebsitePageRouteBinding();
        $this->syncMapsConfigFromSettings();
        $this->forceLocalDevRootUrlFromRequest();

        View::composer('admin.layouts.app', function ($view) {
            if (! auth()->check()) {
                return;
            }

            $scope = app(AdminTenantScope::class);
            $data = $view->getData();

            $tenantScopedActive = $data['adminTenantScopedActive']
                ?? $data['websitePagesTenantScopedActive']
                ?? $data['tenantScopedSettingsActive']
                ?? $data['moduleConfigTenantScopedActive']
                ?? $scope->isTenantScopedActive();

            $showNotice = ($data['adminShowTenantNotice'] ?? $scope->shouldShowTenantNotice())
                && ! ($data['adminTenantScopeNoticeSuppressed'] ?? false);

            $hideContent = $data['adminHideContentWithoutTenant'] ?? $scope->shouldHideContent();

            $view->with([
                'adminTenantScopedActive' => $tenantScopedActive,
                'adminShowTenantNotice' => $showNotice,
                'adminHideContentWithoutTenant' => $hideContent,
                'adminTenantScopeVariant' => $data['adminTenantScopeVariant'] ?? $scope->noticeVariant(),
            ]);
        });

        View::composer(['frontend.layouts.website', 'frontend.layouts.app', 'frontend.layouts.partials.header'], function ($view) {
            $data = $view->getData();
            if ($view->name() === 'frontend.layouts.website') {
                if (empty($data['isPreview']) && ($back = session('website_preview_admin_url'))) {
                    $view->with([
                        'isPreview' => true,
                        'previewEditUrl' => $back,
                    ]);
                }
                if (! isset($data['googleMapsApiKey']) || $data['googleMapsApiKey'] === '') {
                    $view->with('googleMapsApiKey', app(EnvService::class)->getGoogleMapsApiKey());
                }
                if (! array_key_exists('googleMapsMapId', $view->getData())) {
                    $view->with('googleMapsMapId', app(EnvService::class)->getGoogleMapsMapId());
                }
            }

            if (! array_key_exists('showSkillmatchingAppLinks', $data)) {
                $view->with(app(WebsiteBuilderService::class)->frontendPortalViewData($data));
            } elseif (! array_key_exists('showGuestSkillmatchingLinks', $data)) {
                $moduleName = app(WebsiteBuilderService::class)->resolvePublicFrontendModuleName();
                $view->with('showGuestSkillmatchingLinks', $moduleName !== 'taxi');
            }
        });

        // Geen globale URL::forceScheme('https'): dat dwingt ook bij een gewone HTTP-request
        // (bijv. http://192.168.178.116:8000) alle gegenereerde URL’s naar https om, terwijl
        // de container op :8000 geen TLS heeft → assets laden als https://…:8000/build/… en
        // de browser geeft net::ERR_CONNECTION_CLOSED. Achter een echte SSL-proxy volstaat
        // TrustProxies (bootstrap/app.php) + X-Forwarded-Proto om het juiste scheme te krijgen.

        // Geen globale default guard naar 'api' forceren: admin-login gebruikt expliciet web (AdminAuthController).
        // auth()->user() moet dezelfde sessie-user zijn als web, anders falen Spatie can()/hasRole() en krijg je 403.
        // Zet desnoods AUTH_GUARD=api in .env voor API-first apps (Sanctum-routes gebruiken meestal auth:sanctum).

        RateLimiter::for('taxi-driver-login', function ($request) {
            return Limit::perMinute(10)->by($request->ip().'|'.(string) $request->input('email'));
        });

        RateLimiter::for('taxi-contract-login', function ($request) {
            return Limit::perMinute(10)->by($request->ip().'|'.(string) $request->input('email'));
        });

        RateLimiter::for('taxi-app-login-code', function ($request) {
            return [
                Limit::perMinute(8)->by($request->ip()),
                Limit::perHour(8)->by($request->ip().'|'.strtolower((string) $request->input('email'))),
            ];
        });

        RateLimiter::for('taxi-driver-poll', function ($request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(120)->by('taxi-poll|'.$key);
        });

        RateLimiter::for('public-forms', function ($request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        Event::listen(MessageSending::class, function () {
            return app(\App\Services\NexaDemoAccountService::class)->shouldSuppressOutgoingMail() ? false : null;
        });

        // Register SMS notification channel
        Notification::extend('sms', function ($app) {
            return new SmsChannel;
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
            $dbService = $this->app->make(ModuleDatabaseService::class);
            $module = request()->query('module');
            // Bij POST/PUT kan ?module= soms ontbreken (proxy, redirect, bookmark); het formulier stuurt
            // altijd module_name mee → zelfde DB als bij bewerken, anders wordt de verkeerde connection
            // geüpdatet en lijkt o.a. show_in_menu niet opgeslagen.
            if ((! is_string($module) || trim($module) === '') && $dbService->supportsModuleDatabases()) {
                if (request()->isMethod('post') || request()->isMethod('put') || request()->isMethod('patch')) {
                    $fromBody = request()->input('module_name');
                    if (is_string($fromBody) && trim($fromBody) !== '') {
                        $module = trim($fromBody);
                    }
                }
            }
            if ($module && is_string($module) && trim($module) !== '' && $dbService->supportsModuleDatabases()) {
                $moduleTrim = trim($module);
                $conn = $dbService->getModuleConnectionName($moduleTrim);
                // Boot registreert alleen geïnstalleerde modules; ?module= kan eerder komen of ontbreken in de lijst.
                if (! Config::has("database.connections.{$conn}")) {
                    try {
                        $dbService->registerConnection($moduleTrim);
                    } catch (\Throwable) {
                        // Geen geldige module-DB-config: val terug op standaard-connection (geen 500).
                    }
                }
                if (Config::has("database.connections.{$conn}")) {
                    return WebsitePage::on($conn)->with('theme')->findOrFail($value);
                }
            }

            return WebsitePage::with('theme')->findOrFail($value);
        });
    }

    /**
     * Registreer database-connections voor alle geïnstalleerde modules (nexa_taxi, nexa_skillmatching, …)
     * zodat o.a. Nexa Taxi-admin op de module-DB kan werken.
     */
    private function registerModuleDatabaseConnections(): void
    {
        try {
            if (! Schema::hasTable('modules')) {
                return;
            }
        } catch (\Throwable) {
            // Geen bereikbare DB (bv. sqlite-bestand ontbreekt), migraties nog niet: skip zodat o.a. artisan view:clear werkt.
            return;
        }
        $dbService = $this->app->make(ModuleDatabaseService::class);
        if (! $dbService->supportsModuleDatabases()) {
            return;
        }
        $moduleNames = ModuleModel::where('installed', true)->pluck('name');
        foreach ($moduleNames as $name) {
            try {
                $dbService->registerConnection((string) $name);
            } catch (\Throwable) {
                // Bij eerste request na install kan DB nog niet bestaan; negeer
            }
        }
    }

    /**
     * Laad platform Maps-instellingen (Algemene configuraties) in config('maps.*').
     */
    private function syncMapsConfigFromSettings(): void
    {
        app(EnvService::class)->syncMapsConfig();
    }

    /**
     * LAN/mobiel dev: APP_URL is vaak localhost, maar de browser opent 192.168.x.x.
     * Zonder dit wijzen redirects en gegenereerde URL's naar localhost → geen sessie op de telefoon.
     */
    private function forceLocalDevRootUrlFromRequest(): void
    {
        if ($this->app->runningInConsole() || app()->isProduction()) {
            return;
        }

        $request = request();
        if ($request === null) {
            return;
        }

        $host = $request->getHost();
        if (! CentralDomains::isLocalDevEntryHost($host)) {
            return;
        }

        $root = $request->getScheme().'://'.$host;
        $port = $request->getPort();
        if ($port && ! in_array($port, [80, 443], true)) {
            $root .= ':'.$port;
        }

        URL::forceRootUrl($root);
    }

    /**
     * Artisan op de Mac: .env heeft DB_HOST=db (Compose-servicenaam). Die hostname bestaat
     * alleen in het Docker-netwerk. Postgres is lokaal gepubliceerd op 127.0.0.1:5432.
     */
    private function registerDestructiveDatabaseGuard(): void
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            DestructiveDatabaseGuard::abortIfBlocked($event->command);
        });
    }

    private function usePublishedPostgresWhenDockerHostnameIsUnreachable(): void
    {
        if (is_file('/.dockerenv')) {
            return;
        }

        $default = (string) config('database.default');
        if ($default === '' || $default === 'sqlite') {
            return;
        }

        $hostKey = "database.connections.{$default}.host";
        if ((string) config($hostKey) !== 'db') {
            return;
        }

        config([$hostKey => '127.0.0.1']);
    }
}

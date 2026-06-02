<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Frontend-portaal voor Nexa Taxi (Mijn Taxi). Alleen als taxi actief is en de tenant taxi heeft.
 */
class EnsureTenantTaxiModule
{
    public function __construct(
        protected ModuleManager $moduleManager
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Super-admin mag altijd bij de portal (handig voor ontwikkeling op centraal domein / localhost).
        if (auth()->check() && auth()->user()?->hasRole('super-admin')) {
            return $next($request);
        }

        if (! $this->moduleManager->isActive('taxi')) {
            return redirect()->route('home');
        }

        if (app()->bound('resolved_tenant') && app('resolved_tenant') instanceof Company) {
            /** @var Company $tenant */
            $tenant = app('resolved_tenant');
            if (! $tenant->hasTaxiModule()) {
                return redirect()->route('home');
            }
        }

        return $next($request);
    }
}

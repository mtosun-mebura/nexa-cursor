<?php

namespace App\Http\Middleware;

use App\Services\ModuleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect naar home wanneer de Skillmatching-module niet actief is.
 * Gebruikt voor frontend-routes die alleen bij actieve Nexa Skillmatching horen (dashboard, jobs, matches, agenda).
 */
class EnsureSkillmatchingModule
{
    public function __construct(
        protected ModuleManager $moduleManager
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->moduleManager->isActive('skillmatching')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}

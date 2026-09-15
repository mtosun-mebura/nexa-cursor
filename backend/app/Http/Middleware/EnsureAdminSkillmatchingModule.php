<?php

namespace App\Http\Middleware;

use App\Services\AdminDashboardModuleContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-pagina's van Nexa Skillmatching (vacatures, matches, interviews, branches)
 * alleen wanneer de module in deze admin-context beschikbaar is.
 */
class EnsureAdminSkillmatchingModule
{
    public function __construct(
        protected AdminDashboardModuleContext $moduleContext
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->moduleContext->skillmatchingAvailable()) {
            return $next($request);
        }

        $message = 'Nexa Skillmatching is niet actief. Je bent naar het dashboard gestuurd.';

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'message' => $message,
                'redirect' => route('admin.dashboard'),
            ], 404);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('warning', $message);
    }
}

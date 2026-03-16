<?php

namespace App\Http\Middleware;

use App\Services\WebsiteBuilderService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            // Alleen een echte paginapagina als intended bewaren, niet API-endpoints (bijv. unread-count)
            $path = $request->path();
            $isUtilityPath = preg_match('#^(admin/)?(chat|notifications)/unread-count#', $path);
            if (!$isUtilityPath) {
                session(['url.intended' => $request->fullUrl()]);
            }

            // For AJAX requests, return 401 status instead of redirect (client passes intended via window.location)
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Je sessie is verlopen. Log opnieuw in.',
                    'redirect' => route('admin.meld.sessie-verlopen', ['intended' => $request->fullUrl()])
                ], 401);
            }
            
            return redirect()->route('admin.meld.sessie-verlopen', ['intended' => $request->fullUrl()]);
        }

        // Check if user has admin role (super-admin, company-admin, or staff)
        if (!auth()->user()->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
            // For AJAX requests, return 403 status instead of redirect
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Je hebt geen rechten om deze actie uit te voeren.',
                    'redirect' => route('admin.login')
                ], 403);
            }

            // Redirect to admin login page instead of home
            return redirect()->route('admin.login')->with('error', 'Je hebt geen rechten om deze pagina te bekijken.');
        }

        // Super-admin: als er nog geen tenant gekozen is, automatisch de tenant van de actieve (branding) module kiezen
        if (auth()->user()->hasRole('super-admin') && !session()->has('selected_tenant')) {
            $websiteBuilder = app(WebsiteBuilderService::class);
            $brandingModule = $websiteBuilder->getBrandingModule();
            if ($brandingModule && is_array($brandingModule->configuration)) {
                $companyId = $brandingModule->configuration['company_id'] ?? null;
                if ($companyId !== null && $companyId !== '') {
                    session(['selected_tenant' => (int) $companyId]);
                }
            }
        }

        return $next($request);
    }
}

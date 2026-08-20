<?php

namespace App\Http\Middleware;

use App\Services\NexaDemoAccountService;
use App\Support\AdminReturnUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\PermissionRegistrar;
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
        Auth::shouldUse('web');

        // Check if user is authenticated (sessie op web-guard; zie AdminRoutesUseWebGuard bij AUTH_GUARD=api)
        if (! auth('web')->check()) {
            // Alleen een echte paginapagina als intended bewaren, niet API-endpoints (bijv. unread-count)
            $path = $request->path();
            $isUtilityPath = preg_match('#^(admin/)?(chat|notifications)/unread-count#', $path);
            if (! $isUtilityPath) {
                $resolved = AdminReturnUrl::resolveIntended($request->fullUrl());
                if ($resolved !== null) {
                    session(['url.intended' => $resolved]);
                }
            }

            // For AJAX requests, return 401 status instead of redirect (client passes intended via window.location)
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                $relative = '/admin/meld/sessie-verlopen?'.http_build_query(['intended' => $request->fullUrl()]);

                return response()->json([
                    'message' => 'Je sessie is verlopen. Log opnieuw in.',
                    'redirect' => $relative,
                ], 401);
            }

            // Relatief pad i.p.v. route(): voorkomt absolute https://… URL’s terwijl Docker op :8000 geen TLS heeft
            // (anders ERR_CONNECTION_CLOSED in de browser).
            return new RedirectResponse(
                '/admin/meld/sessie-verlopen?'.http_build_query(['intended' => $request->fullUrl()])
            );
        }

        $user = auth('web')->user();
        if ($user && $user->company_id) {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }
        if (! $user->canAccessAdminPanel()) {
            // For AJAX requests, return 403 status instead of redirect
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Je hebt geen rechten om deze actie uit te voeren.',
                    'redirect' => route('admin.login'),
                ], 403);
            }

            // Redirect to admin login page instead of home
            return redirect()->route('admin.login')->with('error', 'Je hebt geen rechten om deze pagina te bekijken.');
        }

        if (app(NexaDemoAccountService::class)->isDemoUser($user)) {
            Config::set('mail.default', 'array');
        }

        return $next($request);
    }
}

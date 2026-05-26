<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zorgt dat alle /admin-routes de web-sessie gebruiken, ook als AUTH_GUARD=api staat.
 * Anders is auth()->user() / @auth / Spatie can() leeg of inconsistent → valse 403 en ontbrekende knoppen.
 */
class AdminRoutesUseWebGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*')) {
            Auth::shouldUse('web');
        }

        return $next($request);
    }
}

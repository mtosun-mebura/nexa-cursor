<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            // Store the intended URL for redirect after login
            session(['url.intended' => $request->url()]);
            return redirect()->route('admin.login')->with('error', 'Je moet ingelogd zijn om deze pagina te bekijken.');
        }

        // Check if user has the required role
        if (!$request->user()->hasRole($role)) {
            return redirect()->back()->with('error', 'Je hebt geen rechten om deze pagina te bekijken.');
        }

        return $next($request);
    }
}

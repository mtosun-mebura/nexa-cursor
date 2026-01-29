<?php

namespace App\Http\Middleware;

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
            // Store the intended URL for redirect after login
            session(['url.intended' => $request->url()]);
            
            // For AJAX requests, return 401 status instead of redirect
            if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Je sessie is verlopen. Log opnieuw in.',
                    'redirect' => route('admin.meld.sessie-verlopen')
                ], 401);
            }
            
            return redirect()->route('admin.meld.sessie-verlopen');
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

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Klanten die via boeking een account kregen moeten eerst een wachtwoord instellen.
 */
class EnsureTaxiKlantPasswordIsSet
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->password_must_be_set) {
            return $next($request);
        }

        if ($request->routeIs('frontend.set-password', 'frontend.set-password.post', 'logout')) {
            return $next($request);
        }

        $intended = $request->fullUrl();

        return redirect()->route('frontend.set-password', [
            'intended' => $intended,
        ]);
    }
}

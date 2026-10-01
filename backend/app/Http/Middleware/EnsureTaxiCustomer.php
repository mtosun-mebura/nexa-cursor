<?php

namespace App\Http\Middleware;

use App\Services\PublicRegistrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaxiCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->hasRole(PublicRegistrationService::ROLE_CUSTOMER)) {
            return response()->json([
                'message' => 'Dit account heeft geen klanttoegang tot de taxi-app.',
            ], 403);
        }

        if ($user->is_active === false) {
            return response()->json(['message' => 'Dit account is gedeactiveerd.'], 403);
        }

        return $next($request);
    }
}

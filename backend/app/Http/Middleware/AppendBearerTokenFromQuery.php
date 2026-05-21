<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EventSource kan geen Authorization-header meesturen; token via ?token= voor Sanctum.
 */
class AppendBearerTokenFromQuery
{
    public function handle(Request $request, Closure $next): Response
    {
        $queryToken = $request->query('token');
        if (is_string($queryToken) && $queryToken !== '' && ! $request->bearerToken()) {
            $request->headers->set('Authorization', 'Bearer '.$queryToken);
        }

        return $next($request);
    }
}

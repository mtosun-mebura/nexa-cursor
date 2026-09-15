<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\CentralDomains;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * LAN/mobiel: APP_URL is localhost, de browser opent 192.168.x.x.
 * Zonder dit wijzen asset()/route() naar localhost — onbereikbaar op de telefoon.
 */
class ForceLocalDevRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction() && CentralDomains::isLocalDevEntryHost($request->getHost())) {
            $root = $request->getScheme().'://'.$request->getHost();
            $port = $request->getPort();
            if ($port && ! in_array($port, [80, 443], true)) {
                $root .= ':'.$port;
            }
            URL::forceRootUrl($root);
        }

        return $next($request);
    }
}

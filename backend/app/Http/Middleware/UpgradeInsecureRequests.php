<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Voegt op een HTTPS-verbinding de CSP-directive `upgrade-insecure-requests` toe, zodat de browser
 * elke http:// subresource (afbeeldingen, scripts, fonts, ook door tenants ingevoerde URL's)
 * automatisch over https laadt en er geen "niet beveiligd"/mixed-content-melding verschijnt.
 *
 * Alleen bij een beveiligde request: op gewone http (lokaal/LAN-dev op :8000 zonder TLS) zou het
 * upgraden van same-origin assets naar https juist ERR_CONNECTION_CLOSED veroorzaken.
 *
 * Daarnaast wordt HSTS (Strict-Transport-Security) gezet zodat de browser na het eerste bezoek
 * automatisch intern naar https upgradet en de http-variant nooit meer toont. includeSubDomains
 * dekt alle tenant-subdomeinen onder het wildcard-certificaat.
 */
class UpgradeInsecureRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->isSecure()) {
            return $response;
        }

        $existing = trim((string) $response->headers->get('Content-Security-Policy', ''));

        if ($existing === '') {
            $response->headers->set('Content-Security-Policy', 'upgrade-insecure-requests');
        } elseif (! str_contains($existing, 'upgrade-insecure-requests')) {
            $response->headers->set('Content-Security-Policy', rtrim($existing, '; ').'; upgrade-insecure-requests');
        }

        if (! $response->headers->has('Strict-Transport-Security')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}

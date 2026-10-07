<?php

namespace App\Http\Middleware;

use App\Support\WebsiteSeoMeta;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301 naar de schone URL wanneer alleen tracking/prefill-queryparams aanwezig zijn.
 * Prefill (bijv. ?pakket=) blijft beschikbaar via session flash voor het volgende request.
 */
class StripSoftSeoQueryParams
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $query = $request->query();
        if ($query === []) {
            return $next($request);
        }

        $soft = [];
        $hard = [];
        foreach ($query as $key => $value) {
            $name = (string) $key;
            if (WebsiteSeoMeta::isSoftQueryParam($name)) {
                $soft[$name] = $value;
            } else {
                $hard[$name] = $value;
            }
        }

        if ($soft === []) {
            return $next($request);
        }

        if ($request->hasSession()) {
            $request->session()->flash(WebsiteSeoMeta::SOFT_QUERY_SESSION_KEY, $soft);
        }

        $target = $request->url();
        if ($hard !== []) {
            $target .= '?'.http_build_query($hard);
        }

        return redirect()->to($target, 301);
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->is(
            'admin',
            'admin/*',
            'api',
            'api/*',
            'livewire/*',
            'sanctum/*',
            'broadcasting/*',
            'login',
            'login/*',
            'register',
            'register/*',
            'password/*',
            'email/*',
            'integrations/*',
        );
    }
}

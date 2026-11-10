<?php

namespace App\Http\Middleware;

use App\Modules\NexaTaxi\Support\CustomerMailLocale;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Native app language (X-App-Locale) for API requests and optional user preference.
 */
class CaptureAppLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = CustomerMailLocale::fromRequest($request);
        App::setLocale($locale);
        Carbon::setLocale($locale);

        $user = $request->user();
        if ($user) {
            CustomerMailLocale::persistForUser($user, $locale);
        }

        return $next($request);
    }
}

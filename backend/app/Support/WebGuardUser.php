<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Web-guard user lookup that never 500's when the session model cannot be loaded.
 */
final class WebGuardUser
{
    public static function fromRequest(Request $request, string $guard = 'web'): ?Authenticatable
    {
        try {
            return $request->user($guard);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    public static function fromGuard(string $guard = 'web'): ?Authenticatable
    {
        try {
            return Auth::guard($guard)->user();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}

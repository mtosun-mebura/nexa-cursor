<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\WebGuardUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = WebGuardUser::fromRequest($request);
        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->must_change_password) {
            if ($this->isPasswordChangeAllowed($request)) {
                return $next($request);
            }

            if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
                view()->share('adminMustChangePassword', true);

                return $next($request);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => 'Wijzig eerst uw tijdelijke wachtwoord voordat u verder gaat.',
                    'must_change_password' => true,
                ], 423);
            }

            return redirect()->to($request->headers->get('referer') ?: route('admin.dashboard'))
                ->with('error', 'Wijzig eerst uw tijdelijke wachtwoord voordat u verder gaat.');
        }

        if ($this->shouldRedirectToHandleiding($request, $user)) {
            return redirect()->route('admin.handleiding.index');
        }

        return $next($request);
    }

    private function shouldRedirectToHandleiding(Request $request, User $user): bool
    {
        if (! $user->welcome_handleiding_pending) {
            return false;
        }

        if ($request->routeIs('admin.handleiding.*', 'admin.logout')) {
            return false;
        }

        if ($request->expectsJson() || $request->ajax()) {
            return false;
        }

        return $request->isMethod('GET') || $request->isMethod('HEAD');
    }

    private function isPasswordChangeAllowed(Request $request): bool
    {
        if ($request->routeIs('admin.password.force.update', 'admin.logout')) {
            return true;
        }

        $path = trim($request->path(), '/');

        return in_array($path, [
            'admin/wachtwoord-wijzigen',
            'admin/logout',
        ], true);
    }
}

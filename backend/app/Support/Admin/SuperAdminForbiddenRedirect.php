<?php

namespace App\Support\Admin;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SuperAdminForbiddenRedirect
{
    /**
     * Super-admin in tenant-context (zijbalk) mag geen 403-pagina zien:
     * stuur door naar het dashboard in plaats van “geen toegang”.
     */
    public static function toDashboard(Request $request): ?RedirectResponse
    {
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return null;
        }

        if (! $request->is('admin') && ! $request->is('admin/*')) {
            return null;
        }

        if ($request->routeIs('admin.dashboard')) {
            return null;
        }

        $user = $request->user();
        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            return null;
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('warning', 'Deze pagina is niet beschikbaar voor de gekozen tenant. Je bent naar het dashboard gestuurd.');
    }
}

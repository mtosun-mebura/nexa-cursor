<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Slaat de gewenste admin-terug-URL op bij ?nexa_admin_preview=1&admin_back=/admin/...
 * Verwijdert die sessie bij bezoek aan /admin.
 */
class WebsiteAdminPreviewSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = ltrim($request->path(), '/');

        if (str_starts_with($path, 'admin')) {
            $request->session()->forget('website_preview_admin_url');
        } elseif ($request->isMethod('GET') && $request->boolean('nexa_admin_preview')) {
            $adminBack = $request->query('admin_back');
            if (is_string($adminBack) && $adminBack !== '') {
                $normalized = $this->normalizeAdminBack($adminBack);
                if ($normalized !== null) {
                    $request->session()->put('website_preview_admin_url', url($normalized));
                }
            }
        }

        return $next($request);
    }

    private function normalizeAdminBack(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }
        $path = '/'.ltrim($trimmed, '/');
        if (! str_starts_with($path, '/admin')) {
            return null;
        }
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        return $path;
    }
}

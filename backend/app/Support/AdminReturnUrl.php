<?php

namespace App\Support;

/**
 * Veilige terug-URL's binnen /admin (geen open redirect).
 */
final class AdminReturnUrl
{
    public static function sanitize(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }
        $path = trim($raw);
        if ($path === '' || strlen($path) > 2048) {
            return null;
        }
        if (str_contains($path, "\r") || str_contains($path, "\n")) {
            return null;
        }
        if (str_starts_with($path, '//')) {
            return null;
        }
        if (! str_starts_with($path, '/admin')) {
            return null;
        }

        return $path;
    }

    public static function fromRequest(mixed $raw, ?string $fallback = null): string
    {
        return self::sanitize($raw) ?? $fallback ?? route('admin.dashboard');
    }

    public static function appendReturnParam(string $url, string $returnPath): string
    {
        $safeReturn = self::sanitize($returnPath);
        if ($safeReturn === null) {
            return $url;
        }

        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'return='.rawurlencode($safeReturn);
    }
}

<?php

namespace App\Support;

/**
 * Veilige terug-URL's binnen /admin (geen open redirect).
 */
final class AdminReturnUrl
{
    /**
     * Paden die nooit als intended mogen dienen (login-loop, meld-pagina's, API-hulpverzoeken).
     */
    public static function isBlockedIntendedPath(string $path): bool
    {
        $path = rtrim($path, '/') ?: '/';

        if ($path === '/admin/login' || str_starts_with($path, '/admin/login/')) {
            return true;
        }

        if (str_starts_with($path, '/admin/meld/')) {
            return true;
        }

        if (preg_match('#^/admin/(chat|notifications)/unread-count#', $path)) {
            return true;
        }

        if (str_starts_with($path, '/admin/password/')) {
            return true;
        }

        if ($path === '/admin' || $path === '/admin/') {
            return true;
        }

        return false;
    }

    /**
     * Pad uit relatief pad of absolute URL halen.
     */
    public static function pathFrom(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '' || strlen($raw) > 2048) {
            return null;
        }

        if (str_contains($raw, "\r") || str_contains($raw, "\n")) {
            return null;
        }

        if (str_starts_with($raw, '//')) {
            return null;
        }

        if (str_starts_with($raw, '/admin')) {
            $path = parse_url($raw, PHP_URL_PATH);

            return is_string($path) && $path !== '' ? $path : null;
        }

        if (preg_match('#^https?://#i', $raw)) {
            $path = parse_url($raw, PHP_URL_PATH);

            return is_string($path) && $path !== '' ? $path : null;
        }

        return null;
    }

    /**
     * Geldige admin-intended URL (volledig of relatief), of null als onbruikbaar/geblokkeerd.
     */
    public static function resolveIntended(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '' || strlen($raw) > 2048) {
            return null;
        }

        if (str_contains($raw, "\r") || str_contains($raw, "\n")) {
            return null;
        }

        if (str_starts_with($raw, '//')) {
            return null;
        }

        $path = self::pathFrom($raw);
        if ($path === null || ! str_starts_with($path, '/admin')) {
            return null;
        }

        if (self::isBlockedIntendedPath($path)) {
            return null;
        }

        return $raw;
    }

    public static function sanitize(mixed $raw): ?string
    {
        $resolved = self::resolveIntended($raw);
        if ($resolved === null) {
            return null;
        }

        if (str_starts_with($resolved, '/admin')) {
            return $resolved;
        }

        $path = parse_url($resolved, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return null;
        }

        $query = parse_url($resolved, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
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

    public static function loginUrlWithIntended(?string $intended = null): string
    {
        $safe = self::resolveIntended($intended);
        if ($safe === null) {
            return '/admin/login';
        }

        return '/admin/login?'.http_build_query(['intended' => $safe]);
    }
}

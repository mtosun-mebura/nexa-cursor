<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Pad op de huidige host, zonder localhost/APP_URL.
 * Nodig voor LAN-preview (192.168.x.x:8085) van chauffeur- en contract-PWA.
 */
final class SameOriginPath
{
    public static function fromUrl(string $urlOrPath): string
    {
        if ($urlOrPath === '') {
            return $urlOrPath;
        }

        $parts = parse_url($urlOrPath);
        if (! is_array($parts)) {
            return $urlOrPath;
        }

        $path = $parts['path'] ?? '';
        if ($path === '') {
            return $urlOrPath;
        }

        if (! empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        return $path;
    }

    public static function namedRoute(string $name, string $fallbackPath): string
    {
        if (! Route::has($name)) {
            return $fallbackPath;
        }

        return self::fromUrl(route($name, absolute: false));
    }
}

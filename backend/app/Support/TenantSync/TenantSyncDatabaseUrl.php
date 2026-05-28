<?php

namespace App\Support\TenantSync;

final class TenantSyncDatabaseUrl
{
    public static function injectPassword(string $url, ?string $password): string
    {
        $password = $password !== null ? trim($password) : '';
        if ($password === '') {
            return $url;
        }

        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme'])) {
            return $url;
        }

        $user = rawurlencode((string) ($parts['user'] ?? ''));
        $encodedPass = rawurlencode($password);
        $auth = $user !== '' ? $user.':'.$encodedPass : $encodedPass;

        return self::build($parts, $auth);
    }

    public static function stripPassword(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme'])) {
            return $url;
        }

        $user = (string) ($parts['user'] ?? '');
        if ($user === '' && ! isset($parts['pass'])) {
            return $url;
        }

        $auth = rawurlencode($user);

        return self::build($parts, $auth !== '' ? $auth : null);
    }

    public static function extractPassword(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        if (! isset($parts['pass'])) {
            return null;
        }

        $pass = (string) $parts['pass'];

        return $pass !== '' ? $pass : null;
    }

    public static function parseUser(string $url): string
    {
        $parts = parse_url($url);

        return is_array($parts) ? (string) ($parts['user'] ?? '') : '';
    }

    public static function parseDatabase(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts)) {
            return '';
        }

        return ltrim((string) ($parts['path'] ?? ''), '/');
    }

    public static function buildConnection(string $scheme, string $user, string $host, int $port, string $database): string
    {
        $user = rawurlencode($user);
        $path = '/'.ltrim($database, '/');

        return sprintf('%s://%s@%s:%d%s', $scheme, $user, $host, $port, $path);
    }

    public static function replaceHostPort(string $url, string $host, int $port): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme'])) {
            return $url;
        }

        $parts['host'] = $host;
        $parts['port'] = $port;

        $auth = null;
        if (isset($parts['user']) && (string) $parts['user'] !== '') {
            $auth = rawurlencode((string) $parts['user']);
            if (isset($parts['pass']) && (string) $parts['pass'] !== '') {
                $auth .= ':'.rawurlencode((string) $parts['pass']);
            }
        }

        return self::build($parts, $auth);
    }

    /**
     * @param  array<string, mixed>  $parts
     */
    private static function build(array $parts, ?string $auth): string
    {
        $scheme = (string) $parts['scheme'];
        $url = $scheme.'://';
        if ($auth !== null && $auth !== '') {
            $url .= $auth.'@';
        }
        $url .= (string) ($parts['host'] ?? 'localhost');
        if (isset($parts['port'])) {
            $url .= ':'.(int) $parts['port'];
        }
        $path = (string) ($parts['path'] ?? '');
        if ($path !== '') {
            $url .= str_starts_with($path, '/') ? $path : '/'.$path;
        }
        if (isset($parts['query']) && (string) $parts['query'] !== '') {
            $url .= '?'.$parts['query'];
        }

        return $url;
    }
}

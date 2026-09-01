<?php

namespace App\Services\CompanySearch;

class DomainService
{
    public function host(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }

    public function origin(string $url): string
    {
        $parts = parse_url($this->normalizeWebsite($url));
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return $parts['scheme'].'://'.$parts['host'];
    }

    public function normalizeWebsite(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.ltrim($url, '/');
        }
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }
        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        return $scheme.'://'.$parts['host'];
    }

    public function emailMatchesDomain(string $email, string $website): bool
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        $host = $this->host($website);
        if ($domain === '' || $host === '') {
            return false;
        }
        if ($domain === $host || str_ends_with($host, '.'.$domain) || str_ends_with($domain, '.'.$host)) {
            return true;
        }

        $emailRoot = $this->registrableName($domain);
        $hostRoot = $this->registrableName($host);

        return $emailRoot !== '' && $emailRoot === $hostRoot;
    }

    private function registrableName(string $host): string
    {
        $parts = explode('.', $host);
        if (count($parts) < 2) {
            return $host;
        }
        array_pop($parts);

        return implode('.', $parts);
    }
}

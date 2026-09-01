<?php

namespace App\Services\CompanySearch;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafeUrlFetcher
{
    public function __construct(
        protected DomainService $domains,
    ) {}

    public function get(string $url): ?Response
    {
        $url = $this->sanitize($url);
        if ($url === null) {
            return null;
        }

        $timeout = (int) config('company-enrichment.crawler.timeout', 10);
        $maxBytes = (int) config('company-enrichment.crawler.max_body_bytes', 512000);
        $userAgent = (string) config('company-enrichment.crawler.user_agent', 'NexaCompanyCrawler/1.0');

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(min(6, $timeout))
                ->withOptions([
                    'allow_redirects' => false,
                    'http_errors' => false,
                ])
                ->withHeaders([
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.8',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::info('Company website unreachable', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        if ($response->redirect()) {
            $location = $response->header('Location');
            if (! is_string($location) || $location === '') {
                return null;
            }
            $next = $this->absolute($location, $url);
            if ($next === null || strcasecmp($next, $url) === 0) {
                return null;
            }

            return $this->get($next);
        }

        if (! $response->successful()) {
            return null;
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if ($contentType !== '' && preg_match('/(pdf|zip|octet-stream|image\/|video\/|audio\/)/i', $contentType)) {
            return null;
        }

        if (strlen($response->body()) > $maxBytes) {
            return $response;
        }

        return $response;
    }

    public function html(string $url): string
    {
        $response = $this->get($url);
        if ($response === null) {
            return '';
        }
        $maxBytes = (int) config('company-enrichment.crawler.max_body_bytes', 512000);
        $body = $response->body();
        if (strlen($body) > $maxBytes) {
            $body = substr($body, 0, $maxBytes);
        }

        return $body;
    }

    public function sanitize(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = 'https://'.$url;
        }
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }
        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }
        $host = strtolower((string) $parts['host']);
        if ($this->isBlockedHost($host)) {
            Log::info('Company crawler blocked SSRF host', ['host' => $host]);

            return null;
        }
        if (! $this->hostResolvesPublicly($host)) {
            Log::info('Company crawler blocked private or unresolved host', ['host' => $host]);

            return null;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$path.$query;
    }

    private function absolute(string $href, string $baseUrl): ?string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->sanitize($href);
        }
        $origin = $this->domains->origin($baseUrl);
        if ($origin === '') {
            return null;
        }
        if (str_starts_with($href, '/')) {
            return $this->sanitize($origin.$href);
        }

        return $this->sanitize($origin.'/'.ltrim($href, '/'));
    }

    private function isBlockedHost(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));
        if (in_array($host, ['localhost', '127.0.0.1', '0.0.0.0', '::1', 'metadata.google.internal'], true)) {
            return true;
        }
        if (str_ends_with($host, '.local') || str_ends_with($host, '.internal') || str_ends_with($host, '.localhost')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return ! $this->isPublicIp($host);
        }

        return false;
    }

    private function hostResolvesPublicly(string $host): bool
    {
        if (app()->environment('testing')) {
            return true;
        }
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return $this->isPublicIp($host);
        }

        $records = @dns_get_record($host, DNS_A + DNS_AAAA);
        if (! is_array($records) || $records === []) {
            $ip = gethostbyname($host);
            if ($ip === $host) {
                return false;
            }

            return $this->isPublicIp($ip);
        }

        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($ip) && $ip !== '' && ! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private function isPublicIp(string $ip): bool
    {
        if (in_array($ip, ['169.254.169.254', '::ffff:169.254.169.254'], true)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}

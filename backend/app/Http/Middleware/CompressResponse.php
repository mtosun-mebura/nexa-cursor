<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gzip HTML/CSS/JS/JSON responses when the client accepts it.
 * Helps locally (PHP built-in server has no gzip) and as a fallback
 * when the reverse proxy does not compress application responses.
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }

        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        $acceptEncoding = strtolower((string) $request->header('Accept-Encoding', ''));
        if (! str_contains($acceptEncoding, 'gzip') || ! function_exists('gzencode')) {
            return $response;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));
        if ($contentType === '' || str_contains($contentType, 'event-stream')) {
            return $response;
        }

        $compressible = str_starts_with($contentType, 'text/')
            || str_contains($contentType, 'json')
            || str_contains($contentType, 'javascript')
            || str_contains($contentType, 'xml')
            || str_contains($contentType, 'svg');

        if (! $compressible) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || strlen($content) < 1024) {
            return $response;
        }

        $compressed = gzencode($content, 5);
        if ($compressed === false || strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', (string) strlen($compressed));
        $response->headers->set('Vary', $this->varyWithAcceptEncoding($response));

        return $response;
    }

    private function varyWithAcceptEncoding(Response $response): string
    {
        $existing = trim((string) $response->headers->get('Vary', ''));
        if ($existing === '') {
            return 'Accept-Encoding';
        }

        $parts = array_map('trim', explode(',', $existing));
        foreach ($parts as $part) {
            if (strcasecmp($part, 'Accept-Encoding') === 0) {
                return $existing;
            }
        }

        return $existing.', Accept-Encoding';
    }
}

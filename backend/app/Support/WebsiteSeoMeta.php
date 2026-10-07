<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Schone SEO-meta voor tenant-/websitepagina's (geen Editor.js-JSON in description).
 */
final class WebsiteSeoMeta
{
    public const SOFT_QUERY_SESSION_KEY = 'seo_soft_query';

    /**
     * Queryparams die geen unieke indexeerbare pagina maken (prefill + tracking).
     */
    public static function isSoftQueryParam(string $key): bool
    {
        $key = strtolower(trim($key));
        if ($key === '') {
            return false;
        }

        if (in_array($key, [
            'pakket',
            'gclid',
            'gbraid',
            'wbraid',
            'fbclid',
            'msclkid',
            'srsltid',
            '_ga',
            '_gl',
            'mc_cid',
            'mc_eid',
        ], true)) {
            return true;
        }

        return str_starts_with($key, 'utm_');
    }

    /**
     * Absolute canonieke URL zonder querystring (zelfde host/scheme als het request).
     */
    public static function canonicalUrl(?Request $request = null): string
    {
        $request ??= request();
        $path = '/'.ltrim($request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }
        if ($request->path() === '/' || $path === '/') {
            $path = '/';
        } else {
            $path = '/'.trim($request->path(), '/');
        }

        $root = rtrim($request->root(), '/');
        if ($request->secure() && str_starts_with($root, 'http://')) {
            $root = 'https://'.substr($root, 7);
        }

        return $path === '/' ? $root.'/' : $root.$path;
    }

    /**
     * Prefill-waarde uit query of (na soft-query 301) uit session flash.
     */
    public static function softQueryValue(string $key, mixed $default = null): mixed
    {
        $fromRequest = request()->query($key);
        if ($fromRequest !== null && $fromRequest !== '') {
            return $fromRequest;
        }

        $bag = session(self::SOFT_QUERY_SESSION_KEY, []);
        if (is_array($bag) && array_key_exists($key, $bag) && $bag[$key] !== null && $bag[$key] !== '') {
            return $bag[$key];
        }

        return $default;
    }

    public static function isUsableDescription(?string $text): bool
    {
        $text = trim((string) $text);
        if ($text === '') {
            return false;
        }

        if (self::looksLikeEditorJs($text)) {
            return false;
        }

        // Alleen JSON/markup zonder leesbare zin
        if (preg_match('/^\s*[\{\[]/', $text) === 1) {
            return false;
        }

        $plain = self::collapseWhitespace(strip_tags($text));

        return mb_strlen($plain) >= 20;
    }

    /**
     * Kies een bruikbare meta description (max ~160 tekens).
     */
    public static function resolveDescription(
        ?string $metaDescription,
        ?string $content = null,
        ?string $siteDescription = null,
        ?string $fallbackBrand = null,
    ): string {
        foreach ([$metaDescription, $siteDescription] as $candidate) {
            if (self::isUsableDescription($candidate)) {
                return self::limit(self::collapseWhitespace(strip_tags((string) $candidate)));
            }
        }

        if (self::isUsableDescription($content)) {
            return self::limit(self::collapseWhitespace(strip_tags((string) $content)));
        }

        // Editor.js of andere JSON: probeer tekstblokken te halen
        $fromBlocks = self::plainTextFromPossibleEditorJs((string) ($content ?? ''));
        if (self::isUsableDescription($fromBlocks)) {
            return self::limit($fromBlocks);
        }

        $brand = trim((string) $fallbackBrand);
        if ($brand !== '') {
            return self::limit($brand.' — betrouwbaar taxivervoer. Boek online of bel de centrale.');
        }

        return 'Boek eenvoudig een taxirit online.';
    }

    public static function looksLikeEditorJs(string $text): bool
    {
        $trim = trim($text);
        if ($trim === '') {
            return false;
        }

        if (str_contains($trim, '"blocks"') && str_contains($trim, '"version"')) {
            return true;
        }

        if ($trim[0] !== '{' && $trim[0] !== '[') {
            return false;
        }

        $decoded = json_decode($trim, true);
        if (! is_array($decoded)) {
            return false;
        }

        return array_key_exists('blocks', $decoded) || array_key_exists('time', $decoded);
    }

    public static function plainTextFromPossibleEditorJs(string $raw): string
    {
        $trim = trim($raw);
        if ($trim === '' || ! self::looksLikeEditorJs($trim)) {
            return self::collapseWhitespace(strip_tags($raw));
        }

        $decoded = json_decode($trim, true);
        if (! is_array($decoded)) {
            return '';
        }

        $parts = [];
        foreach ($decoded['blocks'] ?? [] as $block) {
            if (! is_array($block)) {
                continue;
            }
            $data = $block['data'] ?? [];
            if (! is_array($data)) {
                continue;
            }
            if (! empty($data['text']) && is_string($data['text'])) {
                $parts[] = html_entity_decode(strip_tags($data['text']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (! empty($data['caption']) && is_string($data['caption'])) {
                $parts[] = html_entity_decode(strip_tags($data['caption']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    if (is_string($item)) {
                        $parts[] = html_entity_decode(strip_tags($item), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    } elseif (is_array($item) && ! empty($item['content']) && is_string($item['content'])) {
                        $parts[] = html_entity_decode(strip_tags($item['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    }
                }
            }
        }

        return self::collapseWhitespace(implode(' ', $parts));
    }

    public static function brandLabel(array $branding): string
    {
        $logoAlt = trim((string) ($branding['logo_alt'] ?? ''));
        if ($logoAlt !== '') {
            return $logoAlt;
        }

        return trim((string) ($branding['site_name'] ?? config('app.name', 'Nexa')));
    }

    private static function collapseWhitespace(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private static function limit(string $text, int $max = 160): string
    {
        return Str::limit($text, $max, '…');
    }
}

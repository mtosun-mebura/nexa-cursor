<?php

namespace App\Services;

use App\Services\CompanySearch\DomainService;
use App\Services\CompanySearch\SafeUrlFetcher;

class WebsiteAiSourceReader
{
    private const MAX_PAGES = 5;

    private const MAX_CHARS_PER_PAGE = 2800;

    private const PRIORITY_PATHS = [
        '/over-ons',
        '/overons',
        '/about',
        '/about-us',
        '/diensten',
        '/services',
        '/tarieven',
        '/prijzen',
        '/contact',
        '/contact-ons',
    ];

    public function __construct(
        protected SafeUrlFetcher $fetcher,
        protected DomainService $domains,
    ) {}

    private function maxPages(): int
    {
        return max(1, min(25, (int) config('ai_website.crawl_max_pages', self::MAX_PAGES)));
    }

    /**
     * @return array{url: string, pages: list<array{url: string, title: string, headings: list<string>, text: string}>, summary: string}
     */
    public function read(?string $website): array
    {
        $website = trim((string) $website);
        if ($website === '') {
            return ['url' => '', 'pages' => [], 'summary' => ''];
        }

        $origin = $this->domains->origin($website);
        if ($origin === '') {
            return ['url' => $website, 'pages' => [], 'summary' => ''];
        }

        $start = $this->fetcher->sanitize($website) ?? ($origin.'/');
        $urls = [$start];
        $homeHtml = $this->fetcher->html($start);
        if ($homeHtml !== '') {
            foreach ($this->internalLinks($homeHtml, $origin) as $link) {
                $urls[] = $link;
            }
        }
        foreach (self::PRIORITY_PATHS as $path) {
            $urls[] = $origin.$path;
        }

        $urls = array_values(array_unique(array_slice($urls, 0, $this->maxPages())));
        $pages = [];
        $delayMs = app()->environment('testing') ? 0 : 120;
        foreach ($urls as $index => $url) {
            $html = ($index === 0 && $homeHtml !== '') ? $homeHtml : $this->fetcher->html($url);
            if ($html === '') {
                continue;
            }
            $extracted = $this->extract($url, $html);
            if ($extracted['text'] === '' && $extracted['title'] === '') {
                continue;
            }
            $pages[] = $extracted;
            if ($delayMs > 0 && $index < count($urls) - 1) {
                usleep($delayMs * 1000);
            }
        }

        $summaryParts = [];
        foreach ($pages as $page) {
            $block = trim($page['title']."\n".implode("\n", $page['headings'])."\n".$page['text']);
            if ($block !== '') {
                $summaryParts[] = "Bron: {$page['url']}\n".$block;
            }
        }

        return [
            'url' => $start,
            'pages' => $pages,
            'summary' => mb_substr(implode("\n\n", $summaryParts), 0, 12000),
        ];
    }

    /**
     * @return list<string>
     */
    private function internalLinks(string $html, string $origin): array
    {
        $links = [];
        if (preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\']/i', $html, $matches) < 1) {
            return [];
        }
        foreach ($matches[1] as $href) {
            $href = trim(html_entity_decode((string) $href));
            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $absolute = $this->absolute($href, $origin);
            if ($absolute === null || ! str_starts_with($absolute, $origin)) {
                continue;
            }
            $path = strtolower((string) (parse_url($absolute, PHP_URL_PATH) ?: '/'));
            if (! preg_match('#(over-ons|about|dienst|service|tarief|prijs|contact|team|fleet|wagenpark)#i', $path)) {
                continue;
            }
            $links[] = $absolute;
            if (count($links) >= 4) {
                break;
            }
        }

        return $links;
    }

    private function absolute(string $href, string $origin): ?string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->fetcher->sanitize($href);
        }
        if (str_starts_with($href, '/')) {
            return $this->fetcher->sanitize($origin.$href);
        }

        return $this->fetcher->sanitize($origin.'/'.ltrim($href, '/'));
    }

    /**
     * @return array{url: string, title: string, headings: list<string>, text: string}
     */
    private function extract(string $url, string $html): array
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<noscript\b[^>]*>.*?<\/noscript>/is', ' ', $html) ?? $html;

        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = $this->plain($m[1]);
        }
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)
            || preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i', $html, $m)) {
            $title = trim($title.' — '.$this->plain($m[1]), ' —');
        }

        $headings = [];
        if (preg_match_all('/<h([1-3])[^>]*>(.*?)<\/h\1>/is', $html, $hm)) {
            foreach ($hm[2] as $heading) {
                $text = $this->plain($heading);
                if ($text !== '' && ! in_array($text, $headings, true)) {
                    $headings[] = $text;
                }
                if (count($headings) >= 8) {
                    break;
                }
            }
        }

        $text = $this->plain($html);
        $text = mb_substr($text, 0, self::MAX_CHARS_PER_PAGE);

        return [
            'url' => $url,
            'title' => mb_substr($title, 0, 180),
            'headings' => $headings,
            'text' => $text,
        ];
    }

    private function plain(string $value): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}

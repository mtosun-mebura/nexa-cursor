<?php

namespace App\Services\CompanySearch;

class WebsiteCrawlerService
{
    /** @var list<string> */
    private const PRIORITY_PATHS = [
        '/contact',
        '/contact-ons',
        '/contacteer-ons',
        '/contact-us',
        '/contactgegevens',
        '/over-ons',
        '/about',
        '/about-us',
        '/bedrijf',
        '/team',
        '/nl/contact',
    ];

    public function __construct(
        protected SafeUrlFetcher $fetcher,
        protected DomainService $domains,
        protected EmailExtractorService $emails,
        protected PhoneExtractorService $phones,
        protected ContactNameExtractor $names,
    ) {}

    /**
     * @return array{email: ?string, phone: ?string, emails: list<array{email: string, source: string, confidence: int}>, first_name: ?string, middle_name: ?string, last_name: ?string}
     */
    public function collectContacts(string $website): array
    {
        $origin = $this->domains->origin($website);
        if ($origin === '') {
            return ['email' => null, 'phone' => null, 'emails' => [], 'first_name' => null, 'middle_name' => null, 'last_name' => null];
        }

        $pages = [$origin.'/'];
        $home = $this->fetcher->html($origin.'/');
        if ($home !== '') {
            foreach ($this->internalContactLinks($home, $origin) as $link) {
                $pages[] = $link;
            }
        }
        foreach (self::PRIORITY_PATHS as $path) {
            $pages[] = $origin.$path;
        }

        $maxPages = (int) config('company-enrichment.crawler.max_pages', 6);
        $delayMs = app()->environment('testing') ? 0 : (int) config('company-enrichment.crawler.delay_ms', 150);
        $pages = array_values(array_unique(array_slice($pages, 0, $maxPages)));

        $email = null;
        $phone = null;
        $emails = [];
        $name = null;
        foreach ($pages as $index => $page) {
            $html = ($index === 0) ? $home : $this->fetcher->html($page);
            if ($html === '') {
                continue;
            }
            $extracted = $this->emails->extract($html, $page, $origin);
            foreach ($extracted as $row) {
                $emails[] = $row;
            }
            if ($email === null && isset($extracted[0]['email'])) {
                $email = $extracted[0]['email'];
            }
            if ($phone === null) {
                $phone = $this->phones->fromHtml($html);
            }
            if ($name === null) {
                $name = $this->names->fromHtml($html, (string) ($email ?? ''));
            }
            if ($email !== null && $phone !== null && $name !== null) {
                break;
            }
            if ($delayMs > 0 && $index < count($pages) - 1) {
                usleep($delayMs * 1000);
            }
        }

        if ($emails !== []) {
            usort($emails, fn (array $a, array $b) => $b['confidence'] <=> $a['confidence']);
            $email = $emails[0]['email'];
        }
        if ($name === null && is_string($email) && $email !== '') {
            $name = $this->names->fromHtml('', $email);
        }

        return [
            'email' => $email,
            'phone' => $phone,
            'emails' => $emails,
            'first_name' => $name['first_name'] ?? null,
            'middle_name' => $name['middle_name'] ?? null,
            'last_name' => $name['last_name'] ?? null,
        ];
    }

    /**
     * @return list<string>
     */
    private function internalContactLinks(string $html, string $origin): array
    {
        if (! preg_match_all('/href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }
        $out = [];
        $originHost = $this->domains->host($origin);
        foreach ($matches[1] as $href) {
            $href = html_entity_decode(trim($href));
            if (! preg_match('/contact|contacteer|over-ons|about|bedrijf|team/i', $href)) {
                continue;
            }
            if (str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }
            $absolute = $this->absolute($href, $origin);
            if ($absolute === '' || $this->domains->host($absolute) !== $originHost) {
                continue;
            }
            $out[] = $absolute;
        }

        return array_slice(array_values(array_unique($out)), 0, 4);
    }

    private function absolute(string $href, string $origin): string
    {
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $this->domains->normalizeWebsite($href) !== '' ? $href : '';
        }
        if (str_starts_with($href, '/')) {
            return $origin.$href;
        }
        if (str_starts_with($href, '#')) {
            return '';
        }

        return $origin.'/'.ltrim($href, '/');
    }
}

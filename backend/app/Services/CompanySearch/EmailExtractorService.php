<?php

namespace App\Services\CompanySearch;

class EmailExtractorService
{
    public function __construct(
        protected DomainService $domains,
    ) {}

    /** @var list<string> */
    private const PREFERRED_LOCAL_PARTS = [
        'info', 'contact', 'sales', 'verkoop', 'office', 'administratie',
        'service', 'support', 'hello', 'hallo', 'klantenservice', 'boekhouding',
    ];

    /** @var list<string> */
    private const JUNK_NEEDLES = [
        'noreply@', 'no-reply@', 'no.reply@', 'privacy@', 'legal@',
        'wordpress@', 'webmaster@', 'sentry.io', 'example.com',
        'domain.com', 'email.com', 'wixpress.com', 'cloudflare.com',
        'png@', 'jpg@', 'jpeg@', 'gif@', 'webp@', 'svg@',
        'support@wix', 'user@', 'test@test.', 'placeholder@',
    ];

    /**
     * @return list<array{email: string, source: string, confidence: int}>
     */
    public function extract(string $html, string $pageUrl = '', string $website = ''): array
    {
        $found = [];
        $pageUrl = strtolower($pageUrl);
        $isContactPage = (bool) preg_match('/contact|over-ons|about|bedrijf|team/i', $pageUrl);

        foreach ($this->mailtoAddresses($html) as $email) {
            $found[] = $this->scored($email, 'website_mailto', 100, $isContactPage, $website);
        }
        foreach ($this->jsonLdAddresses($html) as $email) {
            $found[] = $this->scored($email, 'website_jsonld', 98, $isContactPage, $website);
        }
        foreach ($this->visibleAddresses($html) as $email) {
            $source = $isContactPage ? 'website_contact' : 'website_html';
            $base = $isContactPage ? 95 : 90;
            $found[] = $this->scored($email, $source, $base, $isContactPage, $website);
        }

        return $this->uniqueBest($found);
    }

    public function best(string $html, string $pageUrl = '', string $website = ''): ?string
    {
        $all = $this->extract($html, $pageUrl, $website);

        return $all[0]['email'] ?? null;
    }

    public function isJunk(string $email): bool
    {
        $email = strtolower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        foreach (self::JUNK_NEEDLES as $needle) {
            if (str_contains($email, $needle)) {
                return true;
            }
        }
        if (preg_match('/\.(png|jpe?g|gif|webp|svg|css|js)$/i', $email)) {
            return true;
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function mailtoAddresses(string $html): array
    {
        if (! preg_match_all('/mailto:([A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,})/i', $html, $matches)) {
            return [];
        }

        return array_map('strtolower', $matches[1]);
    }

    /**
     * @return list<string>
     */
    private function jsonLdAddresses(string $html): array
    {
        $out = [];
        if (! preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
            return [];
        }
        foreach ($matches[1] as $json) {
            if (! preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', (string) $json, $emails)) {
                continue;
            }
            foreach ($emails[0] as $email) {
                $out[] = strtolower($email);
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function visibleAddresses(string $html): array
    {
        $text = html_entity_decode(strip_tags($html));
        if (! preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $matches)) {
            return [];
        }

        return array_map('strtolower', $matches[0]);
    }

    /**
     * @return array{email: string, source: string, confidence: int}|null
     */
    private function scored(string $email, string $source, int $base, bool $isContactPage, string $website): ?array
    {
        $email = strtolower(trim($email));
        if ($this->isJunk($email)) {
            return null;
        }

        $confidence = $base;
        $local = strstr($email, '@', true) ?: '';
        if (in_array($local, self::PREFERRED_LOCAL_PARTS, true)) {
            $confidence += 4;
        }
        if ($website !== '' && $this->domains->emailMatchesDomain($email, $website)) {
            $confidence += 6;
        } elseif ($website !== '' && $this->isFreeMailbox($email)) {
            $confidence -= 20;
        } elseif ($website !== '') {
            return null;
        }
        if ($isContactPage && $source === 'website_html') {
            $confidence = max($confidence, 90);
        }

        return [
            'email' => $email,
            'source' => $source,
            'confidence' => max(1, min(100, $confidence)),
        ];
    }

    /**
     * @param  list<array{email: string, source: string, confidence: int}|null>  $rows
     * @return list<array{email: string, source: string, confidence: int}>
     */
    private function uniqueBest(array $rows): array
    {
        $best = [];
        foreach ($rows as $row) {
            if ($row === null) {
                continue;
            }
            $email = $row['email'];
            if (! isset($best[$email]) || $row['confidence'] > $best[$email]['confidence']) {
                $best[$email] = $row;
            }
        }
        $list = array_values($best);
        usort($list, fn (array $a, array $b) => $b['confidence'] <=> $a['confidence']);

        return $list;
    }

    private function isFreeMailbox(string $email): bool
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));

        return in_array($domain, ['gmail.com', 'hotmail.com', 'outlook.com', 'live.nl', 'live.com', 'icloud.com', 'yahoo.com', 'yahoo.nl'], true);
    }
}

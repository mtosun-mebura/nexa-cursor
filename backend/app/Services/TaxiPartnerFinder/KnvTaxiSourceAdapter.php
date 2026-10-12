<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiProspect;
use App\Models\TaxiProspectSource;
use App\Services\CompanySearch\SafeUrlFetcher;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KnvTaxiSourceAdapter
{
    public function __construct(
        protected SafeUrlFetcher $fetcher,
        protected TaxiProspectNormalizer $normalizer,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('taxi-partner-finder.sources.knv.enabled', true)
            && (bool) config('taxi-partner-finder.enabled', true);
    }

    public function sourceUrl(): string
    {
        return (string) config(
            'taxi-partner-finder.sources.knv.url',
            'https://www.knv.nl/ledenoverzicht-taxi/'
        );
    }

    /**
     * @return list<TaxiProspectCandidate>
     */
    public function fetchCandidates(): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('KNV-bron is uitgeschakeld.');
        }

        $html = $this->fetcher->html($this->sourceUrl());
        if (trim($html) === '') {
            throw new RuntimeException('KNV-ledenoverzicht kon niet worden opgehaald.');
        }

        return $this->parseHtml($html, $this->sourceUrl());
    }

    /**
     * @return list<TaxiProspectCandidate>
     */
    public function parseHtml(string $html, ?string $sourceUrl = null): array
    {
        $sourceUrl = $sourceUrl ?: $this->sourceUrl();
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument;
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            Log::warning('KNV HTML parse failed');

            return [];
        }

        $xpath = new DOMXPath($dom);
        $blocks = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " member-background ")]');
        if ($blocks === false || $blocks->length === 0) {
            // Structure changed — surface empty so callers/tests can assert.
            Log::warning('KNV HTML structure changed: no member-background blocks');

            return [];
        }

        $candidates = [];
        foreach ($blocks as $block) {
            if (! $block instanceof DOMElement) {
                continue;
            }
            $candidate = $this->parseMemberBlock($block, $xpath, $sourceUrl);
            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    private function parseMemberBlock(DOMElement $block, DOMXPath $xpath, string $sourceUrl): ?TaxiProspectCandidate
    {
        $id = (string) $block->getAttribute('id');
        $externalRef = $id !== '' ? $id : null;

        $nameNodes = $xpath->query('.//div[contains(@class,"member-info")]//h5', $block);
        $companyName = $nameNodes !== false && $nameNodes->length > 0
            ? trim(html_entity_decode($nameNodes->item(0)?->textContent ?? '', ENT_QUOTES | ENT_HTML5))
            : '';
        if ($companyName === '') {
            return null;
        }

        $contactNodes = $xpath->query('.//div[contains(@class,"member-open")]//div[contains(@class,"member-contact")]', $block);
        $lines = [];
        if ($contactNodes !== false) {
            foreach ($contactNodes as $node) {
                $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(str_replace("\xc2\xa0", ' ', $node->textContent), ENT_QUOTES | ENT_HTML5)) ?? '');
                if ($text !== '') {
                    $lines[] = $text;
                }
            }
        }

        $streetLine = $lines[0] ?? null;
        $postalCityLine = $lines[1] ?? null;
        $province = null;
        foreach ($lines as $line) {
            if ($this->looksLikeProvince($line)) {
                $province = $line;
                break;
            }
        }

        $streetParts = $this->normalizer->splitStreetLine($streetLine);
        $postalCity = $this->normalizer->splitPostalCity($postalCityLine);

        $phone = null;
        $telNodes = $xpath->query('.//a[starts-with(@href,"tel:")]', $block);
        if ($telNodes !== false && $telNodes->length > 0) {
            $phone = $this->normalizer->normalizePhone(
                preg_replace('/^tel:/i', '', (string) $telNodes->item(0)?->getAttribute('href'))
            );
        }

        $email = null;
        $mailNodes = $xpath->query('.//a[starts-with(@href,"mailto:")]', $block);
        if ($mailNodes !== false && $mailNodes->length > 0) {
            $email = $this->normalizer->normalizeEmail(
                preg_replace('/^mailto:/i', '', (string) $mailNodes->item(0)?->getAttribute('href'))
            );
        }

        $website = null;
        $linkNodes = $xpath->query('.//a[@href]', $block);
        if ($linkNodes !== false) {
            foreach ($linkNodes as $link) {
                if (! $link instanceof DOMElement) {
                    continue;
                }
                $href = trim($link->getAttribute('href'));
                if ($href === '' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                    continue;
                }
                if (str_contains($href, 'knv.nl')) {
                    continue;
                }
                $website = $this->normalizer->normalizeWebsite($href);
                if ($website !== null) {
                    break;
                }
            }
        }

        return new TaxiProspectCandidate(
            companyName: $companyName,
            sourceType: TaxiProspectSource::TYPE_KNV,
            externalReference: $externalRef,
            sourceUrl: $sourceUrl,
            street: $streetParts['street'],
            houseNumber: $streetParts['house_number'],
            postalCode: $postalCity['postal_code'],
            city: $postalCity['city'],
            municipality: $postalCity['city'],
            province: $province,
            phone: $phone,
            email: $email,
            website: $website,
            knvMemberStatus: TaxiProspect::KNV_MEMBER,
            confidenceScore: $email ? 75 : 55,
            rawMetadata: [
                'consulted_at' => now()->toIso8601String(),
                'source_label' => 'KNV Ledenoverzicht Taxi',
                'member_id' => $externalRef,
                'raw_lines' => $lines,
            ],
        );
    }

    private function looksLikeProvince(string $line): bool
    {
        $provinces = config('newsletter.provinces', []);
        foreach ($provinces as $province) {
            if (strcasecmp($line, (string) $province) === 0) {
                return true;
            }
        }

        return (bool) preg_match(
            '/^(Drenthe|Flevoland|Friesland|Gelderland|Groningen|Limburg|Noord-Brabant|Noord-Holland|Overijssel|Utrecht|Zeeland|Zuid-Holland)$/i',
            $line
        );
    }
}

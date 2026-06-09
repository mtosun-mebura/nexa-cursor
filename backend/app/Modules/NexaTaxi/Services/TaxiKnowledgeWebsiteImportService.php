<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Modules\NexaTaxi\Models\KnowledgeDocument;
use App\Services\NexaTaxiBookingPricingService;
use Illuminate\Support\Str;

final class TaxiKnowledgeWebsiteImportService
{
    private const BOOKING_MODULE_KEYS = [
        'component:taxi.boekingsmodule',
        'component:taxiroyaal.boekingsmodule',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function importForCompany(int $companyId, string $connection): array
    {
        $candidates = $this->collectCandidates($companyId);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($candidates as $candidate) {
            $title = trim((string) ($candidate['title'] ?? ''));
            $content = trim((string) ($candidate['content'] ?? ''));
            $category = trim((string) ($candidate['category'] ?? 'website'));

            if ($title === '' || $content === '' || mb_strlen($content) < 20) {
                $skipped++;

                continue;
            }

            $existing = KnowledgeDocument::on($connection)
                ->where('title', $title)
                ->where('category', $category)
                ->first();

            if ($existing !== null) {
                $existing->update(['content' => $content]);
                $updated++;

                continue;
            }

            KnowledgeDocument::on($connection)->create([
                'title' => $title,
                'category' => $category,
                'content' => $content,
            ]);
            $created++;
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @return list<array{title: string, content: string, category: string}>
     */
    public function collectCandidates(int $companyId): array
    {
        $pages = WebsitePage::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['slug', 'title', 'content', 'home_sections', 'page_type']);

        $company = Company::query()->find($companyId);

        $documents = [];
        $seen = [];

        foreach ($pages as $page) {
            if (! $this->shouldImportPage($page)) {
                continue;
            }

            $category = $this->categoryForPage($page);
            foreach ($this->chunksFromPage($page) as $chunk) {
                $title = trim((string) ($chunk['title'] ?? ''));
                $content = trim((string) ($chunk['content'] ?? ''));

                if ($title === '' || $this->isPlaceholderTitle($title)) {
                    continue;
                }

                $content = $this->normalizeContent($title, $content);
                if ($content === '' || mb_strlen($content) < 20) {
                    continue;
                }

                $key = mb_strtolower($category.'|'.$title);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $documents[] = [
                    'title' => Str::limit($title, 255, ''),
                    'content' => Str::limit($content, 4000, '…'),
                    'category' => $category,
                ];
            }
        }

        foreach ($this->collectGeneralCandidates($company, $pages) as $candidate) {
            $title = trim((string) ($candidate['title'] ?? ''));
            $content = trim((string) ($candidate['content'] ?? ''));
            $category = trim((string) ($candidate['category'] ?? 'algemeen'));

            if ($title === '' || $content === '' || mb_strlen($content) < 20) {
                continue;
            }

            $key = mb_strtolower($category.'|'.$title);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $documents[] = [
                'title' => Str::limit($title, 255, ''),
                'content' => Str::limit($content, 4000, '…'),
                'category' => $category,
            ];
        }

        return $documents;
    }

    /**
     * @return array<string, mixed>
     */
    private function storedHomeSections(WebsitePage $page): array
    {
        $stored = $page->home_sections;

        return is_array($stored) ? $stored : [];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     * @return list<array{title: string, content: string, category: string}>
     */
    private function collectGeneralCandidates(?Company $company, $pages): array
    {
        $documents = [];

        $contactDocument = $this->buildContactDocument($company, $pages);
        if ($contactDocument !== null) {
            $documents[] = $contactDocument;
        }

        $bookingDocument = $this->buildBookingDocument($pages);
        if ($bookingDocument !== null) {
            $documents[] = $bookingDocument;
        }

        $emailFormDocument = $this->buildEmailInquiryDocument($pages);
        if ($emailFormDocument !== null) {
            $documents[] = $emailFormDocument;
        }

        $navigationDocument = $this->buildWebsiteNavigationDocument($pages);
        if ($navigationDocument !== null) {
            $documents[] = $navigationDocument;
        }

        return $documents;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     * @return array{title: string, content: string, category: string}|null
     */
    private function buildContactDocument(?Company $company, $pages): ?array
    {
        $lines = [];
        $companyName = trim((string) ($company?->name ?? ''));

        if ($companyName !== '') {
            $lines[] = "Bedrijfsnaam: {$companyName}";
        }

        $phone = trim((string) ($company?->phone ?? ''));
        if ($phone !== '') {
            $lines[] = "Telefoon: {$phone}";
        }

        $email = trim((string) ($company?->email ?? $company?->contact_email ?? ''));
        if ($email !== '') {
            $lines[] = "E-mail: {$email}";
        }

        $website = trim((string) ($company?->website ?? ''));
        if ($website !== '') {
            $lines[] = "Website: {$website}";
        }

        $address = $this->formatAddress($company, $pages);
        if ($address !== '') {
            $lines[] = "Adres: {$address}";
        }

        $contactPerson = $this->formatContactPerson($company);
        if ($contactPerson !== '') {
            $lines[] = "Contactpersoon: {$contactPerson}";
        }

        if ($phone === '' && $email === '' && $address === '' && $contactPerson === '') {
            return null;
        }

        if ($lines === []) {
            return null;
        }

        $intro = $companyName !== ''
            ? "Klanten kunnen {$companyName} op de volgende manieren bereiken:"
            : 'Klanten kunnen ons op de volgende manieren bereiken:';

        $content = $intro."\n\n".implode("\n", $lines);
        $content .= "\n\nVoor dringende vragen of het maken van een rit kunnen klanten bellen of mailen. Op de website staan ook contact- en boekingsmogelijkheden.";

        return [
            'title' => 'Contactgegevens',
            'content' => $content,
            'category' => 'contact',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     * @return array{title: string, content: string, category: string}|null
     */
    private function buildBookingDocument($pages): ?array
    {
        foreach ($pages as $page) {
            $storedSections = $this->storedHomeSections($page);
            $sections = $page->getHomeSections();
            if (! $this->bookingModuleIsVisible($storedSections)) {
                continue;
            }

            $moduleConfig = $this->resolveBookingModuleConfig($sections);
            $title = trim((string) ($moduleConfig['title'] ?? 'Boek eenvoudig je taxirit'));
            $stepLabels = is_array($moduleConfig['step_labels'] ?? null) ? $moduleConfig['step_labels'] : [];
            $texts = is_array($moduleConfig['texts'] ?? null) ? $moduleConfig['texts'] : [];
            $stepOrder = is_array($moduleConfig['step_order'] ?? null) ? $moduleConfig['step_order'] : ['trip', 'baggage', 'offers', 'contact', 'confirm'];

            $logicalLabels = [
                'trip' => $stepLabels['step3'] ?? 'Reisgegevens',
                'baggage' => $stepLabels['step1'] ?? 'Bagage',
                'offers' => $stepLabels['step2'] ?? 'Aanbiedingen',
                'contact' => $stepLabels['step4'] ?? 'Contactgegevens',
                'confirm' => $stepLabels['step5'] ?? 'Bevestiging',
            ];

            $pageLabel = $this->pageLabelForImport($page);
            $steps = [];
            $stepNumber = 1;
            foreach ($stepOrder as $logicalStep) {
                if (! isset($logicalLabels[$logicalStep])) {
                    continue;
                }
                $steps[] = "{$stepNumber}. {$logicalLabels[$logicalStep]}";
                $stepNumber++;
            }

            $submitLabel = trim((string) ($texts['submit_button_text'] ?? 'Boeking versturen'));
            $successMessage = trim((string) ($texts['success_message'] ?? 'Bedankt! Je boeking is ontvangen.'));

            $content = "Op {$pageLabel} staat een online boekingsformulier waarmee klanten direct een taxirit kunnen aanvragen.";
            if ($title !== '') {
                $content .= " Het formulier heet \"{$title}\".";
            }
            $content .= "\n\nStappen in het boekingsformulier:\n".implode("\n", $steps);
            $content .= "\n\nNa het invullen klikken klanten op \"{$submitLabel}\". {$successMessage}";
            $content .= "\n\nKlanten vullen ophaladres, bestemming, datum/tijd, bagage, contactgegevens en bevestigen de boeking in het formulier op de website.";

            return [
                'title' => 'Taxirit boeken op de website',
                'content' => $content,
                'category' => 'algemeen',
            ];
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     * @return array{title: string, content: string, category: string}|null
     */
    private function buildEmailInquiryDocument($pages): ?array
    {
        foreach ($pages as $page) {
            $storedSections = $this->storedHomeSections($page);
            $sections = $page->getHomeSections();
            if (! $this->emailTemplateIsVisible($storedSections)) {
                continue;
            }

            $emailSection = is_array($storedSections['email_template'] ?? null)
                ? $storedSections['email_template']
                : [];
            $formTitle = trim((string) ($emailSection['title'] ?? 'Informatie aanvragen'));
            $pageLabel = $this->pageLabelForImport($page);

            $content = "Op {$pageLabel} staat een contactformulier: \"{$formTitle}\".";
            $content .= ' Klanten kunnen daar hun gegevens invullen om een vraag, offerte-aanvraag of algemene vraag te sturen.';
            $content .= ' Het formulier is zichtbaar op de website en verstuurt het bericht per e-mail naar het bedrijf.';
            $content .= "\n\nVerwijs klanten die een algemene vraag hebben, geen directe rit willen boeken, of liever schriftelijk contact opnemen naar dit formulier op de website.";

            return [
                'title' => 'Contactformulier op de website',
                'content' => $content,
                'category' => 'algemeen',
            ];
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     * @return array{title: string, content: string, category: string}|null
     */
    private function buildWebsiteNavigationDocument($pages): ?array
    {
        $lines = [];

        foreach ($pages as $page) {
            $storedSections = $this->storedHomeSections($page);
            $footer = is_array($storedSections['footer'] ?? null) ? $storedSections['footer'] : [];
            if ($footer === []) {
                continue;
            }

            $pageLabel = $this->pageLabelForImport($page);

            foreach (['quick_links', 'support_links'] as $linkGroup) {
                $title = trim((string) ($footer[$linkGroup.'_title'] ?? ''));
                $links = is_array($footer[$linkGroup] ?? null) ? $footer[$linkGroup] : [];

                foreach ($links as $link) {
                    if (! is_array($link)) {
                        continue;
                    }

                    $label = trim((string) ($link['label'] ?? ''));
                    if ($label === '') {
                        continue;
                    }

                    $lines[] = $title !== ''
                        ? "{$pageLabel} – {$title}: {$label}"
                        : "{$pageLabel}: {$label}";
                }
            }

            $tagline = $this->plainText((string) ($footer['tagline'] ?? ''), true);
            if ($tagline !== '' && mb_strlen($tagline) >= 20) {
                $lines[] = "{$pageLabel} – korte omschrijving: {$tagline}";
            }
        }

        $lines = array_values(array_unique($lines));
        if ($lines === []) {
            return null;
        }

        $content = "Overzicht van veelgebruikte onderdelen en links op de website:\n\n";
        $content .= implode("\n", array_map(static fn (string $line): string => '- '.$line, $lines));
        $content .= "\n\nGebruik dit overzicht om klanten te verwijzen naar diensten, contact, privacy, voorwaarden of hulp op de website.";

        return [
            'title' => 'Veelgestelde vragen over de website',
            'content' => $content,
            'category' => 'algemeen',
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, WebsitePage>  $pages
     */
    private function formatAddress(?Company $company, $pages): string
    {
        if ($company !== null) {
            $parts = array_filter([
                trim(implode(' ', array_filter([
                    trim((string) ($company->street ?? '')),
                    trim((string) ($company->house_number ?? '')),
                    trim((string) ($company->house_number_extension ?? '')),
                ]))),
                trim((string) ($company->postal_code ?? '')),
                trim((string) ($company->city ?? '')),
                trim((string) ($company->country ?? '')),
            ]);

            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        foreach ($pages as $page) {
            $storedSections = $this->storedHomeSections($page);
            $footer = is_array($storedSections['footer'] ?? null) ? $storedSections['footer'] : [];

            $parts = array_filter([
                trim((string) ($footer['map_street'] ?? '')),
                trim((string) ($footer['map_huisnummer'] ?? '')),
                trim((string) ($footer['map_postcode'] ?? '')),
                trim((string) ($footer['map_city'] ?? '')),
            ]);

            if ($parts !== []) {
                return implode(', ', $parts);
            }
        }

        return '';
    }

    private function formatContactPerson(?Company $company): string
    {
        if ($company === null) {
            return '';
        }

        $name = trim(implode(' ', array_filter([
            trim((string) ($company->contact_first_name ?? '')),
            trim((string) ($company->contact_middle_name ?? '')),
            trim((string) ($company->contact_last_name ?? '')),
        ])));

        $contactEmail = trim((string) ($company->contact_email ?? ''));

        if ($name === '' && $contactEmail === '') {
            return '';
        }

        if ($name !== '' && $contactEmail !== '') {
            return "{$name} ({$contactEmail})";
        }

        return $name !== '' ? $name : $contactEmail;
    }

    private function pageLabelForImport(WebsitePage $page): string
    {
        if ($page->page_type === 'home' || mb_strtolower((string) ($page->slug ?? '')) === 'home') {
            return 'de homepage';
        }

        $title = trim((string) ($page->title ?? ''));
        if ($title !== '') {
            return "de pagina \"{$title}\"";
        }

        $slug = trim((string) ($page->slug ?? ''));

        return $slug !== '' ? "de pagina /{$slug}" : 'de website';
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function bookingModuleIsVisible(array $sections): bool
    {
        $visibility = is_array($sections['visibility'] ?? null) ? $sections['visibility'] : [];
        $sectionOrder = $sections['section_order'] ?? [];
        $orderedKeys = is_array($sectionOrder) ? $sectionOrder : [];

        foreach (self::BOOKING_MODULE_KEYS as $key) {
            if (! isset($sections[$key]) && ! in_array($key, $orderedKeys, true)) {
                continue;
            }

            if (($visibility[$key] ?? true) === false || ($visibility[$key] ?? true) === '0') {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function emailTemplateIsVisible(array $sections): bool
    {
        $visibility = is_array($sections['visibility'] ?? null) ? $sections['visibility'] : [];
        $sectionOrder = $sections['section_order'] ?? [];
        $orderedKeys = is_array($sectionOrder) ? $sectionOrder : [];

        if (! isset($sections['email_template']) && ! in_array('email_template', $orderedKeys, true)) {
            return false;
        }

        return ($visibility['email_template'] ?? true) !== false
            && ($visibility['email_template'] ?? true) !== '0';
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    private function resolveBookingModuleConfig(array $sections): array
    {
        foreach (self::BOOKING_MODULE_KEYS as $key) {
            if (isset($sections[$key]) && is_array($sections[$key])) {
                return app(NexaTaxiBookingPricingService::class)->mergeSectionConfig($sections[$key]);
            }
        }

        return app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
    }

    private function shouldImportPage(WebsitePage $page): bool
    {
        $slug = mb_strtolower(trim((string) ($page->slug ?? '')));

        foreach (['dienst', 'voorwaard', 'terms', 'contact', 'privacy', 'help', 'faq', 'over-ons', 'about'] as $needle) {
            if (str_contains($slug, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isPlaceholderTitle(string $title): bool
    {
        $normalized = mb_strtolower(trim($title));

        return in_array($normalized, [
            'business collaboration',
            'engineering & services',
            'consulting',
            'featured services',
            'diensten',
            'home',
        ], true);
    }

    private function categoryForPage(WebsitePage $page): string
    {
        $slug = mb_strtolower(trim((string) ($page->slug ?? '')));

        if (str_contains($slug, 'dienst')) {
            return 'diensten';
        }
        if (str_contains($slug, 'voorwaard') || str_contains($slug, 'terms')) {
            return 'voorwaarden';
        }
        if (str_contains($slug, 'contact')) {
            return 'contact';
        }
        if (str_contains($slug, 'privacy')) {
            return 'privacy';
        }

        return 'website';
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    private function chunksFromPage(WebsitePage $page): array
    {
        $chunks = [];

        foreach ($this->chunksFromHomeSections($page->home_sections) as $chunk) {
            $chunks[] = $chunk;
        }

        foreach ($this->chunksFromEditorContent($page->content) as $chunk) {
            if (($chunk['title'] ?? '') === '') {
                $chunk['title'] = $this->plainText((string) ($page->title ?? ''));
            }
            $chunks[] = $chunk;
        }

        return $chunks;
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    private function chunksFromHomeSections(mixed $sections): array
    {
        if (! is_array($sections)) {
            return [];
        }

        $chunks = [];

        foreach ($sections as $section) {
            if (! is_array($section)) {
                continue;
            }

            if (isset($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $title = $this->plainText((string) ($item['title'] ?? ''));
                    $description = $this->plainText((string) ($item['description'] ?? ''), true);
                    $rawTextField = (string) ($item['text'] ?? '');
                    $textField = $this->plainText($rawTextField, true);

                    if ($title === '' && $rawTextField !== '') {
                        $title = $this->extractTitleFromHtml($rawTextField) ?? $this->extractTitleFromPlainText($textField) ?? '';
                    }

                    if ($textField !== '' && $title !== '') {
                        $textField = $this->removeLeadingTitleFromText($textField, $title);
                    }

                    $content = $description !== '' ? $description : $textField;
                    if ($title === '' || $content === '') {
                        continue;
                    }

                    $chunks[] = ['title' => $title, 'content' => $content];
                }
            }
        }

        return $chunks;
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    private function chunksFromEditorContent(mixed $content): array
    {
        if (! is_string($content) || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        if (! is_array($decoded) || ! isset($decoded['blocks']) || ! is_array($decoded['blocks'])) {
            $plain = $this->plainText($content, true);

            return $plain !== '' ? [['title' => '', 'content' => $plain]] : [];
        }

        $chunks = [];
        foreach ($decoded['blocks'] as $block) {
            if (! is_array($block) || ! isset($block['data']) || ! is_array($block['data'])) {
                continue;
            }

            $data = $block['data'];
            if (isset($data['text']) && is_string($data['text'])) {
                $text = $this->plainText($data['text'], true);
                if ($text !== '') {
                    $chunks[] = ['title' => '', 'content' => $text];
                }
            }
        }

        return $chunks;
    }

    private function normalizeContent(string $title, string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        if (! str_starts_with(mb_strtolower($content), mb_strtolower($title))) {
            return $content;
        }

        return trim($this->removeLeadingTitleFromText($content, $title));
    }

    private function extractTitleFromHtml(string $html): ?string
    {
        if (preg_match('/<strong[^>]*>(.*?)<\/strong>/is', $html, $matches) !== 1) {
            return null;
        }

        $title = $this->plainText((string) ($matches[1] ?? ''));

        return $title !== '' ? $title : null;
    }

    private function extractTitleFromPlainText(string $text): ?string
    {
        $firstLine = trim(strtok($text, "\n") ?: '');

        return $firstLine !== '' ? $firstLine : null;
    }

    private function removeLeadingTitleFromText(string $text, string $title): string
    {
        $pattern = '/^'.preg_quote($title, '/').'(\s+|$)/iu';

        return trim(preg_replace($pattern, '', $text) ?? $text);
    }

    private function plainText(string $value, bool $preserveParagraphs = false): string
    {
        $value = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $value) ?? $value;
        $value = preg_replace('/<br\s*\/?>/i', "\n", $value) ?? $value;
        $value = preg_replace('/<\/li>/i', "\n", $value) ?? $value;
        $text = strip_tags($value);

        if ($preserveParagraphs) {
            $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
            $text = preg_replace("/\n{3,}/u", "\n\n", $text) ?? $text;

            return trim($text);
        }

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}

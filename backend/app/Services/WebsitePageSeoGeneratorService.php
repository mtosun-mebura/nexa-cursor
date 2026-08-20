<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WebsitePageSeoGeneratorService
{
    private const META_MAX = 160;

    private const META_MIN = 150;

    private const TITLE_MAX = 55;

    private const PAGE_CONTENT_MAX = 8000;

    /**
     * @param  array{
     *     title?: string,
     *     page_type?: string,
     *     module_name?: string|null,
     *     module_display_name?: string|null,
     *     slug?: string,
     *     site_name?: string,
     *     site_description?: string,
     *     company_name?: string|null,
     *     include_sections?: bool,
     *     page_content?: string,
     *     home_sections?: array<string, mixed>,
     * }  $input
     * @return array{
     *     title: string,
     *     meta_description: string,
     *     sections: array<string, array<string, string>>,
     *     tips: list<string>,
     *     source: string,
     * }
     */
    public function generate(array $input): array
    {
        $context = $this->buildContext($input);

        $fromLlm = $this->tryGenerateWithOpenAi($context);
        if ($fromLlm !== null) {
            return $this->normalizeResult($fromLlm, $context, 'openai');
        }

        return $this->normalizeResult($this->generateWithTemplates($context), $context, 'template');
    }

    /**
     * Schrijf gegenereerde hero-teksten terug in de eerste hero-sectie.
     *
     * @param  array<string, mixed>  $sections
     * @param  array<string, string>  $hero
     * @return array<string, mixed>|null  Bijgewerkte secties, of null als er geen hero is
     */
    public function applyHeroToHomeSections(array $sections, array $hero): ?array
    {
        $heroKey = $this->firstHeroSectionKey($sections);
        if ($heroKey === null) {
            return null;
        }

        $block = is_array($sections[$heroKey] ?? null) ? $sections[$heroKey] : [];
        $changed = false;
        foreach (['title', 'subtitle', 'cta_primary_text', 'cta_secondary_text'] as $field) {
            $value = $this->replaceEmDash(trim((string) ($hero[$field] ?? '')));
            if ($value === '') {
                continue;
            }
            if ($field === 'title') {
                $current = trim((string) ($block[$field] ?? ''));
                $incomingWasTruncated = $this->isTruncatedTitle($value);
                $value = $this->replaceEmDash($this->completeTruncatedHeroTitle($value));
                if ($this->isTruncatedTitle($value)) {
                    continue;
                }
                if ($current !== '' && ! $this->isTruncatedTitle($current) && ($incomingWasTruncated || mb_strlen($current) >= mb_strlen($value))) {
                    continue;
                }
            }
            if (($block[$field] ?? null) !== $value) {
                $block[$field] = $value;
                $changed = true;
            }
        }
        if (! $changed) {
            return null;
        }

        $sections[$heroKey] = $block;

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    private function firstHeroSectionKey(array $sections): ?string
    {
        $order = $sections['section_order'] ?? [];
        $keys = is_array($order) ? $order : [];
        $keys = array_merge($keys, array_keys($sections));
        foreach ($keys as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            $base = preg_replace('/_\d+$/', '', $key) ?? $key;
            if ($base === 'hero') {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function buildContext(array $input): array
    {
        $pageType = (string) ($input['page_type'] ?? 'custom');
        $title = trim((string) ($input['title'] ?? ''));
        $siteName = trim((string) ($input['site_name'] ?? config('app.name', 'Nexa')));
        $siteDescription = trim((string) ($input['site_description'] ?? ''));
        $moduleDisplay = trim((string) ($input['module_display_name'] ?? $input['module_name'] ?? ''));
        $companyName = trim((string) ($input['company_name'] ?? ''));

        $brand = $companyName !== '' ? $companyName : $siteName;
        if ($moduleDisplay !== '' && $moduleDisplay !== $brand) {
            $brand = $brand.' · '.$moduleDisplay;
        }

        $pageContent = $this->normalizePageContent($input);

        return [
            'title' => $title,
            'page_type' => $pageType,
            'page_type_label' => $this->pageTypeLabel($pageType),
            'slug' => trim((string) ($input['slug'] ?? '')),
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'module_name' => trim((string) ($input['module_name'] ?? '')),
            'module_display_name' => $moduleDisplay,
            'company_name' => $companyName,
            'brand' => $brand,
            'include_sections' => (bool) ($input['include_sections'] ?? true),
            'page_content' => $pageContent,
            'home_sections' => is_array($input['home_sections'] ?? null) ? $input['home_sections'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function normalizePageContent(array $input): string
    {
        $fromText = trim((string) ($input['page_content'] ?? ''));
        if ($fromText !== '') {
            $fromText = html_entity_decode(strip_tags($fromText), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $fromText = preg_replace("/[ \t]+/u", ' ', $fromText) ?? $fromText;

            return $this->limitChars(trim($fromText), self::PAGE_CONTENT_MAX);
        }

        $sections = $input['home_sections'] ?? null;
        if (! is_array($sections)) {
            return '';
        }

        return $this->limitChars($this->extractTextFromHomeSections($sections), self::PAGE_CONTENT_MAX);
    }

    /**
     * @param  array<string, mixed>  $sections
     */
    public function extractTextFromHomeSections(array $sections): string
    {
        $order = $sections['section_order'] ?? null;
        $keys = is_array($order)
            ? array_values(array_filter($order, fn ($key) => is_string($key) && $key !== ''))
            : array_keys($sections);

        $blocks = [];
        foreach ($keys as $key) {
            if (in_array($key, ['section_order', 'visibility', 'removed_section_keys', 'footer', 'copyright'], true)) {
                continue;
            }
            $data = $sections[$key] ?? null;
            $lines = [];
            $this->collectTextValues($data, $lines, 0);
            $lines = $this->uniqueLines($lines);
            if ($lines === []) {
                continue;
            }
            $blocks[] = '['.$this->sectionLabel((string) $key).']'."\n".implode("\n", $lines);
        }

        return trim(implode("\n\n", $blocks));
    }

    private function pageTypeLabel(string $pageType): string
    {
        return match ($pageType) {
            'home' => 'Home',
            'about' => 'Over ons',
            'contact' => 'Contact',
            'module' => 'Modulepagina',
            default => 'Pagina',
        };
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function tryGenerateWithOpenAi(array $context): ?array
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = config('services.openai.model', 'gpt-4o-mini');
        $prompt = $this->buildLlmPrompt($context);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.4,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Je bent een Nederlandse SEO-strateeg. Je schrijft Google-titels en meta-omschrijvingen die aangeklikt worden én ranken: zoekwoord vooraan, uniek per pagina, feitelijk. Je plakt nooit paginatekst, knoppen of afgekapte koppen aan elkaar. Gebruik geen gedachtestreepje (—); schrijf een dubbele punt, komma of een nieuwe zin. Antwoord uitsluitend met geldig JSON.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = $response->json('choices.0.message.content');
            if (! is_string($content) || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function buildLlmPrompt(array $context): string
    {
        $payload = json_encode([
            'merk' => $context['brand'],
            'site_naam' => $context['site_name'],
            'paginatype' => $context['page_type_label'],
            'huidige_titel' => $context['title'],
            'site_omschrijving' => $context['site_description'],
            'slug' => $context['slug'],
            'hero_mee_invullen' => $context['include_sections'],
            'pagina_inhoud' => $context['page_content'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
Schrijf de Google-snippet voor deze Nederlandse websitepagina. Doel: hoge vindbaarheid op wat bezoekers intypen (dienst, product, plaats, merkprobleem).

Haal zoekwoorden uit pagina_inhoud (diensten, doelgroep, plaats, productnamen). Verzin geen extra diensten. Schrijf WEL nieuwe SEO-zinnen: niet knippen-plakken.

HTML-title in Google wordt: "{title} - {site_naam}".
Dus: title 40–55 tekens, zoekwoord vooraan, GEEN merk/sitenaam erachter, GEEN …, geen hero-vraag letterlijk.

Verboden in title én meta_description:
- Knopteksten (Start hier, Meer lezen, Gratis account, Neem contact op, Bekijk prijzen, …)
- Afkappingsteken … of halve zinnen
- Dezelfde zin twee keer
- UI-koppen plakken (Herkenbaar?, Start vandaag, etc.)

JSON-schema:
{
  "title": "zoekwoordrijke Google-titel, 40-55 tekens, geen merk erachter",
  "meta_description": "155-160 tekens, twee volle zinnen, eindig op een punt. Zin 1: wie + wat + primair zoekwoord. Zin 2: voordeel + volgende stap.",
  "sections": {
    "hero": {
      "title": "volledige hero-kop op de pagina (mag lang), niet de Google-titel, niet afkappen",
      "subtitle": "1-2 feitelijke zinnen, plain text",
      "cta_primary_text": "korte primaire knop (max 24 tekens)",
      "cta_secondary_text": "korte secundaire knop (max 24 tekens)"
    }
  },
  "tips": ["max 3 korte SEO-tips specifiek voor deze pagina"]
}

Regels:
- Nederlands, concreet, scanbaar.
- Meta is de Google-snippet die aangeklikt moet worden: geen samenvatting van knoppen.
- Geen gedachtestreepje (—). Gebruik een dubbele punt, komma of een nieuwe zin.
- Lege pagina_inhoud: schrijf op paginatype, slug en merk, zonder vage claims.
- sections.hero alleen als hero_mee_invullen true is.

Context: {$payload}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function generateWithTemplates(array $context): array
    {
        $brand = (string) $context['brand'];
        $pageLabel = (string) $context['page_type_label'];
        $title = (string) $context['title'];

        $pageTitle = $this->seoTitleFromContext($context, $title !== '' ? $title : $pageLabel);
        $meta = $this->composeSeoMeta($context);

        $sections = [];
        if ($context['include_sections']) {
            $sections['hero'] = $this->heroSectionCopy($context, $pageTitle, $brand);
        }

        return [
            'title' => $pageTitle,
            'meta_description' => $meta,
            'sections' => $sections,
            'tips' => $this->seoTips($context),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seoTitleFromContext(array $context, string $fallback): string
    {
        $current = $this->stripBrandSuffix(trim((string) ($context['title'] ?? '')), $context);
        if (! $this->isWeakSeoTitle($current)) {
            return $this->fitSeoTitle($current);
        }

        $fromSite = $this->stripBrandSuffix(rtrim(trim((string) ($context['site_description'] ?? '')), '.!?'), $context);
        if (! $this->isWeakSeoTitle($fromSite) && mb_strlen($fromSite) >= 20 && mb_strlen($fromSite) <= 60) {
            return $this->fitSeoTitle($fromSite);
        }

        $heading = $this->stripBrandSuffix(
            $this->completeTruncatedHeroTitle($this->firstHeadingFromContent((string) ($context['page_content'] ?? ''))),
            $context
        );
        if (! $this->isWeakSeoTitle($heading) && mb_strlen($heading) <= 60) {
            return $this->fitSeoTitle($heading);
        }

        $composed = $this->composeSeoTitle($context);
        if ($composed !== '') {
            return $this->fitSeoTitle($composed);
        }

        return $this->fitSeoTitle($fallback !== '' ? $fallback : (string) ($context['page_type_label'] ?? 'Pagina'));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function heroSectionCopy(array $context, string $pageTitle, string $brand): array
    {
        $pageType = (string) $context['page_type'];
        $pageContent = (string) ($context['page_content'] ?? '');
        $contentLead = $this->firstSentences(implode(' ', $this->seoSourceLines($pageContent)), 2);
        $heroTitle = $this->resolveHeroTitle($context, '', $pageTitle);
        if ($heroTitle === '') {
            $heroTitle = $pageTitle !== '' ? $pageTitle : "Welkom bij {$brand}";
        }

        $defaults = match ($pageType) {
            'home' => [
                'title' => $heroTitle,
                'subtitle' => $contentLead !== '' ? $contentLead : "{$brand} helpt u met professionele oplossingen. Ontdek onze diensten en neem vrijblijvend contact op.",
                'cta_primary_text' => 'Neem contact op',
                'cta_secondary_text' => 'Meer informatie',
            ],
            'about' => [
                'title' => $heroTitle !== '' ? $heroTitle : "Over {$brand}",
                'subtitle' => $contentLead !== '' ? $contentLead : "Wij zijn {$brand}: ervaring, transparantie en focus op resultaat voor onze klanten.",
                'cta_primary_text' => 'Ons team',
                'cta_secondary_text' => 'Contact',
            ],
            'contact' => [
                'title' => $heroTitle !== '' ? $heroTitle : 'Contact',
                'subtitle' => $contentLead !== '' ? $contentLead : "Vragen voor {$brand}? Stuur een bericht: wij reageren zo snel mogelijk.",
                'cta_primary_text' => 'Bericht sturen',
                'cta_secondary_text' => 'Bel ons',
            ],
            default => [
                'title' => $heroTitle,
                'subtitle' => $contentLead !== '' ? $contentLead : "Alles over {$heroTitle} bij {$brand}. Duidelijke uitleg en een sterke volgende stap.",
                'cta_primary_text' => 'Start hier',
                'cta_secondary_text' => 'Meer lezen',
            ],
        };

        return $defaults;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function seoTips(array $context): array
    {
        $keywords = $this->keywordsFromContext($context);
        $topic = $this->topicPhrase($keywords) ?: 'uw belangrijkste onderwerp';

        return [
            "Zet het zoekwoord ({$topic}) in de eerste 40 tekens van de Google-titel: zo ziet Google meteen waar de pagina over gaat.",
            'De meta-omschrijving is uw snippet in Google: wie u bent, wat u biedt, en welke actie de bezoeker kan nemen. Uniek per pagina, geen knopteksten.',
            'Op de live pagina staat automatisch structured data (Organization + WebPage) voor Google Zoeken.',
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $context
     * @return array{
     *     title: string,
     *     meta_description: string,
     *     sections: array<string, array<string, string>>,
     *     tips: list<string>,
     *     source: string,
     * }
     */
    private function normalizeResult(array $raw, array $context, string $source): array
    {
        $title = $this->replaceEmDash(trim((string) ($raw['title'] ?? $context['title'] ?? '')));
        if ($title === '') {
            $title = (string) ($context['page_type_label'] ?? 'Pagina');
        }
        $title = $this->fitSeoTitle($this->stripBrandSuffix($title, $context));
        if ($this->isWeakSeoTitle($title)) {
            $title = $this->seoTitleFromContext($context, $title);
        }

        $meta = $this->replaceEmDash(trim((string) ($raw['meta_description'] ?? '')));
        if ($meta === '' || $this->looksLikeContentDump($meta)) {
            $meta = $this->composeSeoMeta($context);
        } else {
            $meta = $this->fitMetaDescription($meta, $context);
            if ($this->looksLikeContentDump($meta)) {
                $meta = $this->composeSeoMeta($context);
            }
        }
        $meta = $this->fitMetaDescription($meta, $context);
        if ($this->looksLikeContentDump($meta)) {
            $meta = $this->fitMetaDescription($this->composeSeoMeta($context), $context);
        }

        $sections = [];
        if ($context['include_sections'] && isset($raw['sections']) && is_array($raw['sections'])) {
            $hero = $raw['sections']['hero'] ?? null;
            if (is_array($hero)) {
                $heroTitle = $this->resolveHeroTitle($context, trim((string) ($hero['title'] ?? '')), $title);
                $sections['hero'] = array_filter([
                    'title' => $this->replaceEmDash($heroTitle),
                    'subtitle' => $this->replaceEmDash(trim(strip_tags((string) ($hero['subtitle'] ?? '')))),
                    'cta_primary_text' => $this->replaceEmDash(trim((string) ($hero['cta_primary_text'] ?? ''))),
                    'cta_secondary_text' => $this->replaceEmDash(trim((string) ($hero['cta_secondary_text'] ?? ''))),
                ], fn ($v) => $v !== '');
            }
        }
        if ($sections === [] && $context['include_sections']) {
            $copy = $this->heroSectionCopy(
                $context,
                $title,
                (string) $context['brand']
            );
            foreach ($copy as $key => $value) {
                $copy[$key] = $this->replaceEmDash((string) $value);
            }
            $sections['hero'] = $copy;
        }

        $tips = [];
        if (isset($raw['tips']) && is_array($raw['tips'])) {
            foreach ($raw['tips'] as $tip) {
                $t = $this->replaceEmDash(trim((string) $tip));
                if ($t !== '') {
                    $tips[] = $t;
                }
            }
        }
        if ($tips === []) {
            $tips = $this->seoTips($context);
        }

        return [
            'title' => $title,
            'meta_description' => $meta,
            'sections' => $sections,
            'tips' => array_slice($tips, 0, 5),
            'source' => $source,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function stripBrandSuffix(string $title, array $context): string
    {
        $names = array_filter([
            (string) ($context['brand'] ?? ''),
            (string) ($context['site_name'] ?? ''),
            (string) ($context['company_name'] ?? ''),
            (string) ($context['module_display_name'] ?? ''),
        ]);

        foreach ($names as $name) {
            $quoted = preg_quote($name, '/');
            $title = preg_replace('/\s*[\|\-–—]\s*'.$quoted.'\s*$/iu', '', $title) ?? $title;
        }

        return trim($title);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function composeSeoTitle(array $context): string
    {
        $blob = mb_strtolower(implode(' ', array_filter([
            (string) ($context['page_content'] ?? ''),
            (string) ($context['title'] ?? ''),
            (string) ($context['brand'] ?? ''),
            (string) ($context['slug'] ?? ''),
        ])));
        if (str_contains($blob, 'boek') && (str_contains($blob, 'taxi') || str_contains($blob, 'rit'))) {
            $title = str_contains($blob, 'taxi') ? 'Taxi online laten boeken' : 'Ritten online laten boeken';
            if (str_contains($blob, 'rit') && ! str_contains(mb_strtolower($title), 'ritten')) {
                $withRitten = $title.' zonder gemiste ritten';
                if (mb_strlen($withRitten) <= self::TITLE_MAX) {
                    return $withRitten;
                }
            }
            if (str_contains($blob, 'chauffeur')) {
                $withApp = $title.' en chauffeur-app';
                if (mb_strlen($withApp) <= self::TITLE_MAX) {
                    return $withApp;
                }
            }

            return $title;
        }

        $pageType = (string) ($context['page_type'] ?? 'custom');
        $collocations = $this->collocationsInContent((string) ($context['page_content'] ?? ''));
        $keywords = $this->keywordsFromContext($context);
        $seed = $collocations[0] ?? ($keywords[0] ?? '');
        $extra = $collocations[1] ?? ($keywords[1] ?? null);

        if ($seed === '') {
            return match ($pageType) {
                'home' => 'Diensten, werkwijze en contact',
                'about' => 'Over ons: team en werkwijze',
                'contact' => 'Contact, offerte of afspraak',
                default => (string) ($context['page_type_label'] ?? 'Pagina'),
            };
        }

        $title = $this->mbUcfirst($seed);
        if (in_array(mb_strtolower($title), ['contact', 'home', 'pagina', 'over ons'], true)) {
            return match ($pageType) {
                'home' => 'Diensten, werkwijze en contact',
                'about' => 'Over ons: team en werkwijze',
                'contact' => 'Contact, offerte of afspraak',
                default => (string) ($context['page_type_label'] ?? 'Pagina'),
            };
        }
        if (is_string($extra) && $extra !== '' && ! str_contains(mb_strtolower($title), mb_strtolower($extra))) {
            $withEn = $title.' en '.$extra;
            if (mb_strlen($withEn) <= self::TITLE_MAX) {
                return $withEn;
            }
        }

        return $title;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function composeSeoMeta(array $context): string
    {
        $brand = trim((string) ($context['brand'] ?? ''));
        $keywords = $this->keywordsFromContext($context);
        $lines = $this->seoSourceLines((string) ($context['page_content'] ?? ''));
        $topic = $this->seoTitleFromContext($context, (string) ($context['page_type_label'] ?? 'Deze pagina'));
        $promise = $this->seoPromise($keywords, $lines, $context);
        if ($this->sharesTopicWords($topic, $promise)) {
            $promise = $this->seoPromiseAlternate($keywords, $lines, $context);
        }
        $pageType = (string) ($context['page_type'] ?? 'custom');

        $lead = match ($pageType) {
            'contact' => ($brand !== '' ? "Neem contact op met {$brand}" : 'Neem contact op')
                .'. Stel uw vraag, vraag een offerte of plan een afspraak: wij reageren snel en duidelijk.',
            default => $this->seoLead($topic, $brand, $promise),
        };

        return $this->fitMetaDescription($lead, $context);
    }

    private function seoLead(string $topic, string $brand, string $promise): string
    {
        $topic = rtrim($this->mbUcfirst($topic), '.:');
        $promise = $this->mbUcfirst(trim($promise, " \t."));
        $open = $brand !== '' && $topic !== '' && ! str_contains(mb_strtolower($topic), mb_strtolower($brand))
            ? "{$topic} bij {$brand}"
            : ($topic !== '' ? $topic : $brand);
        if ($open === '') {
            $open = 'Deze pagina';
        }
        if ($promise !== '') {
            return $open.'. '.$promise.'.';
        }

        return $open.'. Concrete informatie, een heldere werkwijze en een duidelijke volgende stap.';
    }

    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $context
     */
    private function seoPromise(array $keywords, array $lines, array $context): string
    {
        $blob = mb_strtolower(implode(' ', array_merge($keywords, $lines, [
            (string) ($context['page_content'] ?? ''),
            (string) ($context['site_description'] ?? ''),
        ])));

        if (preg_match('/\b(boek(en|ing)?|reserv(eer|eren|atie))\b/u', $blob)
            && preg_match('/\b(ritten?|taxi|taxibedrijf|taxibedrijven)\b/u', $blob)) {
            return 'klanten reserveren 24/7 zelf; u plant, dispatcht en factureert in één systeem';
        }
        if (str_contains($blob, 'contractvervoer') || (str_contains($blob, 'excel') && str_contains($blob, 'rit'))) {
            return 'plan vaste ritten zonder Excel, met status, afmeldingen en facturatie';
        }
        if (str_contains($blob, 'website builder') || (str_contains($blob, 'website') && str_contains($blob, 'cms'))) {
            return 'eigen website met boeking, thema en SEO, zonder apart CMS';
        }
        if (str_contains($blob, 'chauffeur')) {
            return 'stuur chauffeurs via de app, met dispatch en betaling in één overzicht';
        }

        $siteDescription = rtrim(trim((string) ($context['site_description'] ?? '')), '.');
        if (mb_strlen($siteDescription) >= 28) {
            return $siteDescription;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) >= 40 && mb_strlen($line) <= 140 && ! str_ends_with($line, '?')) {
                return rtrim($line, '.:');
            }
        }

        return 'concrete informatie, een heldere werkwijze en een duidelijke volgende stap';
    }

    /**
     * @param  list<string>  $keywords
     * @param  list<string>  $lines
     * @param  array<string, mixed>  $context
     */
    private function seoPromiseAlternate(array $keywords, array $lines, array $context): string
    {
        $blob = mb_strtolower((string) ($context['page_content'] ?? '').' '.(string) ($context['site_description'] ?? ''));
        if (preg_match('/\b(boek(en|ing)?|chauffeur|dispatch)\b/u', $blob)
            && preg_match('/\b(taxi|ritten?)\b/u', $blob)) {
            return 'website, chauffeur-app en dispatch in één platform: start vandaag';
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (mb_strlen($line) >= 40 && mb_strlen($line) <= 140 && ! str_ends_with($line, '?')) {
                return rtrim($line, '.:');
            }
        }

        return 'bekijk de mogelijkheden en neem vrijblijvend contact op';
    }

    private function sharesTopicWords(string $topic, string $promise): bool
    {
        $topic = mb_strtolower(trim($topic, '.: '));
        $promise = mb_strtolower(trim($promise, '.: '));
        if ($topic === '' || $promise === '') {
            return false;
        }
        if (str_contains($promise, $topic) || str_contains($topic, $promise)) {
            return true;
        }
        foreach (['gemiste ritten', 'online boeken', 'chauffeur-app', 'contractvervoer', 'website builder'] as $phrase) {
            if (str_contains($topic, $phrase) && str_contains($promise, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function isWeakSeoTitle(string $title): bool
    {
        $title = trim($title);
        if ($title === '' || $this->isTruncatedTitle($title)) {
            return true;
        }
        $lower = mb_strtolower($title);
        if (in_array($lower, ['home', 'pagina', 'untitled', 'nieuw', 'welkom', 'start', 'over ons', 'contact', 'diensten'], true)) {
            return true;
        }
        if (mb_strlen($title) > 70) {
            return true;
        }

        return str_ends_with($title, '?') && mb_strlen($title) > 48;
    }

    private function looksLikeContentDump(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }
        if (str_contains($text, '…') || str_contains($text, '...')) {
            return true;
        }
        $lower = mb_strtolower($text);
        foreach ([
            'start hier', 'meer lezen', 'gratis account aanmaken', 'vacatures bekijken',
            'herkenbaar?', 'lees meer', 'klik hier',
        ] as $noise) {
            if (str_contains($lower, $noise)) {
                return true;
            }
        }

        return (bool) preg_match('/(.{12,48}).{8,}\1/iu', $text);
    }

    private function replaceEmDash(string $text): string
    {
        $text = preg_replace('/\s*—\s*/u', ': ', $text) ?? $text;

        return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
    }

    private function fitSeoTitle(string $title): string
    {
        $title = $this->replaceEmDash($title);
        $title = preg_replace('/\s+/u', ' ', trim($title)) ?? '';
        $title = rtrim($title, " \t…");
        if (mb_strlen($title) <= self::TITLE_MAX) {
            return $title;
        }

        $cut = mb_substr($title, 0, self::TITLE_MAX);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > 28) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, '.,;:-–—|');
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function keywordsFromContext(array $context): array
    {
        return $this->keywordsFromText(implode("\n", array_filter([
            (string) ($context['page_content'] ?? ''),
            (string) ($context['title'] ?? ''),
            str_replace('-', ' ', (string) ($context['slug'] ?? '')),
            (string) ($context['site_description'] ?? ''),
        ])));
    }

    /**
     * @return list<string>
     */
    private function keywordsFromText(string $text): array
    {
        $text = mb_strtolower($this->plainText($text));
        $text = str_replace(['…', '...'], ' ', $text);
        $found = $this->collocationsInContent($text);
        $stop = $this->seoStopwords();
        preg_match_all('/[a-zà-ÿ]{4,}/u', $text, $matches);
        $counts = [];
        foreach ($matches[0] ?? [] as $word) {
            if (isset($stop[$word])) {
                continue;
            }
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }
        arsort($counts);
        foreach (array_keys($counts) as $word) {
            if (count($found) >= 6) {
                break;
            }
            if ($this->wordCoveredByPhrase($word, $found)) {
                continue;
            }
            $found[] = $word;
        }

        return array_values(array_slice($found, 0, 6));
    }

    /**
     * @return list<string>
     */
    private function collocationsInContent(string $text): array
    {
        $haystack = mb_strtolower($this->plainText($text));
        $found = [];
        foreach ([
            'chauffeur-app', 'online boeking', 'online boeken', 'zelf boeken', 'gemiste ritten',
            'contractvervoer', 'website builder', 'live dispatch', 'schoolvervoer', 'taxi software',
            'taxibedrijven', 'taxibedrijf', 'ritten plannen',
        ] as $phrase) {
            if (str_contains($haystack, $phrase)) {
                $found[] = $phrase;
            }
        }

        return $found;
    }

    /**
     * @param  list<string>  $phrases
     */
    private function wordCoveredByPhrase(string $word, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if ($word === $phrase || str_contains($phrase, $word)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $keywords
     */
    private function topicPhrase(array $keywords): string
    {
        if ($keywords === []) {
            return '';
        }
        if (isset($keywords[1])) {
            return $keywords[0].' en '.$keywords[1];
        }

        return $keywords[0];
    }

    /**
     * @return list<string>
     */
    private function seoSourceLines(string $pageContent): array
    {
        $lines = [];
        foreach (preg_split("/\R/u", $pageContent) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '[')) {
                continue;
            }
            $line = $this->completeTruncatedHeroTitle($this->plainText($line));
            if ($line === '' || $this->isTruncatedTitle($line) || $this->isUiChromeLine($line)) {
                continue;
            }
            $lines[] = $line;
        }
        $lines = $this->uniqueLines($lines);
        usort($lines, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));
        $kept = [];
        foreach ($lines as $line) {
            $skip = false;
            foreach ($kept as $existing) {
                if (str_starts_with($existing, $line) || str_starts_with($line, $existing)) {
                    $skip = true;
                    break;
                }
            }
            if (! $skip) {
                $kept[] = $line;
            }
        }

        return $kept;
    }

    private function isUiChromeLine(string $line): bool
    {
        $line = trim($line);
        $lower = mb_strtolower($line);
        $buttons = [
            'start hier', 'meer lezen', 'meer informatie', 'neem contact op', 'gratis account aanmaken',
            'vacatures bekijken', 'bekijk prijzen', 'plan een gesprek', 'offerte aanvragen',
            'bericht sturen', 'bel ons', 'ons team', 'demo aanvragen', 'gratis demo',
            'herkenbaar?', 'herkenbaar', 'lees meer', 'aan de slag', 'direct starten',
            'gratis account', 'naar overzicht', 'naar admin',
        ];
        if (in_array($lower, $buttons, true)) {
            return true;
        }
        if (mb_strlen($line) <= 24 && ! preg_match('/[.!]/u', $line)
            && preg_match('/^(start|meer|lees|bekijk|klik|ga |naar |gratis |neem |plan )/iu', $line)) {
            return true;
        }

        return mb_strlen($line) <= 18 && str_ends_with($line, '?');
    }

    /**
     * @return array<string, true>
     */
    private function seoStopwords(): array
    {
        $words = [
            'deze', 'dit', 'dat', 'die', 'een', 'voor', 'van', 'het', 'met', 'een', 'aan', 'bij', 'uit',
            'ook', 'nog', 'wel', 'niet', 'geen', 'naar', 'over', 'onder', 'tussen', 'zonder', 'omdat',
            'als', 'dan', 'maar', 'want', 'dus', 'hier', 'daar', 'waar', 'hoe', 'wat', 'wie', 'welke',
            'onze', 'ons', 'uw', 'hun', 'jullie', 'zijn', 'haar', 'mijn', 'jouw', 'zelf', 'laat',
            'meer', 'lezen', 'start', 'klik', 'pagina', 'home', 'welkom',             'kunnen', 'wordt', 'worden',
            'heeft', 'hebben', 'tegen', 'via', 'per', 'alle', 'elke',
            'contact', 'pagina', 'home', 'welkom', 'untitled',
        ];
        $map = [];
        foreach ($words as $word) {
            $map[$word] = true;
        }

        return $map;
    }

    private function mbUcfirst(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return mb_strtoupper(mb_substr($value, 0, 1)).mb_substr($value, 1);
    }

    /**
     * Hero-kop is paginatekst, geen Google-title: nooit afkappen tot TITLE_MAX.
     *
     * @param  array<string, mixed>  $context
     */
    private function resolveHeroTitle(array $context, string $generated, string $seoTitle): string
    {
        $existing = $this->completeTruncatedHeroTitle($this->existingHeroTitle($context));
        $heading = $this->completeTruncatedHeroTitle(
            $this->firstHeadingFromContent((string) ($context['page_content'] ?? ''))
        );
        $generated = $this->completeTruncatedHeroTitle($generated);
        $original = $this->completeTruncatedHeroTitle(trim((string) ($context['title'] ?? '')));

        $complete = [];
        foreach ([$existing, $heading, $generated, $original] as $candidate) {
            if ($candidate === '' || $this->isTruncatedTitle($candidate)) {
                continue;
            }
            if ($candidate === $seoTitle && $this->isTruncatedTitle($seoTitle)) {
                continue;
            }
            $complete[] = $candidate;
        }
        if ($complete !== []) {
            usort($complete, fn (string $a, string $b) => mb_strlen($b) <=> mb_strlen($a));

            return $complete[0];
        }

        foreach ([$existing, $heading, $generated, $original] as $candidate) {
            if ($candidate !== '' && ! $this->isTruncatedTitle($candidate)) {
                return $candidate;
            }
        }

        return $existing !== '' ? $existing : ($heading !== '' ? $heading : $generated);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function existingHeroTitle(array $context): string
    {
        $sections = $context['home_sections'] ?? [];
        if (! is_array($sections)) {
            return '';
        }
        $key = $this->firstHeroSectionKey($sections);
        if ($key === null) {
            return '';
        }
        $block = $sections[$key] ?? [];

        return is_array($block) ? trim((string) ($block['title'] ?? '')) : '';
    }

    private function isTruncatedTitle(string $title): bool
    {
        $title = trim($title);

        return str_ends_with($title, '…') || str_ends_with($title, '...');
    }

    private function completeTruncatedHeroTitle(string $title): string
    {
        $title = trim($title);
        if ($title === '' || ! $this->isTruncatedTitle($title)) {
            return $title;
        }

        $prefix = rtrim(preg_replace('/(\.\.\.|…)+$/u', '', $title) ?? $title);
        if ($prefix === '') {
            return $title;
        }

        foreach ($this->knownFullHeroTitles() as $full) {
            if (str_starts_with($full, $prefix)) {
                return $full;
            }
        }

        return $title;
    }

    /**
     * @return list<string>
     */
    private function knownFullHeroTitles(): array
    {
        return [
            'Mis je ritten aan de telefoon? Laat klanten zelf boeken.',
            'Mis je ritten omdat de telefoon overgaat? Laat klanten zelf boeken.',
            'Laat klanten 24/7 zelf boeken.',
            'Nexa Taxi: van telefoon naar online boeking & chauffeur-app',
            'Vaste ritten zonder Excel.',
            'Vaste ritten zonder Excel. School, zorg, zakelijk en privé.',
            'Website builder: merk + boeking zonder apart CMS',
            'Plan een gesprek. We kijken naar jouw ritten.',
        ];
    }

    private function firstHeadingFromContent(string $pageContent): string
    {
        foreach (preg_split("/\R/u", $pageContent) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '[')) {
                continue;
            }
            $line = $this->plainText($line);
            if (mb_strlen($line) >= 4 && mb_strlen($line) <= 160 && ! $this->looksLikeUrlOrCode($line)) {
                return $line;
            }
        }

        return '';
    }

    private function plainTextFromContentBlocks(string $pageContent): string
    {
        $lines = [];
        foreach (preg_split("/\R/u", $pageContent) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '[')) {
                continue;
            }
            $line = $this->plainText($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return trim(implode(' ', $this->uniqueLines($lines)));
    }

    private function firstSentences(string $text, int $maxSentences): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        preg_match_all('/[^.!?]+[.!?]?/u', $text, $matches);
        $sentences = [];
        foreach ($matches[0] ?? [] as $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) < 20) {
                continue;
            }
            $sentences[] = $sentence;
            if (count($sentences) >= $maxSentences) {
                break;
            }
        }

        if ($sentences === []) {
            return $this->truncate($text, 180);
        }

        return trim(implode(' ', $sentences));
    }

    private function plainText(string $value): string
    {
        $value = preg_replace('/<script[\s\S]*?<\/script>/iu', ' ', $value) ?? $value;
        $value = preg_replace('/<style[\s\S]*?<\/style>/iu', ' ', $value) ?? $value;
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function uniqueLines(array $lines): array
    {
        $seen = [];
        $out = [];
        foreach ($lines as $line) {
            $key = mb_strtolower($line);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $line;
        }

        return $out;
    }

    private function sectionLabel(string $key): string
    {
        if (str_starts_with($key, 'component:')) {
            $name = str_replace(['.', '_'], ' ', substr($key, strlen('component:')));

            return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
        }

        $base = preg_replace('/_\d+$/', '', $key) ?? $key;

        return match ($base) {
            'hero' => 'Hero',
            'stats' => 'Statistieken',
            'why_nexa' => 'Introductie',
            'features' => 'Kenmerken',
            'cta' => 'Call-to-action',
            'carousel' => 'Carousel',
            'cards_ronde_hoeken' => 'Kaarten',
            'featured_services' => 'Diensten',
            'email_template' => 'Formulier',
            'text_block' => 'Tekstblok',
            default => $base,
        };
    }

    /**
     * @param  list<string>  $into
     */
    private function collectTextValues(mixed $value, array &$into, int $depth): void
    {
        if ($depth > 8 || count($into) > 80) {
            return;
        }

        if (is_string($value)) {
            $text = $this->plainText($value);
            if (mb_strlen($text) >= 2 && ! $this->looksLikeUrlOrCode($text)) {
                $into[] = $text;
            }

            return;
        }

        if (! is_array($value)) {
            return;
        }

        $isList = array_is_list($value);
        foreach ($value as $key => $nested) {
            if (! $isList && is_string($key)) {
                if ($this->shouldSkipContentKey($key)) {
                    continue;
                }
                if ($this->isTextContentKey($key)) {
                    $this->collectTextValues($nested, $into, $depth + 1);
                    continue;
                }
            }
            if (is_array($nested)) {
                $this->collectTextValues($nested, $into, $depth + 1);
            } elseif ($isList && is_string($nested)) {
                $this->collectTextValues($nested, $into, $depth + 1);
            }
        }
    }

    private function isTextContentKey(string $key): bool
    {
        $exact = [
            'title', 'subtitle', 'content', 'description', 'text', 'heading', 'label', 'caption', 'alt',
            'value', 'cta_primary_text', 'cta_secondary_text', 'section_title', 'intro', 'body', 'quote',
            'name', 'question', 'answer', 'left_heading', 'right_heading', 'features_text', 'badge',
            'button_text', 'copyright',
        ];
        if (in_array($key, $exact, true)) {
            return true;
        }

        return (bool) preg_match('/_(title|subtitle|content|description|text|heading|label|caption)$/i', $key);
    }

    private function shouldSkipContentKey(string $key): bool
    {
        return (bool) preg_match(
            '/url|color|image|uuid|font|opacity|animation|padding|margin|background|logo|map_|_lat|_lng|zoom|align|class|token|percent|duration|stagger|radius|shadow|border|icon|visibility|inherit|order|style|_src|href|file|_path|width|height|_px|api_key|html_id|removed_/i',
            $key
        );
    }

    private function looksLikeUrlOrCode(string $value): bool
    {
        if (preg_match('/^https?:\/\//i', $value) || preg_match('/^\/[a-z0-9_\-.\/]+$/i', $value)) {
            return true;
        }
        if (preg_match('/^#[0-9a-f]{3,8}$/i', $value)) {
            return true;
        }

        return (bool) preg_match('/^[0-9.]+(px|rem|em|%|ms)$/i', $value);
    }

    /**
     * Volledige Google-snippet: 150–160 tekens, hele zinnen, geen ….
     *
     * @param  array<string, mixed>  $context
     */
    private function fitMetaDescription(string $meta, array $context): string
    {
        $meta = $this->replaceEmDash($meta);
        $meta = preg_replace('/\s+/u', ' ', trim($meta)) ?? '';
        $meta = rtrim($meta, " \t…");

        if (mb_strlen($meta) > self::META_MAX) {
            $meta = $this->trimToCompleteMeta($meta);
        }

        if (mb_strlen($meta) < self::META_MIN) {
            $meta = $this->expandMetaToTarget($meta, $context);
        }

        if (mb_strlen($meta) > self::META_MAX) {
            $meta = $this->trimToCompleteMeta($meta);
        }

        return $this->nudgeMetaLength($meta, $context);
    }

    private function trimToCompleteMeta(string $meta): string
    {
        $meta = preg_replace('/\s+/u', ' ', trim($meta)) ?? '';
        if (mb_strlen($meta) <= self::META_MAX) {
            return $meta;
        }

        $window = mb_substr($meta, 0, self::META_MAX);
        if (preg_match('/^(.*[.!?])(?:\s|$)/u', $window, $matches)) {
            $cut = trim($matches[1]);
            if (mb_strlen($cut) >= 80) {
                return $cut;
            }
        }

        $lastSpace = mb_strrpos($window, ' ');
        if ($lastSpace !== false && $lastSpace > 80) {
            $cut = rtrim(mb_substr($window, 0, $lastSpace), '.,;: ');
            if (! preg_match('/[.!?]$/u', $cut) && mb_strlen($cut) < self::META_MAX) {
                $cut .= '.';
            }

            return $cut;
        }

        return rtrim(mb_substr($meta, 0, self::META_MAX - 1)).'.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function expandMetaToTarget(string $meta, array $context): string
    {
        $parts = $this->metaTails($context);
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || $this->sharesTopicWords($meta, $part)) {
                continue;
            }
            $candidate = $this->joinMetaSentences($meta, $part);
            if (mb_strlen($candidate) <= self::META_MAX) {
                $meta = $candidate;
            }
            if (mb_strlen($meta) >= 155) {
                return $meta;
            }
        }

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function nudgeMetaLength(string $meta, array $context): string
    {
        $len = mb_strlen($meta);
        if ($len >= 155 && $len <= self::META_MAX) {
            return $meta;
        }
        if ($len > self::META_MAX) {
            return $this->trimToCompleteMeta($meta);
        }

        foreach ($this->metaTails($context) as $tail) {
            if ($this->sharesTopicWords($meta, $tail)) {
                continue;
            }
            $candidate = $this->joinMetaSentences($meta, $tail);
            $n = mb_strlen($candidate);
            if ($n >= self::META_MIN && $n <= self::META_MAX) {
                return $candidate;
            }
        }

        if (mb_strlen($meta) >= self::META_MIN) {
            return $meta;
        }

        foreach ([
            'Start nu.',
            'Start vandaag.',
            'Vraag een demo.',
            'Plan een gesprek.',
            'Vergelijk de mogelijkheden en kies bewust.',
            'Vraag het na of start direct online.',
            'Bekijk de pagina en neem de volgende stap.',
        ] as $filler) {
            if ($this->sharesTopicWords($meta, $filler)) {
                continue;
            }
            $candidate = $this->joinMetaSentences($meta, $filler);
            $n = mb_strlen($candidate);
            if ($n >= self::META_MIN && $n <= self::META_MAX) {
                return $candidate;
            }
        }

        return $meta;
    }

    private function joinMetaSentences(string $base, string $extra): string
    {
        $base = trim($base);
        $extra = trim($extra);
        if ($base === '') {
            return $extra;
        }
        if ($extra === '') {
            return $base;
        }
        if (! preg_match('/[.!?]$/u', $base)) {
            $base .= '.';
        }

        return $base.' '.$extra;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return list<string>
     */
    private function metaTails(array $context): array
    {
        $brand = trim((string) ($context['brand'] ?? 'Ons team'));
        $topic = $this->topicPhrase($this->keywordsFromContext($context));
        if ($topic === '') {
            $topic = trim((string) ($context['title'] ?? $context['page_type_label'] ?? 'deze pagina'));
        }
        if ($topic === '') {
            $topic = 'deze pagina';
        }

        return match ((string) ($context['page_type'] ?? 'custom')) {
            'home' => [
                "Bekijk {$topic} en start vandaag bij {$brand}.",
                'Vraag een vrijblijvende demo of meer informatie aan.',
                'Regel het online, zonder verplichtingen.',
            ],
            'about' => [
                "Leer {$brand} kennen: werkwijze, ervaring en waarom klanten blijven.",
                'Lees meer over ons team en neem vrijblijvend contact op.',
                'Plan een kennismaking zonder verplichtingen.',
            ],
            'contact' => [
                "Neem contact op met {$brand} voor een snel en duidelijk antwoord.",
                'Stel uw vraag via het formulier of bel ons direct.',
                'Wij reageren zo snel mogelijk op uw bericht.',
            ],
            default => [
                "Lees alles over {$topic} bij {$brand}.",
                'Bekijk de pagina en neem vrijblijvend contact op.',
                'Vraag informatie aan of start direct met de volgende stap.',
            ],
        };
    }

    private function limitChars(string $text, int $max): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    private function truncate(string $text, int $max): string
    {
        $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max - 1);
        $lastSpace = mb_strrpos($cut, ' ');
        if ($lastSpace !== false && $lastSpace > (int) ($max * 0.6)) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, '.,;:-').'…';
    }
}

<?php

namespace App\Services\AiWebsite\Planning;

use App\Models\Company;
use App\Services\AiWebsite\AiScalar;
use App\Services\AiWebsite\ComponentRegistry\AiComponentRegistry;
use App\Services\AiWebsite\Providers\AiProviderInterface;
use App\Services\WebsiteAiSiteCopy;

class AiSitemapPlanner
{
    private string $varietyKey = '';

    public function __construct(
        protected AiProviderInterface $provider,
        protected AiComponentRegistry $registry,
        protected WebsiteAiSiteCopy $siteCopy,
    ) {}

    /**
     * @param  array<string, mixed>  $brief
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $source
     * @return array{sitemap: list<array<string, mixed>>, pages: list<array<string, mixed>>, homepage: array<string, mixed>, used_openai: bool}
     */
    public function make(Company $company, array $brief, array $input, string $themeSlug, array $source = []): array
    {
        $this->varietyKey = implode('|', [
            (string) ($input['style'] ?? ''),
            (string) ($input['tone'] ?? ''),
            implode(',', array_map('strval', (array) ($input['goals'] ?? []))),
        ]);
        $maxPages = max(1, min(12, (int) ($input['max_pages'] ?? 3)));
        $fallback = $this->fallbackPack($company, $brief, $maxPages, $themeSlug, $source);
        $fromLlm = $this->provider->generateStructured(
            (string) config('ai_website.prompt_versions.sitemap-homepage', 'sitemap-homepage:v1'),
            [
                'brief' => $brief,
                'max_paginas' => $maxPages,
                'toegestane_secties' => $this->registry->allowedSectionKeys($themeSlug),
                'toegestane_componenten' => $this->registry->allowedComponentIds(),
                'toegestane_iconen' => [
                    'light-bulb', 'bolt', 'briefcase', 'cog-6-tooth', 'user-group', 'map-pin',
                    'phone', 'clock', 'shield-check', 'truck', 'star', 'heart', 'globe-alt',
                    'chat-bubble-left-right', 'calendar', 'check-badge',
                ],
                'gewenst' => [
                    'pages' => $fallback['sitemap'],
                    'homepage' => $fallback['homepage'],
                ],
            ],
            'Je bent een Nederlandse information architect en copywriter voor de NEXA PageBuilder. Antwoord uitsluitend met JSON. Maak precies het gevraagde aantal pagina’s. Eerste pagina is altijd home. Pagina 2 is over-ons als er minstens 3 pagina’s zijn, contact als er 2 pagina’s zijn. Bij 3+ pagina’s: home, over-ons, contact. Schrijf rijke, concrete Nederlandse teksten (diensten, werkwijze, waarom-wij). Kies per site een ANDERE subset van 3 tot 4 geanimeerde componenten uit de toegestane lijst; gebruik niet altijd dezelfde combinatie (FAQ, quotes, comparison, checklist, stats_strip, pills, contact_split). Wissel ook de volgorde van why_nexa, featured_services, features, stats en text_block. Zet taxi.boekingsmodule_v2 altijd direct onder de hero als dat component is toegestaan. Voeg image_prompt toe bij hero én text_block: fotorealistische live-action of hoogwaardige cinematic still, géén schets, géén illustratie, géén tekst in beeld. Footer support-links (Help, Privacy, Voorwaarden, Cookies) alleen als die pagina’s in pages staan. Interne URL’s alleen naar slugs uit pages. Geen em-dash. Verzin geen feiten.'
        );

        if (is_array($fromLlm)) {
            $sitemap = $this->normalizeSitemap($fromLlm['pages'] ?? [], $maxPages, $company);
            $pages = $this->mergeLlmIntoPages($fallback['pages'], $fromLlm, $themeSlug, $company);

            return [
                'sitemap' => $sitemap,
                'pages' => $this->alignPagesToSitemap($pages, $sitemap),
                'homepage' => $pages[0] ?? $fallback['homepage'],
                'used_openai' => true,
            ];
        }

        return $fallback + ['used_openai' => false];
    }

    /**
     * @param  array<string, mixed>  $brief
     * @param  array<string, mixed>  $source
     * @return array{sitemap: list<array<string, mixed>>, pages: list<array<string, mixed>>, homepage: array<string, mixed>}
     */
    public function fallbackPack(Company $company, array $brief, int $maxPages, string $themeSlug, array $source = []): array
    {
        $context = AiScalar::string($brief['business_summary'] ?? $source['summary'] ?? '');
        $blueprint = $this->siteCopy->blueprint($company, $context, $source, $maxPages);
        $pages = [];
        foreach ($blueprint['pages'] as $page) {
            if (! is_array($page)) {
                continue;
            }
            $pages[] = $this->decoratePage($page, $company, $themeSlug, $blueprint['footer'] ?? []);
        }
        $pages = $this->ensureContactAndAbout($pages, $maxPages, $company, $themeSlug, $brief);
        $sitemap = $this->sitemapFromPages($pages);

        return [
            'sitemap' => $sitemap,
            'pages' => $pages,
            'homepage' => $pages[0] ?? $this->decoratePage(['slug' => 'home', 'page_type' => 'home', 'title' => (string) $company->name], $company, $themeSlug, []),
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $footer
     * @return array<string, mixed>
     */
    private function decoratePage(array $page, Company $company, string $themeSlug, array $footer): array
    {
        $page['slug'] = $this->slug(AiScalar::string($page['slug'] ?? $page['title'] ?? 'pagina'));
        $page['page_type'] = AiScalar::string($page['page_type'] ?? $page['type'] ?? 'custom');
        if (($page['page_type'] ?? '') === 'home') {
            $page['slug'] = 'home';
        }
        $components = AiScalar::componentIds($page['components'] ?? []);
        $pageType = (string) ($page['page_type'] ?? 'custom');
        if (! $company->hasTaxiModule()) {
            $components = array_values(array_filter(
                $components,
                fn (string $id) => ! str_contains($id, 'boekingsmodule') && $id !== 'taxi.tarieven'
            ));
        }
        $pool = $components !== [] ? $components : WebsiteAiSiteCopy::HOME_COMPONENT_POOL;
        $components = $this->siteCopy->pickAnimatedComponents($company, $pool, $pageType, $this->varietyKey);
        if ($company->hasTaxiModule() && $pageType === 'home' && ! in_array(WebsiteAiSiteCopy::BOOKING_COMPONENT, $components, true)) {
            array_unshift($components, WebsiteAiSiteCopy::BOOKING_COMPONENT);
        }
        $page['components'] = $components;
        $page['section_order'] = $this->sectionOrderFor($page, $company, $themeSlug);
        if ($footer !== []) {
            $page['footer'] = $footer;
        }

        return $page;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return list<string>
     */
    private function sectionOrderFor(array $page, Company $company, string $themeSlug): array
    {
        $type = AiScalar::string($page['page_type'] ?? 'custom');
        $components = AiScalar::componentIds($page['components'] ?? []);
        $componentKeys = [];
        foreach ($components as $id) {
            if ($id === '' || str_contains($id, 'boekingsmodule')) {
                continue;
            }
            $componentKeys[] = 'component:'.$id;
        }

        if ($type === 'home') {
            $order = ['hero'];
            $includeBooking = $company->hasTaxiModule()
                || collect($components)->contains(fn (string $id) => str_contains($id, 'boekingsmodule'));
            if ($includeBooking) {
                $order[] = 'component:'.WebsiteAiSiteCopy::BOOKING_COMPONENT;
            }
            $nativeSets = [
                ['why_nexa', 'featured_services', 'text_block', 'features'],
                ['text_block', 'featured_services', 'why_nexa', 'stats'],
                ['featured_services', 'text_block', 'features', 'why_nexa'],
                ['why_nexa', 'stats', 'text_block', 'featured_services'],
                ['text_block', 'features', 'stats', 'featured_services'],
                ['featured_services', 'why_nexa', 'features', 'text_block'],
                ['why_nexa', 'featured_services', 'stats', 'text_block'],
                ['features', 'text_block', 'why_nexa'],
            ];
            $seed = $this->siteCopy->layoutSeed($company, 'home', $this->varietyKey);
            $natives = $nativeSets[$seed % count($nativeSets)];
            if ($componentKeys !== []) {
                $rot = $seed % count($componentKeys);
                $componentKeys = array_merge(array_slice($componentKeys, $rot), array_slice($componentKeys, 0, $rot));
            }
            $order = array_merge($order, $natives, $componentKeys, ['cta']);
        } elseif ($type === 'contact') {
            $order = array_merge(['hero', 'text_block', 'email_template'], $componentKeys);
        } elseif ($type === 'about') {
            $aboutSets = [
                ['hero', 'text_block', 'featured_services'],
                ['hero', 'featured_services', 'text_block'],
                ['hero', 'text_block'],
            ];
            $seed = $this->siteCopy->layoutSeed($company, 'about', $this->varietyKey);
            $order = array_merge($aboutSets[$seed % count($aboutSets)], $componentKeys);
        } else {
            $order = array_merge(['hero', 'featured_services', 'text_block'], $componentKeys);
        }

        return $this->registry->filterSectionOrder($themeSlug, $order);
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  array<string, mixed>  $brief
     * @return list<array<string, mixed>>
     */
    private function ensureContactAndAbout(array $pages, int $maxPages, Company $company, string $themeSlug, array $brief): array
    {
        $has = fn (string $type) => collect($pages)->contains(fn (array $p) => ($p['page_type'] ?? '') === $type || ($p['slug'] ?? '') === ($type === 'about' ? 'over-ons' : $type));
        if ($maxPages >= 3 && ! $has('about')) {
            $pages[] = $this->decoratePage($this->siteCopy->aboutPage($this->siteCopy->profile($company, AiScalar::string($brief['business_summary'] ?? ''), []), $company), $company, $themeSlug, []);
        }
        if ($maxPages >= 2 && ! $has('contact')) {
            $pages[] = $this->decoratePage($this->siteCopy->contactPage($this->siteCopy->profile($company, AiScalar::string($brief['business_summary'] ?? ''), []), $company), $company, $themeSlug, []);
        }

        $home = [];
        $about = [];
        $contact = [];
        $rest = [];
        foreach ($pages as $page) {
            $type = AiScalar::string($page['page_type'] ?? '');
            $slug = AiScalar::string($page['slug'] ?? '');
            if ($type === 'home' || $slug === 'home') {
                $home[] = $page;
            } elseif ($type === 'about' || $slug === 'over-ons') {
                $about[] = $page;
            } elseif ($type === 'contact' || $slug === 'contact') {
                $contact[] = $page;
            } else {
                $rest[] = $page;
            }
        }

        $ordered = array_merge(
            $home !== [] ? [reset($home)] : [],
            $maxPages >= 3 ? $about : [],
            $maxPages >= 2 ? $contact : [],
            $rest
        );

        return array_slice($ordered, 0, $maxPages);
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @return list<array<string, mixed>>
     */
    private function sitemapFromPages(array $pages): array
    {
        $out = [];
        foreach ($pages as $page) {
            $type = AiScalar::string($page['page_type'] ?? 'custom');
            $out[] = [
                'title' => AiScalar::string($page['menu_title'] ?? $page['title'] ?? 'Pagina'),
                'slug' => AiScalar::string($page['slug'] ?? 'pagina'),
                'type' => $type,
                'purpose' => AiScalar::string($page['meta_description'] ?? ''),
                'show_in_menu' => (bool) ($page['show_in_menu'] ?? true),
            ];
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $fallbackPages
     * @param  array<string, mixed>  $fromLlm
     * @return list<array<string, mixed>>
     */
    private function mergeLlmIntoPages(array $fallbackPages, array $fromLlm, string $themeSlug, Company $company): array
    {
        $rawHome = is_array($fromLlm['homepage'] ?? null)
            ? $fromLlm['homepage']
            : (is_array($fromLlm['pages'][0] ?? null) ? $fromLlm['pages'][0] : []);
        $bySlug = [];
        foreach ($fallbackPages as $page) {
            $bySlug[AiScalar::string($page['slug'] ?? '')] = $page;
        }
        if ($rawHome !== [] && isset($bySlug['home'])) {
            $bySlug['home'] = $this->normalizeHomepage($rawHome, $bySlug['home'], $themeSlug, $company);
        }
        if (is_array($fromLlm['pages'] ?? null)) {
            foreach ($fromLlm['pages'] as $raw) {
                if (! is_array($raw)) {
                    continue;
                }
                $slug = $this->slug(AiScalar::string($raw['slug'] ?? $raw['title'] ?? ''));
                if ($slug === '' || $slug === 'home' || ! isset($bySlug[$slug])) {
                    continue;
                }
                $bySlug[$slug] = $this->overlayPage($bySlug[$slug], $raw, $themeSlug, $company);
            }
        }

        return array_values($bySlug);
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $sitemap
     * @return list<array<string, mixed>>
     */
    private function alignPagesToSitemap(array $pages, array $sitemap): array
    {
        $bySlug = [];
        foreach ($pages as $page) {
            $bySlug[AiScalar::string($page['slug'] ?? '')] = $page;
        }
        $out = [];
        foreach ($sitemap as $item) {
            $slug = AiScalar::string($item['slug'] ?? '');
            if ($slug !== '' && isset($bySlug[$slug])) {
                $out[] = $bySlug[$slug];
            }
        }

        return $out !== [] ? $out : $pages;
    }

    /**
     * @param  mixed  $pages
     * @return list<array<string, mixed>>
     */
    private function normalizeSitemap(mixed $pages, int $maxPages, Company $company): array
    {
        $out = [];
        if (is_array($pages)) {
            foreach ($pages as $page) {
                if (! is_array($page)) {
                    continue;
                }
                $slug = $this->slug(AiScalar::string($page['slug'] ?? $page['title'] ?? 'pagina'));
                $type = AiScalar::string($page['type'] ?? $page['page_type'] ?? 'custom');
                if (! in_array($type, ['home', 'about', 'contact', 'custom'], true)) {
                    $type = 'custom';
                }
                $out[] = [
                    'title' => AiScalar::string($page['title'] ?? 'Pagina') ?: 'Pagina',
                    'slug' => $slug,
                    'type' => $type,
                    'purpose' => AiScalar::string($page['purpose'] ?? ''),
                    'show_in_menu' => (bool) ($page['show_in_menu'] ?? true),
                ];
                if (count($out) >= $maxPages) {
                    break;
                }
            }
        }
        if ($out === [] || ($out[0]['type'] ?? '') !== 'home') {
            array_unshift($out, [
                'title' => (string) $company->name,
                'slug' => 'home',
                'type' => 'home',
                'purpose' => 'Homepage',
                'show_in_menu' => true,
            ]);
            $out = array_slice($out, 0, $maxPages);
        }
        $out[0]['slug'] = 'home';
        $out[0]['type'] = 'home';
        $types = array_column($out, 'type');
        if ($maxPages >= 2 && ! in_array('contact', $types, true)) {
            $out[] = ['title' => 'Contact', 'slug' => 'contact', 'type' => 'contact', 'purpose' => 'Contactformulier', 'show_in_menu' => true];
        }
        if ($maxPages >= 3 && ! in_array('about', $types, true)) {
            array_splice($out, 1, 0, [['title' => 'Over ons', 'slug' => 'over-ons', 'type' => 'about', 'purpose' => 'Over ons', 'show_in_menu' => true]]);
        }

        return array_slice($out, 0, $maxPages);
    }

    /**
     * @param  array<string, mixed>  $raw
     * @param  array<string, mixed>  $fallback
     * @return array<string, mixed>
     */
    private function normalizeHomepage(array $raw, array $fallback, string $themeSlug, Company $company): array
    {
        return $this->overlayPage($fallback, $raw, $themeSlug, $company);
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function overlayPage(array $base, array $raw, string $themeSlug, Company $company): array
    {
        foreach (['title', 'menu_title', 'meta_description'] as $field) {
            $value = AiScalar::string($raw[$field] ?? '');
            if ($value !== '') {
                $base[$field] = $value;
            }
        }
        foreach (['hero', 'why_nexa', 'features', 'stats', 'cta', 'featured_services', 'text_block', 'email_template', 'footer'] as $block) {
            if (is_array($raw[$block] ?? null)) {
                $base[$block] = array_replace_recursive(is_array($base[$block] ?? null) ? $base[$block] : [], $raw[$block]);
            }
        }
        if (is_array($raw['components'] ?? null) && $raw['components'] !== []) {
            $requested = AiScalar::componentIds($raw['components']);
            $pageType = AiScalar::string($base['page_type'] ?? 'custom');
            $base['components'] = $this->siteCopy->pickAnimatedComponents(
                $company,
                $requested !== [] ? $requested : WebsiteAiSiteCopy::HOME_COMPONENT_POOL,
                $pageType,
                $this->varietyKey
            );
            if ($company->hasTaxiModule() && $pageType === 'home' && ! in_array(WebsiteAiSiteCopy::BOOKING_COMPONENT, $base['components'], true)) {
                array_unshift($base['components'], WebsiteAiSiteCopy::BOOKING_COMPONENT);
            }
        }
        if (is_array($raw['component_copy'] ?? null)) {
            $base['component_copy'] = array_replace_recursive(is_array($base['component_copy'] ?? null) ? $base['component_copy'] : [], $raw['component_copy']);
        }
        $base['section_order'] = $this->sectionOrderFor($base, $company, $themeSlug);

        return $base;
    }

    private function slug(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'pagina';
    }
}

<?php

namespace App\Services\AiWebsite\ComponentRegistry;

use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\FrontendComponentService;

/**
 * Allowlist van bestaande PageBuilder-secties en -componenten.
 * AI mag alleen deze keys in section_order zetten.
 */
class AiComponentRegistry
{
    public function __construct(protected FrontendComponentService $components) {}

    /**
     * @return list<array{type: string, name: string, category: string, description: string, content_rules: string}>
     */
    public function definitionsForTheme(string $themeSlug): array
    {
        $native = [];
        foreach (WebsitePage::getAvailableHomeSectionTypesForTheme($themeSlug) as $row) {
            $type = (string) ($row['type'] ?? '');
            if ($type === '') {
                continue;
            }
            $native[] = [
                'type' => $type,
                'name' => (string) ($row['label'] ?? $type),
                'category' => 'section',
                'description' => $this->sectionDescription($type),
                'content_rules' => $this->sectionRules($type),
            ];
        }

        $mods = [];
        foreach ($this->components->all() as $component) {
            $id = strtolower(trim((string) ($component->id ?? '')));
            if ($id === '' || ! empty($component->disabled)) {
                continue;
            }
            $mods[] = [
                'type' => 'component:'.$id,
                'name' => (string) ($component->name ?? $id),
                'category' => 'component',
                'description' => (string) ($component->description ?? ''),
                'content_rules' => 'Alleen gebruiken als het past bij de tenant-module. Geen extra velden verzinnen buiten de builder.',
            ];
        }

        return array_values(array_merge($native, $mods));
    }

    /**
     * @return list<string>
     */
    public function allowedSectionKeys(string $themeSlug): array
    {
        return array_values(array_map(
            fn (array $row) => $row['type'],
            array_filter($this->definitionsForTheme($themeSlug), fn (array $row) => $row['category'] === 'section')
        ));
    }

    /**
     * @return list<string>
     */
    public function allowedComponentIds(): array
    {
        return $this->components->all()
            ->reject(fn ($c) => ! empty($c->disabled))
            ->map(fn ($c) => strtolower(trim((string) ($c->id ?? ''))))
            ->filter()
            ->values()
            ->all();
    }

    public function isAllowedSectionKey(string $themeSlug, string $key): bool
    {
        $key = trim($key);
        if ($key === '') {
            return false;
        }
        if (str_starts_with($key, 'component:')) {
            $componentId = FrontendComponentService::componentIdFromKey($key);
            if ($componentId && $this->components->isDisabled($componentId)) {
                return false;
            }

            return $this->components->isAllowedComponentSectionKey($key);
        }

        return in_array($key, $this->allowedSectionKeys($themeSlug), true);
    }

    /**
     * @param  list<string>  $order
     * @return list<string>
     */
    public function filterSectionOrder(string $themeSlug, array $order): array
    {
        $out = [];
        foreach ($order as $key) {
            $key = trim((string) $key);
            if ($key !== '' && $this->isAllowedSectionKey($themeSlug, $key) && ! in_array($key, $out, true)) {
                $out[] = $key;
            }
        }

        return $out !== [] ? $out : ['hero'];
    }

    public function themeSupportsBuilder(?string $slug): bool
    {
        return FrontendTheme::usesHomeSections($slug);
    }

    private function sectionDescription(string $type): string
    {
        return match ($type) {
            'hero' => 'Grote banner met titel, subtitel, knoppen en achtergrondbeeld.',
            'stats' => 'Vier cijfers of kerngetallen naast elkaar.',
            'why_nexa' => 'Korte introductie / over-ons blok met titel en tekst.',
            'features' => 'Grid met kenmerken of diensten (titel, korte tekst, icoon).',
            'cta' => 'Call-to-action met titel, tekst en knoppen.',
            'carousel' => 'Wisselende slides met afbeelding.',
            'cards_ronde_hoeken' => 'Kaarten met afbeelding en tekst.',
            'featured_services' => 'Dienstenblok met iconen en korte omschrijvingen.',
            'email_template' => 'Formulier gekoppeld aan een e-mailtemplate.',
            'text_block' => 'Vrije rich-text met optionele sidebar.',
            default => 'Bestaande Nexa PageBuilder-sectie.',
        };
    }

    private function sectionRules(string $type): string
    {
        return match ($type) {
            'hero' => 'Headline 6-12 woorden, voordeel duidelijk, geen verzinsels. Subtitel max 2-3 zinnen. Primary CTA actiegericht. Interne URL alleen naar sitemap-slugs.',
            'features' => '3-6 items. Korte titels. Omschrijving max circa 25 woorden. Alleen toegestane iconen.',
            'featured_services' => '3-6 diensten. Geen prijzen of claims zonder trusted fact.',
            'stats' => 'Max 4 items. Geen cijfers verzinnen die niet in trusted facts of bedrijfsdata staan; anders kwalitatieve labels.',
            'cta' => 'Eén duidelijke actie. URL intern en geldig.',
            'why_nexa' => 'Geen overdreven superlatieven zonder bron.',
            'text_block' => 'HTML-paragrafen, geen scripts. Feiten alleen uit trusted facts.',
            default => 'Houd teksten kort en consistent met de website brief.',
        };
    }
}

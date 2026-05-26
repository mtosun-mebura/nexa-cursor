<?php

namespace App\Services;

use App\Models\WebsitePage;
use Illuminate\Support\Facades\Schema;

class CentralWelcomePageService
{
    public function __construct(
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Zorgt dat er precies één centrale welkom-pagina bestaat (company_id en module_name null).
     */
    public function ensurePageExists(): WebsitePage
    {
        $theme = $this->websiteBuilder->getActiveTheme();
        $defaults = $this->defaultCentralHomeSections($theme?->slug ?? 'modern');

        $defaultsForCreate = [
            'title' => 'NEXA Welkom (centraal domein)',
            'page_type' => 'custom',
            'meta_description' => 'NEXA — modulair SaaS-platform',
            'content' => null,
            'home_sections' => $defaults,
            'is_active' => true,
            'show_in_menu' => false,
            'sort_order' => 0,
            'frontend_theme_id' => $theme?->id,
        ];

        $table = (new WebsitePage)->getTable();
        $keys = [
            'slug' => WebsitePage::CENTRAL_WELCOME_SLUG,
            'module_name' => null,
        ];
        if (Schema::hasColumn($table, 'company_id')) {
            $keys['company_id'] = null;
        }

        // Belangrijk: bestaande centrale pagina niet overschrijven met defaults.
        // Alleen aanmaken als hij nog niet bestaat.
        $page = WebsitePage::query()->firstOrCreate($keys, $defaultsForCreate);

        if (empty($page->home_sections)) {
            $page->home_sections = $defaults;
            $page->save();
        }

        return $page;
    }

    /**
     * Forceer de centrale welkom-tekst/secties op basis van de huidige standaard-inhoud.
     */
    public function syncDefaultContent(): WebsitePage
    {
        $page = $this->ensurePageExists();
        $theme = $this->websiteBuilder->getActiveTheme();
        $page->home_sections = $this->defaultCentralHomeSections($theme?->slug ?? 'modern');
        $page->save();

        return $page;
    }

    private function defaultCentralHomeSections(string $themeSlug): array
    {
        $sections = WebsitePage::defaultHomeSectionsForTheme($themeSlug);

        $sections['hero']['title'] = 'Welkom bij NEXA';
        $sections['hero']['title_highlight'] = 'NEXA';
        $sections['hero']['subtitle'] = 'Het modulaire SaaS-platform dat meegroeit met uw bedrijf. Kies de modules die u nodig heeft en ga direct aan de slag.';
        $sections['hero']['cta_primary_text'] = 'Bekijk modules';
        $sections['hero']['cta_primary_url'] = '#modules-overview';
        $sections['hero']['cta_secondary_text'] = 'Naar admin';
        $sections['hero']['cta_secondary_url'] = '/admin/login';

        $sections['why_nexa']['title'] = 'Waarom NEXA?';
        $sections['why_nexa']['subtitle'] = 'Modulair, multi-tenant, white-label en veilig. NEXA groeit mee met uw bedrijf.';

        $sections['cta']['title'] = 'Klaar om te starten?';
        $sections['cta']['subtitle'] = 'Log in op het admin-paneel om modules te installeren, bedrijven aan te maken en uw platform in te richten.';
        $sections['cta']['cta_primary_text'] = 'Naar Admin';
        $sections['cta']['cta_primary_url'] = '/admin/login';
        $sections['cta']['cta_secondary_text'] = 'Bekijk modules';
        $sections['cta']['cta_secondary_url'] = '#modules-overview';

        $sections['section_order'] = [
            'hero',
            'component:website.nexa_modules_overview',
            'why_nexa',
            'cta',
        ];
        $sections['component:website.nexa_modules_overview'] = [
            'eyebrow' => 'Onze modules',
            'title' => 'Alles wat uw bedrijf nodig heeft',
            'subtitle' => 'Elke module werkt standalone of in combinatie. Installeer alleen wat u nodig heeft.',
            'items' => [
                [
                    'name' => 'NEXA Skillmatching',
                    'description' => 'AI-gestuurde vacature-matching, kandidaatbeheer en sollicitatieflow. Van publicatie tot plaatsing in een gestroomlijnd proces.',
                    'features' => ['Vacaturebeheer met branches en functies', 'Kandidaat-pipeline en interviews', 'AI-matching op skills, locatie en ervaring'],
                    'badge' => 'Beschikbaar',
                    'badge_variant' => 'available',
                    'icon' => 'user-group',
                ],
                [
                    'name' => 'NEXA Taxi',
                    'description' => 'Compleet ritbeheer voor taxi- en vervoersbedrijven. Van boeking tot facturatie, met voertuig- en tarievenbeheer.',
                    'features' => ['Voertuigbeheer met foto\'s en kenmerken', 'Ritaanvragen en boekingen', 'Flexibele tarieven per voertuigtype'],
                    'badge' => 'Beschikbaar',
                    'badge_variant' => 'available',
                    'icon' => 'truck',
                ],
                [
                    'name' => 'NEXA Garage',
                    'description' => 'Werkplaatsbeheer voor garages en autobedrijven. Werkorders, planning, onderdelen en klantcommunicatie.',
                    'features' => ['Werkorderbeheer en planning', 'Voertuighistorie per klant', 'Onderdelenvoorraad en leveranciers'],
                    'badge' => 'Binnenkort',
                    'badge_variant' => 'soon',
                    'icon' => 'cog-6-tooth',
                ],
            ],
        ];
        $sections['visibility']['component:website.nexa_modules_overview'] = true;

        return $sections;
    }
}

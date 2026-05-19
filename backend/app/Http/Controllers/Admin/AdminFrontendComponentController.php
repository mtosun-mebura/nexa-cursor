<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsitePage;
use App\Services\FrontendComponentService;
use App\Services\WebsiteBuilderService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Overzicht van front-end componenten (alleen lezen; aanpassen alleen in code).
 */
class AdminFrontendComponentController extends Controller
{
    public function __construct(
        protected FrontendComponentService $componentService,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    public function index(): View
    {
        $this->ensureSuperAdmin();
        $activeModuleName = $this->websiteBuilder->getActiveModuleName();
        $components = $this->catalogItems();
        $grouped = $components->isEmpty()
            ? new Collection
            : $components->groupBy('module_name');

        return view('admin.frontend-components.index', compact('grouped', 'activeModuleName'));
    }

    public function demo(string $componentId): View
    {
        $this->ensureSuperAdmin();
        $component = $this->catalogItems()
            ->first(fn ($c) => strcasecmp((string) ($c->id ?? ''), trim($componentId)) === 0);
        if (! $component) {
            abort(404, 'Component niet gevonden.');
        }

        $sampleGoogleReviews = [
            'place_name' => 'NEXA Demo Bedrijf',
            'rating' => 4.8,
            'user_rating_count' => 128,
            'write_review_url' => 'https://www.google.com/maps',
            'reviews' => [
                ['author_name' => 'Sanne de Vries', 'rating' => 5, 'text' => 'Topservice en snelle opvolging.', 'time' => '2 weken geleden'],
                ['author_name' => 'Murat Kaya', 'rating' => 5, 'text' => 'Zeer tevreden over de samenwerking.', 'time' => '1 maand geleden'],
                ['author_name' => 'Lotte Janssen', 'rating' => 4, 'text' => 'Duidelijke communicatie en goed resultaat.', 'time' => '3 maanden geleden'],
                ['author_name' => 'Koen Bos', 'rating' => 5, 'text' => 'Aanrader voor bedrijven die willen groeien.', 'time' => '4 maanden geleden'],
            ],
        ];
        $sampleJobs = collect([
            (object) [
                'id' => 1,
                'title' => 'Frontend Developer',
                'description' => 'Bouw moderne webinterfaces met Laravel en Tailwind.',
                'salary_min' => 3200,
                'salary_max' => 4600,
                'location' => 'Rotterdam',
                'company' => (object) ['name' => 'NEXA Tech'],
            ],
            (object) [
                'id' => 2,
                'title' => 'Recruitment Specialist',
                'description' => 'Vind en begeleid talent in verschillende branches.',
                'salary_min' => 3000,
                'salary_max' => 4200,
                'location' => 'Utrecht',
                'company' => (object) ['name' => 'NEXA People'],
            ],
            (object) [
                'id' => 3,
                'title' => 'Accountmanager SaaS',
                'description' => 'Onderhoud klantrelaties en groei strategische accounts.',
                'salary_min' => 3400,
                'salary_max' => 5000,
                'location' => 'Amsterdam',
                'company' => (object) ['name' => 'NEXA Groei'],
            ],
        ]);
        $demoFormFields = collect([
            (object) ['name' => 'voornaam', 'label' => 'Voornaam', 'is_required' => true, 'validation_rule' => ''],
            (object) ['name' => 'achternaam', 'label' => 'Achternaam', 'is_required' => true, 'validation_rule' => ''],
            (object) ['name' => 'email', 'label' => 'E-mailadres', 'is_required' => true, 'validation_rule' => 'email'],
            (object) ['name' => 'telefoon', 'label' => 'Telefoonnummer', 'is_required' => false, 'validation_rule' => ''],
            (object) ['name' => 'omschrijving', 'label' => 'Omschrijving', 'is_required' => true, 'validation_rule' => ''],
        ]);
        $demoEmailTemplate = new class($demoFormFields)
        {
            public int $id = 999001;

            public function __construct(private Collection $fields) {}

            public function getOrderedFormFields(): Collection
            {
                return $this->fields;
            }
        };

        $homeSections = [
            'component:website.nexa_modules_overview' => [],
            'component:taxi.tarieven' => [],
            'component:taxi.boekingsmodule' => [],
            'component:website.email_template_section' => [
                'title' => 'Vraag direct een demo aan',
            ],
            'visibility' => [],
        ];

        $sectionKey = 'component:' . $component->id;

        return view('admin.frontend-components.demo', [
            'component' => $component,
            'sectionKey' => $sectionKey,
            'homeSections' => $homeSections,
            'googleReviews' => $sampleGoogleReviews,
            'jobs' => $sampleJobs,
            'page' => new WebsitePage(['title' => 'Demo', 'slug' => 'demo']),
            'themeSlug' => 'modern',
            'themeSettings' => [],
            'googleMapsApiKey' => (string) (config('maps.api_key') ?? ''),
            'emailTemplate' => $demoEmailTemplate,
            'formFields' => $demoFormFields,
            'emailTemplateBySectionKey' => ['component:website.email_template_section' => $demoEmailTemplate],
        ]);
    }

    private function catalogItems(): Collection
    {
        $components = $this->componentService->all()->values();
        $themeSectionTypes = collect(WebsitePage::getAvailableHomeSectionTypesForTheme('modern'))
            ->map(function (array $st) {
                $type = trim((string) ($st['type'] ?? ''));
                $label = trim((string) ($st['label'] ?? $type));
                if ($type === '') {
                    return null;
                }

                return (object) [
                    'id' => 'section.' . $type,
                    'name' => $label,
                    'module_name' => 'Algemeen',
                    'description' => 'Ingebouwde pagina-sectie uit de website builder.',
                    'is_section_type' => true,
                    'section_type' => $type,
                ];
            })
            ->filter()
            ->values();

        return $components
            ->merge($themeSectionTypes)
            ->unique(fn ($c) => strtolower((string) ($c->id ?? '')))
            ->values();
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot componenten.');
        }
    }
}

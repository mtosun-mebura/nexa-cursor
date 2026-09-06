<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\AiWebsite\AiWebsiteOrchestrator;
use App\Services\WebsiteAiGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AdminWebsiteAiGeneratorController extends Controller
{
    /** @var list<array{key: string, label: string, primary: string, secondary: string}> */
    public const COLOR_PRESETS = [
        ['key' => 'navy-gold', 'label' => 'Navy & goud', 'primary' => '#1e3a8a', 'secondary' => '#0f172a'],
        ['key' => 'bordeaux', 'label' => 'Bordeaux & crème', 'primary' => '#7f1d1d', 'secondary' => '#1c1917'],
        ['key' => 'forest', 'label' => 'Bosgroen & antraciet', 'primary' => '#166534', 'secondary' => '#14532d'],
        ['key' => 'sky', 'label' => 'Hemelsblauw', 'primary' => '#2563eb', 'secondary' => '#1e3a8a'],
        ['key' => 'taxi', 'label' => 'Taxi geel & zwart', 'primary' => '#eab308', 'secondary' => '#171717'],
        ['key' => 'custom', 'label' => 'Eigen kleuren', 'primary' => '#1e3a8a', 'secondary' => '#0f172a'],
    ];

    public function create(Request $request): View
    {
        $this->ensureSuperAdmin();

        $companies = Company::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active', 'frontend_theme_id', 'website', 'website_theme_settings']);
        $themes = FrontendTheme::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active', 'settings'])
            ->filter(fn (FrontendTheme $theme) => FrontendTheme::usesHomeSections((string) $theme->slug))
            ->values();

        $selectedCompanyId = (int) old('company_id', $request->session()->get('selected_tenant') ?: ($companies->first()?->id ?? 0));
        $selectedCompany = $companies->firstWhere('id', $selectedCompanyId);
        $existingPageCount = 0;
        if ($selectedCompany) {
            $existingPageCount = WebsitePage::query()
                ->where('company_id', $selectedCompany->id)
                ->count();
        }

        $defaultThemeId = old('frontend_theme_id', $selectedCompany?->frontend_theme_id ?: $themes->firstWhere('is_active', true)?->id ?: $themes->first()?->id);
        $defaultTheme = $themes->firstWhere('id', (int) $defaultThemeId) ?? $themes->first();
        $themePalettes = $themes->mapWithKeys(function (FrontendTheme $theme) {
            $primary = FrontendTheme::normalizeHexColor((string) (($theme->settings['primary_color'] ?? null) ?: '#2563eb')) ?: '#2563eb';
            $secondary = FrontendTheme::normalizeHexColor((string) (($theme->settings['secondary_color'] ?? null) ?: ''))
                ?: FrontendTheme::defaultSecondaryFor($primary);

            return [
                (string) $theme->id => [
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'primary' => $primary,
                    'secondary' => $secondary,
                ],
            ];
        })->all();

        $companyPalette = is_array($selectedCompany?->website_theme_settings) ? $selectedCompany->website_theme_settings : [];
        $defaultPrimary = FrontendTheme::normalizeHexColor((string) old('primary_color', $companyPalette['primary_color'] ?? ($themePalettes[(string) $defaultThemeId]['primary'] ?? '#2563eb')));
        $defaultSecondary = FrontendTheme::normalizeHexColor((string) old('secondary_color', $companyPalette['secondary_color'] ?? ($themePalettes[(string) $defaultThemeId]['secondary'] ?? '#0f172a')));
        $openaiConfigured = is_string(config('services.openai.api_key')) && trim((string) config('services.openai.api_key')) !== '';

        $themePresets = [];
        foreach ($themePalettes as $palette) {
            $themePresets[] = [
                'key' => 'theme-'.($palette['slug'] ?? 'custom'),
                'label' => $palette['name'].' (thema)',
                'primary' => $palette['primary'],
                'secondary' => $palette['secondary'],
            ];
        }

        return view('admin.website-ai.create', [
            'companies' => $companies,
            'themes' => $themes,
            'colorPresets' => array_merge($themePresets, self::COLOR_PRESETS),
            'themePalettes' => $themePalettes,
            'defaultPrimary' => $defaultPrimary !== '' ? $defaultPrimary : '#2563eb',
            'defaultSecondary' => $defaultSecondary !== '' ? $defaultSecondary : '#0f172a',
            'selectedCompanyId' => $selectedCompanyId,
            'existingPageCount' => $existingPageCount,
            'defaultThemeId' => $defaultThemeId,
            'defaultTheme' => $defaultTheme,
            'openaiConfigured' => $openaiConfigured,
            'goals' => config('ai_website.goals', []),
            'styles' => config('ai_website.styles', []),
            'tones' => config('ai_website.tones', []),
        ]);
    }

    public function generate(Request $request, AiWebsiteOrchestrator $orchestrator): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'source_type' => ['nullable', 'in:new,url'],
            'source_url' => ['nullable', 'string', 'max:500'],
            'context' => ['required', 'string', 'max:4000'],
            'goals' => ['nullable', 'array'],
            'goals.*' => ['string', 'max:40'],
            'style' => ['nullable', 'string', 'max:40'],
            'tone' => ['nullable', 'string', 'max:40'],
            'max_pages' => ['required', 'integer', 'min:1', 'max:'.WebsiteAiGeneratorService::MAX_PAGES],
            'frontend_theme_id' => ['required', 'integer', 'exists:frontend_themes,id'],
            'color_preset' => ['nullable', 'string', 'max:40'],
            'primary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'secondary_color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6})$/'],
            'generate_images' => ['sometimes', 'boolean'],
            'replace_existing' => ['sometimes', 'boolean'],
        ], [
            'context.required' => 'Beschrijf waar de website over moet gaan.',
            'primary_color.regex' => 'Kies een geldige merkkleur (hex).',
            'secondary_color.regex' => 'Kies een geldige secundaire kleur (hex).',
        ]);

        $theme = FrontendTheme::query()->findOrFail((int) $data['frontend_theme_id']);
        if (! FrontendTheme::usesHomeSections((string) $theme->slug) && ! in_array(strtolower((string) $theme->slug), FrontendTheme::HOME_SECTION_SLUGS, true)) {
            return back()->withInput()->with('error', 'Kies een thema dat de website builder ondersteunt.');
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        try {
            $result = $orchestrator->run([
                'company_id' => (int) $data['company_id'],
                'user_id' => (int) auth()->id(),
                'source_type' => $data['source_type'] ?? 'new',
                'source_url' => $data['source_url'] ?? '',
                'context' => $data['context'],
                'goals' => $data['goals'] ?? [],
                'style' => $data['style'] ?? 'professional',
                'tone' => $data['tone'] ?? 'zakelijk',
                'max_pages' => (int) $data['max_pages'],
                'frontend_theme_id' => (int) $data['frontend_theme_id'],
                'primary_color' => $data['primary_color'],
                'secondary_color' => $data['secondary_color'],
                'generate_images' => $request->boolean('generate_images'),
                'replace_existing' => $request->boolean('replace_existing'),
            ]);
        } catch (Throwable $e) {
            report($e);

            $hint = config('app.debug')
                ? ' '.$e->getMessage()
                : ' Controleer de bron-URL en probeer het opnieuw.';

            return back()->withInput()->with('error', 'Genereren is mislukt.'.$hint);
        }

        $created = count($result['created']);
        $updated = count($result['updated']);
        $skipped = count($result['skipped']);
        $parts = [];
        if ($created > 0) {
            $parts[] = $created === 1 ? '1 pagina als concept aangemaakt' : $created.' pagina\'s als concept aangemaakt';
        }
        if ($updated > 0) {
            $parts[] = $updated === 1 ? '1 pagina als concept bijgewerkt' : $updated.' pagina\'s als concept bijgewerkt';
        }
        if ($skipped > 0) {
            $parts[] = $skipped === 1 ? '1 bestaande pagina overgeslagen' : $skipped.' bestaande pagina\'s overgeslagen';
        }
        if ($result['images'] > 0) {
            $parts[] = $result['images'] === 1 ? '1 afbeelding in de mediabibliotheek' : $result['images'].' afbeeldingen in de mediabibliotheek';
        }
        if ($parts === []) {
            $parts[] = 'Geen pagina\'s gezet. Zet “Bestaande pagina’s overschrijven” aan.';
        }
        $aiNote = $result['used_openai']
            ? ' Teksten komen uit OpenAI.'
            : ' OpenAI was niet beschikbaar; er is een professionele basisstructuur gezet die u in de builder kunt finetunen.';
        $sourceNote = $result['source_pages'] > 0
            ? ' Bronwebsite: '.$result['source_pages'].' pagina\'s doorgenomen.'
            : '';

        $request->session()->put('selected_tenant', (int) $data['company_id']);

        $pageId = $result['homepage_page_id'] ?? null;
        if ($pageId) {
            $params = ['website_page' => $pageId, 'tenant_company' => (int) $data['company_id'], 'saved' => 1];
            $page = WebsitePage::query()->find($pageId);
            if ($page && trim((string) $page->module_name) !== '') {
                $params['module'] = $page->module_name;
            }

            return redirect()
                ->route('admin.website-pages.builder-v2.edit', $params)
                ->with('success', implode('. ', $parts).'.'.$sourceNote.$aiNote.(($created > 0 || $updated > 0) ? ' Pagina\'s zijn concepten (niet gepubliceerd).' : ''));
        }

        return redirect()
            ->route('admin.website-pages.index', ['tenant_company' => (int) $data['company_id'], 'saved' => 1])
            ->with('success', implode('. ', $parts).'.'.$sourceNote.$aiNote);
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Alleen super-admins kunnen websites met AI genereren.');
        }
    }
}

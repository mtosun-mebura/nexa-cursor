<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\Vacancy;
use App\Models\WebsitePage;
use App\Services\FrontendComponentService;
use App\Services\ModuleContextService;
use App\Services\ModuleDatabaseService;
use App\Services\ModuleManager;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminWebsitePageController extends Controller
{
    public function __construct(
        protected ModuleManager $moduleManager,
        protected WebsiteBuilderService $websiteBuilder,
        protected ModuleDatabaseService $moduleDb,
        protected ModuleContextService $moduleContext
    ) {}

    /**
     * Lijst website-pagina's. Kernpagina's (geen module) uit hoofddatabase; plus alle module-pagina's
     * (per module-DB of in hoofddatabase bij single-DB). Worden op de site in het actieve thema getoond.
     */
    public function index(Request $request)
    {
        $this->ensureSuperAdmin();
        $activeModuleName = $this->getActiveModuleNameForFrontend();

        $tenantCompanyId = $this->resolveTenantCompanyIdForWebsitePagesList($request);
        $pages = $this->websiteBuilder->loadAllPagesForAdminIndex(
            $tenantCompanyId,
            $tenantCompanyId !== null
        );
        $activeTheme = $this->websiteBuilder->getActiveTheme();
        $wizardBackUrl = $this->resolveTenantWizardReturnUrl($request);
        $wizardIndexQuery = $this->websitePagesIndexQuery($request);
        $websiteTenantContext = $this->buildWebsitePageCompanyContext($request, null);
        $websitePagesCompanyNames = $this->websitePagesCompanyNameMapForIndex($pages);

        return view('admin.website-pages.index', compact('pages', 'activeModuleName', 'activeTheme', 'wizardBackUrl', 'wizardIndexQuery', 'websiteTenantContext', 'websitePagesCompanyNames'));
    }

    public function create(Request $request)
    {
        $this->ensureSuperAdmin();
        $installedModules = $this->moduleManager->getInstalledModules();
        $themes = FrontendTheme::orderBy('slug')->get();
        $defaultTheme = $this->websiteBuilder->getActiveTheme();
        $moduleThemes = $this->getModuleThemesForForm($installedModules);
        $env = app(\App\Services\EnvService::class);
        $googleMapsApiKey = $env->getGoogleMapsApiKey();
        $googleMapsMapId = $env->getGoogleMapsMapId();
        $emailTemplates = $this->getEmailTemplatesForWebsiteForm();
        $wizardBackUrl = $this->resolveTenantWizardReturnUrl($request);
        $wizardIndexQuery = $this->websitePagesIndexQuery($request);

        $moduleNameForComponents = $this->moduleNameForWebsiteComponents(null, $request);

        $websiteTenantContext = $this->buildWebsitePageCompanyContext($request, null);

        return view('admin.website-pages.create', compact('installedModules', 'themes', 'defaultTheme', 'moduleThemes', 'googleMapsApiKey', 'googleMapsMapId', 'emailTemplates', 'wizardBackUrl', 'wizardIndexQuery', 'moduleNameForComponents', 'websiteTenantContext'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $moduleName = $this->resolveCanonicalModuleName($request->input('module_name'));
        $connection = null;
        if ($moduleName !== null && $this->moduleDb->supportsModuleDatabases()) {
            $connection = $this->moduleDb->getModuleConnectionName($moduleName);
        }
        $slugRule = $this->buildSlugUniqueRule(
            $connection,
            $moduleName,
            null,
            $this->resolveCompanyIdForWebsitePageSlugRule($request, null)
        );
        $companyIdRules = $this->websitePageCompanyIdValidationRules($request, null, $connection);
        $data = $request->validate(array_merge([
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/', $slugRule],
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'page_type' => 'required|in:home,about,contact,custom,module',
            'module_name' => 'nullable|string|max:255',
            'frontend_theme_id' => 'nullable|integer|exists:frontend_themes,id',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], $companyIdRules), [
            'slug.unique' => 'Deze slug wordt al gebruikt voor dit bedrijf binnen deze module. Kies een andere slug.',
            'company_id.required' => 'Kies een bedrijf waaraan deze pagina wordt gekoppeld.',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $table = (new WebsitePage)->getTable();
        if ($connection !== null) {
            if (! \Illuminate\Support\Facades\Schema::connection($connection)->hasColumn($table, 'show_in_menu')) {
                unset($data['show_in_menu']);
            } else {
                $data['show_in_menu'] = $this->requestHasInput($request, 'show_in_menu')
                    ? $request->boolean('show_in_menu')
                    : true;
            }
            if (! \Illuminate\Support\Facades\Schema::connection($connection)->hasColumn($table, 'sort_order')) {
                unset($data['sort_order']);
            } else {
                $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
            }
        } else {
            $data['show_in_menu'] = $this->requestHasInput($request, 'show_in_menu')
                ? $request->boolean('show_in_menu')
                : true;
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        }
        $activeTheme = $this->websiteBuilder->getActiveTheme();
        $data['frontend_theme_id'] = $activeTheme ? (int) $activeTheme->id : null;
        $data['module_name'] = $moduleName;
        $themeSlug = $activeTheme ? ($activeTheme->slug ?? 'modern') : 'modern';
        $input = $this->getHomeSectionsInput($request);
        if ($data['page_type'] === 'home') {
            $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, false);
        } else {
            $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, true);
        }
        $connForCompany = $connection ?? config('database.default');
        $this->mergeCompanyIdIntoWebsitePageSaveData($data, $request, $connForCompany, null);
        if ($connection !== null) {
            $data['frontend_theme_id'] = null;
            WebsitePage::on($connection)->create($data);
        } else {
            WebsitePage::create($data);
        }

        return redirect()->route('admin.website-pages.index', $this->websitePagesIndexQuery($request))->with('success', 'Pagina aangemaakt.');
    }

    /**
     * Redirect to edit form (resource has no dedicated show view).
     * Bij module-pagina: ?module= meegeven zodat binding uit module-DB laadt.
     */
    public function show(WebsitePage $website_page)
    {
        $url = route('admin.website-pages.edit', $website_page);
        if ($website_page->module_name) {
            $url .= '?module='.rawurlencode($website_page->module_name);
        }

        return redirect($url);
    }

    public function edit(Request $request, WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        // Zorg dat bij een module-pagina altijd ?module= in de URL staat, zodat route binding de pagina uit de module-DB laadt (anders toont de select geen opgeslagen template_id).
        if ($website_page->module_name && trim((string) request()->query('module')) !== trim((string) $website_page->module_name)) {
            $url = route('admin.website-pages.edit', ['website_page' => $website_page->id]).'?module='.rawurlencode($website_page->module_name);
            $wizardQ = $this->websitePagesIndexQuery($request);
            if ($wizardQ !== []) {
                $url .= '&'.http_build_query($wizardQ);
            }

            return redirect($url);
        }
        $installedModules = $this->moduleManager->getInstalledModules();
        $themes = FrontendTheme::orderBy('slug')->get();
        $defaultTheme = $this->websiteBuilder->getActiveTheme();
        $moduleThemes = $this->getModuleThemesForForm($installedModules);
        $env = app(\App\Services\EnvService::class);
        $googleMapsApiKey = $env->getGoogleMapsApiKey();
        $googleMapsMapId = $env->getGoogleMapsMapId();
        $homeSections = $website_page->getHomeSections();
        $includeTemplateIds = [];
        foreach ($homeSections['section_order'] ?? [] as $sectionKey) {
            $base = is_string($sectionKey) ? preg_replace('/_\d+$/', '', $sectionKey) : '';
            if ($base === 'email_template') {
                $tid = $homeSections[$sectionKey]['template_id'] ?? null;
                if ($tid !== null && $tid !== '') {
                    $includeTemplateIds[] = (int) $tid;
                }
            }
        }
        // Hoofdlijst altijd uit standaard-DB (waar templates staan). Bij module-pagina: opgeslagen template(s) uit module-DB toevoegen zodat ze geselecteerd kunnen worden.
        $includeFromConnection = null;
        $moduleName = $website_page->module_name;
        if ($moduleName && $this->moduleDb->supportsModuleDatabases()) {
            $connName = $this->moduleDb->getModuleConnectionName($moduleName);
            if (Config::has("database.connections.{$connName}")) {
                $includeFromConnection = $connName;
            }
        }
        $emailTemplates = $this->getEmailTemplatesForWebsiteForm(null, array_unique($includeTemplateIds), $includeFromConnection);
        $emailTemplateSelectedIds = [];
        $rawSections = $website_page->home_sections ?? [];
        foreach (($rawSections['section_order'] ?? $homeSections['section_order'] ?? []) as $sectionKey) {
            if (! is_string($sectionKey)) {
                continue;
            }
            $base = preg_replace('/_\d+$/', '', $sectionKey);
            if ($base === 'email_template') {
                $tid = $rawSections[$sectionKey]['template_id'] ?? $homeSections[$sectionKey]['template_id'] ?? null;
                $emailTemplateSelectedIds[$sectionKey] = $tid !== null && $tid !== '' ? (int) $tid : 0;
            }
        }

        $wizardBackUrl = $this->resolveTenantWizardReturnUrl($request);
        $wizardIndexQuery = $this->websitePagesIndexQuery($request);
        $moduleNameForComponents = $this->moduleNameForWebsiteComponents($website_page->module_name, $request);
        $websiteTenantContext = $this->buildWebsitePageCompanyContext($request, $website_page);

        return view('admin.website-pages.edit', [
            'page' => $website_page,
            'installedModules' => $installedModules,
            'themes' => $themes,
            'defaultTheme' => $defaultTheme,
            'moduleThemes' => $moduleThemes,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsMapId' => $googleMapsMapId,
            'emailTemplates' => $emailTemplates,
            'emailTemplateSelectedIds' => $emailTemplateSelectedIds,
            'wizardBackUrl' => $wizardBackUrl,
            'wizardIndexQuery' => $wizardIndexQuery,
            'moduleNameForComponents' => $moduleNameForComponents,
            'websiteTenantContext' => $websiteTenantContext,
        ]);
    }

    /**
     * De modulenaam van de "actieve" frontend: de module die het actieve thema gebruikt,
     * anders de eerste actieve module. Null als er geen actieve module is.
     */
    private function getActiveModuleNameForFrontend(): ?string
    {
        $activeTheme = $this->websiteBuilder->getActiveTheme();
        if ($activeTheme) {
            $module = Module::where('installed', true)->where('active', true)
                ->where('frontend_theme_id', $activeTheme->id)
                ->first();
            if ($module) {
                return $module->name;
            }
        }
        $first = Module::where('installed', true)->where('active', true)->first();

        return $first ? $first->name : null;
    }

    /**
     * Modulenaam voor het filteren van website-builder componenten (sectie "Componenten").
     * Eerst de opgeslagen pagina-module; anders query/old; anders actieve frontend-module.
     */
    private function moduleNameForWebsiteComponents(?string $pageModuleName, Request $request): ?string
    {
        if ($pageModuleName !== null && trim((string) $pageModuleName) !== '') {
            return $this->resolveCanonicalModuleName($pageModuleName);
        }
        $fromOld = old('module_name');
        if ($fromOld !== null && trim((string) $fromOld) !== '') {
            return $this->resolveCanonicalModuleName($fromOld);
        }
        $q = $request->query('module_name') ?? $request->query('module');
        if ($q !== null && trim((string) $q) !== '') {
            return $this->resolveCanonicalModuleName((string) $q);
        }

        return $this->getActiveModuleNameForFrontend();
    }

    /**
     * Map: modulenaam (zoals getName()) => frontend_theme_id.
     * Gebruikt dezelfde case-insensitive lookup als getModuleThemesForForm, voor filter in index.
     */
    private function getModuleThemeIdsByModuleName(): \Illuminate\Support\Collection
    {
        $installedModules = $this->moduleManager->getInstalledModules();
        $ids = collect();
        foreach ($installedModules as $module) {
            $name = is_object($module) ? $module->getName() : ($module['name'] ?? null);
            if ($name === null || $name === '') {
                continue;
            }
            $name = (string) $name;
            $moduleModel = Module::where('installed', true)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->first();
            if ($moduleModel !== null) {
                $ids->put(strtolower($name), $moduleModel->frontend_theme_id);
            }
        }

        return $ids;
    }

    /**
     * Map: modulenaam (zoals getName()) => Module model met theme.
     * Gebruikt case-insensitive lookup op modules.name zodat we dezelfde rij vinden als bij opslaan.
     */
    private function getModuleThemesForForm(array $installedModules): \Illuminate\Support\Collection
    {
        $moduleThemes = collect();
        foreach ($installedModules as $module) {
            $name = is_object($module) ? $module->getName() : ($module['name'] ?? null);
            if ($name === null || $name === '') {
                continue;
            }
            $name = (string) $name;
            $moduleModel = Module::where('installed', true)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->with('theme')
                ->first();
            if ($moduleModel) {
                $moduleThemes->put($name, $moduleModel);
            }
        }

        return $moduleThemes;
    }

    /**
     * JSON: default_blocks voor het thema van de gekozen module (of actief thema bij geen module).
     * Gebruikt bij create/edit om de vaste blokken van het thema in te laden.
     */
    public function themeBlocks(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();
        $moduleName = $request->query('module_name');
        $theme = null;
        if ($moduleName) {
            $module = Module::where('name', $moduleName)->where('installed', true)->first();
            if ($module && $module->frontend_theme_id) {
                $theme = FrontendTheme::find($module->frontend_theme_id);
            }
        }
        if (! $theme) {
            $theme = $this->websiteBuilder->getActiveTheme();
        }
        $blocks = $theme && $theme->default_blocks ? $theme->default_blocks : [];

        return response()->json(['blocks' => $blocks, 'theme_slug' => $theme ? $theme->slug : null]);
    }

    /**
     * HTML voor één sectiekaart (hero, stats, why_nexa, features, cta).
     * Gebruikt wanneer een sectie is verwijderd en opnieuw toegevoegd moet kunnen worden.
     */
    public function sectionCardHtml(Request $request)
    {
        $this->ensureSuperAdmin();
        $valid = $request->validate([
            'type' => 'required|string|in:hero,stats,why_nexa,features,cta,carousel,cards_ronde_hoeken,featured_services,email_template,text_block',
            'theme' => 'nullable|string|max:64',
        ]);
        $type = $valid['type'];
        $theme = $request->input('theme', 'modern');
        $defaults = WebsitePage::defaultHomeSectionsForTheme($theme);
        $sections = [
            'section_order' => [$type],
            'visibility' => $defaults['visibility'] ?? [],
            'hero' => $defaults['hero'] ?? [],
            'stats' => $defaults['stats'] ?? ['items' => [['value' => '', 'label' => ''], ['value' => '', 'label' => ''], ['value' => '', 'label' => ''], ['value' => '', 'label' => '']], 'background' => '', 'background_image' => ''],
            'why_nexa' => $defaults['why_nexa'] ?? [],
            'features' => $defaults['features'] ?? [],
            'cta' => $defaults['cta'] ?? [],
            'carousel' => $defaults['carousel'] ?? ['items' => []],
            'cards_ronde_hoeken' => $defaults['cards_ronde_hoeken'] ?? ['cards_per_row' => 4, 'items' => [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => '']]],
            'featured_services' => $defaults['featured_services'] ?? ['title' => 'Diensten', 'subtitle' => 'Onze diensten in het kort.', 'blocks_per_row' => 3, 'block_size' => 'medium', 'block_align' => 'center', 'icon_size' => 'medium', 'icon_align' => 'center', 'card_bg_color' => '', 'animation_speed' => 'slow', 'items' => [['icon' => 'briefcase', 'title' => '', 'description' => ''], ['icon' => 'cog-6-tooth', 'title' => '', 'description' => ''], ['icon' => 'user-group', 'title' => '', 'description' => '']]],
            'email_template' => $defaults['email_template'] ?? ['title' => 'Informatie aanvragen', 'template_id' => null],
            'text_block' => $defaults['text_block'] ?? ['content' => '', 'alignment' => 'left', 'side_component_key' => '', 'image_url' => '', 'width_percent' => 100],
            'footer' => $defaults['footer'] ?? [],
            'copyright' => $defaults['copyright'] ?? '',
        ];
        $emailTemplates = $this->getEmailTemplatesForWebsiteForm();
        $html = view('admin.website-pages.partials.home-sections', [
            'homeSections' => $sections,
            'themeSlug' => $theme,
            'isNonHomePage' => false,
            'sectionCardOnly' => true,
            'emailTemplates' => $emailTemplates,
        ])->render();

        // Eerste .home-section-card extraheren (bij sectionCardOnly rendert de partial precies één kaart)
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="section-card-wrapper">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $sortable = $xpath->query("//*[@id='home-sections-sortable']")->item(0);
        $card = null;
        if ($sortable) {
            foreach ($sortable->childNodes as $child) {
                if ($child->nodeType !== \XML_ELEMENT_NODE) {
                    continue;
                }
                $class = $child->getAttribute('class') ?? '';
                if (str_contains($class, 'home-section-card') && str_contains($class, 'kt-card')) {
                    $card = $child;
                    break;
                }
            }
        }
        $cardHtml = $card ? $dom->saveHTML($card) : '';

        return response($cardHtml, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * HTML voor één frontend-componentkaart (component:taxi.boekingsmodule, enz.).
     * Gebruikt bij "Sectie toevoegen" wanneer er nog geen kaart van dat type op de pagina staat.
     */
    public function componentSectionCardHtml(Request $request)
    {
        $this->ensureSuperAdmin();
        $raw = trim((string) $request->query('component', ''));
        if ($raw === '') {
            abort(400);
        }
        if (str_starts_with(strtolower($raw), 'component:')) {
            $raw = (string) preg_replace('/^component:+/i', '', $raw);
        }
        $componentService = app(FrontendComponentService::class);
        $comp = $componentService->getById($raw);
        if (! $comp) {
            abort(404);
        }
        $canonicalId = trim((string) ($comp->id ?? $raw));
        $sectionKey = 'component:'.$canonicalId;

        $theme = $request->input('theme', 'modern');
        $emailTemplates = $this->getEmailTemplatesForWebsiteForm();

        $moduleName = $request->query('module_name');
        $moduleNameForUploads = null;
        if (is_string($moduleName) && trim($moduleName) !== '') {
            $moduleNameForUploads = trim($moduleName);
        }

        $homeSections = [
            'section_order' => [$sectionKey],
            $sectionKey => [],
            'visibility' => [],
            'admin_collapsed' => [],
        ];

        $html = view('admin.website-pages.partials.home-sections', [
            'homeSections' => $homeSections,
            'themeSlug' => $theme,
            'isNonHomePage' => false,
            'sectionCardOnly' => true,
            'emailTemplates' => $emailTemplates,
            'moduleNameForUploads' => $moduleNameForUploads,
        ])->render();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="section-card-wrapper">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);
        $sortable = $xpath->query("//*[@id='home-sections-sortable']")->item(0);
        $card = null;
        if ($sortable) {
            foreach ($sortable->childNodes as $child) {
                if ($child->nodeType !== \XML_ELEMENT_NODE) {
                    continue;
                }
                $class = $child->getAttribute('class') ?? '';
                if (str_contains($class, 'home-section-card') && str_contains($class, 'kt-card')) {
                    $card = $child;
                    break;
                }
            }
        }
        $cardHtml = $card ? $dom->saveHTML($card) : '';

        return response($cardHtml, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Preview van de pagina met het thema van de module / standaardthema (zoals op de website).
     */
    public function preview(WebsitePage $website_page): View
    {
        $this->ensureSuperAdmin();
        $theme = $this->websiteBuilder->getThemeForPage($website_page);
        $menuPages = $this->websiteBuilder->getActiveMenuPages();
        $branding = $this->websiteBuilder->getSiteBranding(
            $this->websiteBuilder->getBrandingModuleNameForWebsitePage($website_page)
        );
        $themeSlug = $theme ? $theme->slug : 'modern';
        $themeSettings = $theme ? $theme->getSettings() : [];

        $jobs = collect();
        $isHomePage = $website_page->page_type === 'home' || $website_page->slug === 'home';
        if ($isHomePage) {
            $rotationKey = floor(now()->timestamp / (2 * 3600));
            $jobs = Cache::remember("home_jobs_rotation_{$rotationKey}", 7200, function () {
                return Vacancy::with(['company', 'category'])
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->where(function ($subQ) {
                            $subQ->where('published_at', '<=', now())
                                ->orWhereNull('published_at')
                                ->orWhereNull('publication_date');
                        });
                    })
                    ->orderBy('published_at', 'desc')
                    ->limit(6)
                    ->get();
            });
        }

        $themeHasHomeSections = in_array($themeSlug, ['modern', 'atom-v2', 'nextly-template', 'next-landing-vpn'], true);
        $useThemeHomeLayout = $themeHasHomeSections && ($website_page->page_type === 'home' || $website_page->slug === 'home' || ! empty($website_page->home_sections));
        // Altijd homeSections doorgeven wanneer de pagina home_sections heeft, zodat footer/visibility op preview werken
        $homeSections = ! empty($website_page->home_sections) ? $website_page->getHomeSections() : [];
        // E-mailtemplate per sectie (zelfde logica als frontend WebsitePageController: module-DB bij module-pagina)
        $emailTemplateBySectionKey = [];
        $templateConnection = null;
        $moduleName = $website_page->module_name;
        if ($moduleName && $this->moduleDb->supportsModuleDatabases()) {
            $connName = $this->moduleDb->getModuleConnectionName($moduleName);
            if (Config::has("database.connections.{$connName}")) {
                $templateConnection = $connName;
            }
        }
        foreach ($homeSections['section_order'] ?? [] as $sectionKey) {
            $base = is_string($sectionKey) ? preg_replace('/_\d+$/', '', $sectionKey) : '';
            if ($base === 'email_template') {
                $tid = $homeSections[$sectionKey]['template_id'] ?? null;
                if (! $tid) {
                    $emailTemplateBySectionKey[$sectionKey] = null;

                    continue;
                }
                $tidInt = (int) $tid;
                $template = \App\Models\EmailTemplate::find($tidInt);
                if (! $template && $templateConnection) {
                    $template = \App\Models\EmailTemplate::on($templateConnection)->find($tidInt);
                }
                $emailTemplateBySectionKey[$sectionKey] = $template;
            }
        }
        // Atom v2: laad thema-styles op alle paginatypes (preview) voor dezelfde weergave als home
        $loadAtomV2Styles = ($themeSlug === 'atom-v2');
        // Footer-kaart: expliciet Maps API-key en map-id doorgeven (zelfde bron als frontend)
        $env = app(\App\Services\EnvService::class);
        $googleMapsApiKey = trim((string) ($env->getGoogleMapsApiKey() ?? ''));
        $googleMapsMapId = $env->getGoogleMapsMapId() ?? '';

        $previewEditUrl = route('admin.website-pages.edit', $website_page);
        if ($website_page->module_name) {
            $previewEditUrl .= '?module='.rawurlencode($website_page->module_name);
        }
        $googleReviews = $useThemeHomeLayout ? app(\App\Services\GoogleReviewsService::class)->getReviews() : [];

        return view('frontend.website.page', [
            'page' => $website_page,
            'theme' => $theme,
            'themeSlug' => $themeSlug,
            'themeSettings' => $themeSettings,
            'menuPages' => $menuPages,
            'branding' => $branding,
            'showContactForm' => $website_page->page_type === 'contact',
            'isPreview' => true,
            'previewEditUrl' => $previewEditUrl,
            'jobs' => $jobs,
            'useModernHomeLayout' => $useThemeHomeLayout,
            'homeSections' => $homeSections,
            'emailTemplateBySectionKey' => $emailTemplateBySectionKey,
            'loadAtomV2Styles' => $loadAtomV2Styles,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsMapId' => $googleMapsMapId,
            'googleReviews' => $googleReviews,
        ]);
    }

    public function update(Request $request, WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        $moduleName = $this->resolveCanonicalModuleName($request->input('module_name'));
        // Zelfde DB als route-binding (module-DB vs hoofddatabase). Request-module_name kan afwijken;
        // dan gold hasColumn/validatie voor de verkeerde connection en werd company_id niet opgeslagen.
        $connection = $website_page->getConnectionName();
        $slugRule = $this->buildSlugUniqueRule(
            $connection,
            $moduleName,
            (int) $website_page->id,
            $this->resolveCompanyIdForWebsitePageSlugRule($request, $website_page)
        );
        $companyIdRules = $this->websitePageCompanyIdValidationRules($request, $website_page, $connection);
        $data = $request->validate(array_merge([
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                $slugRule,
            ],
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'page_type' => 'required|in:home,about,contact,custom,module',
            'module_name' => 'nullable|string|max:255',
            'frontend_theme_id' => 'nullable|integer|exists:frontend_themes,id',
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ], $companyIdRules), [
            'slug.unique' => 'Deze slug wordt al gebruikt voor dit bedrijf binnen deze module. Kies een andere slug.',
            'company_id.required' => 'Kies een bedrijf waaraan deze pagina wordt gekoppeld.',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $table = $website_page->getTable();
        // Altijd het connection van het opgeloste model (route binding), niet afgeleid van module_name in de request.
        $connForSchema = $website_page->getConnection()->getName();
        // show_in_menu: requestHasInput i.p.v. has() — "0" moet meetellen.
        $showInMenuValue = $this->requestHasInput($request, 'show_in_menu')
            ? $request->boolean('show_in_menu')
            : (bool) $website_page->getAttribute('show_in_menu');
        if (! \Illuminate\Support\Facades\Schema::connection($connForSchema)->hasColumn($table, 'show_in_menu')) {
            unset($data['show_in_menu']);
        } else {
            $data['show_in_menu'] = $showInMenuValue;
        }
        if (! \Illuminate\Support\Facades\Schema::connection($connForSchema)->hasColumn($table, 'sort_order')) {
            unset($data['sort_order']);
        } else {
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        }
        $activeTheme = $this->websiteBuilder->getActiveTheme();
        $data['frontend_theme_id'] = $activeTheme ? (int) $activeTheme->id : null;
        $data['module_name'] = $moduleName;
        $themeSlug = $activeTheme ? ($activeTheme->slug ?? 'modern') : 'modern';
        // home_sections niet meenemen in validate() om BadRequestException te voorkomen (ParameterBag verwacht array;
        // bij PUT kan de structuur anders zijn). We halen het veilig op en normaliseren zelf.
        $input = $this->getHomeSectionsInput($request);
        if (! is_array($input)) {
            $input = [];
        }
        if (config('app.debug')) {
            \Illuminate\Support\Facades\Log::debug('WebsitePage update: home_sections keys', [
                'keys' => array_keys($input),
                'has_cards' => isset($input['cards_ronde_hoeken']),
                'cards_items' => isset($input['cards_ronde_hoeken']['items']) ? count($input['cards_ronde_hoeken']['items']) : 0,
            ]);
        }
        // Als de request helemaal geen home_sections bevat (bijv. request bag leeg), bestaande niet overschrijven
        if (empty($input) && ! empty($website_page->home_sections)) {
            $input = $website_page->getHomeSections();
        }
        // Altijd _section_order uit de request laten voorgaan (staat bovenaan form, wordt door JS bijgewerkt bij verwijderen/sorteren).
        // Zo blijft een verwijderde sectie ook weg als home_sections door max_input_vars werd afgekapt of leeg was.
        $fallbackOrder = $request->input('_section_order');
        if ((! is_string($fallbackOrder) || trim($fallbackOrder) === '') && $request->getContent() !== '') {
            parse_str($request->getContent(), $parsed);
            $fallbackOrder = $parsed['_section_order'] ?? null;
        }
        if (is_string($fallbackOrder) && trim($fallbackOrder) !== '') {
            $input['section_order'] = trim($fallbackOrder);
        }
        $orderInput = $input['section_order'] ?? null;
        $orderIsEmpty = $orderInput === null || $orderInput === ''
            || (is_array($orderInput) && empty(array_filter($orderInput)))
            || (is_string($orderInput) && trim((string) $orderInput) === '');
        if ($orderIsEmpty && $website_page->page_type === 'home') {
            $existing = $website_page->getHomeSections();
            $input['section_order'] = $existing['section_order'] ?? WebsitePage::defaultHomeSectionsForTheme($themeSlug)['section_order'];
        }
        if ($orderIsEmpty && $website_page->page_type !== 'home') {
            $existing = $website_page->getHomeSections();
            $input['section_order'] = $existing['section_order'] ?? WebsitePage::defaultPageSectionsForNonHome($themeSlug)['section_order'];
        }
        // Tekstblok-content kan ontbreken in de request (bijv. max_input_vars of async WYSIWYG-sync): neem bestaande content over
        $existingSections = $website_page->getHomeSections();
        $orderForMerge = $input['section_order'] ?? [];
        if (is_string($orderForMerge) && $orderForMerge !== '') {
            $orderForMerge = array_values(array_filter(array_map('trim', explode(',', $orderForMerge))));
        }
        if (is_array($orderForMerge)) {
            foreach ($orderForMerge as $sk) {
                if (self::homeSectionBaseType($sk) !== 'text_block') {
                    continue;
                }
                $incomingContent = $input[$sk]['content'] ?? null;
                $incomingContent = is_string($incomingContent) ? $incomingContent : '';
                $existingContent = $existingSections[$sk]['content'] ?? null;
                $existingContent = is_string($existingContent) ? $existingContent : '';
                if ($existingContent !== '' && $incomingContent === '') {
                    if (! isset($input[$sk]) || ! is_array($input[$sk])) {
                        $input[$sk] = [];
                    }
                    $input[$sk]['content'] = $existingContent;
                }
            }
        }
        try {
            if ($data['page_type'] === 'home') {
                $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, false);
            } else {
                $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, true);
            }
            if ($connection !== null) {
                $data['frontend_theme_id'] = null;
            }
            $this->mergeCompanyIdIntoWebsitePageSaveData($data, $request, $connForSchema, $website_page);
            $website_page->update($data);

            // Footer op de site komt van de home-pagina. Sync vanuit een andere pagina alleen als die pagina
            // de footer zelf beheert (geen "Overnemen van Home"); anders zou een save van bv. Over ons de
            // home-footer overschrijven met lege/verouderde data uit het verborgen formulier.
            $homePage = $this->websiteBuilder->getHomePage();
            $footerInheritsFromHome = ! empty($data['home_sections']['footer']['inherit_from_home'] ?? false);
            if ($homePage && $homePage->id !== $website_page->id && $homePage->frontend_theme_id == $website_page->frontend_theme_id && ! $footerInheritsFromHome) {
                $current = is_array($homePage->home_sections) ? $homePage->home_sections : [];
                $current['footer'] = $data['home_sections']['footer'] ?? ($current['footer'] ?? []);
                $current['visibility'] = array_merge($current['visibility'] ?? [], $data['home_sections']['visibility'] ?? []);
                $homePage->update(['home_sections' => $current]);
            }
        } catch (\Throwable $e) {
            \Log::error('Website page update failed', [
                'page_id' => $website_page->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['home_sections' => 'Opslaan mislukt: '.$e->getMessage()]);
        }
        $editUrl = route('admin.website-pages.edit', $website_page);
        $separator = '?';
        if ($website_page->module_name) {
            $editUrl .= '?module='.rawurlencode($website_page->module_name);
            $separator = '&';
        }
        $editUrl .= $separator.'saved=1';
        $wizardQ = $this->websitePagesIndexQuery($request);
        if ($wizardQ !== []) {
            $editUrl .= '&'.http_build_query($wizardQ);
        }

        return redirect($editUrl)->with('success', 'Pagina bijgewerkt.');
    }

    /**
     * Of een input-key expliciet in de request zit (ook waarde "0").
     * Laravel's Request::has() kan "0" als afwezig behandelen; query/request-bag has() gebruikt array_key_exists.
     */
    private function requestHasInput(Request $request, string $key): bool
    {
        if ($request->query->has($key) || $request->request->has($key)) {
            return true;
        }

        // Fallback: merged input (zeldzaam edge-case waarbij de waarde niet in query/request-bag zit)
        return array_key_exists($key, $request->all());
    }

    /**
     * Haal home_sections uit de request zonder ParameterBag::all($key) aan te roepen
     * (die gooit BadRequestException als de waarde geen array is).
     * Ondersteunt POST (form) en fallback parse van body bij lege request bag.
     */
    private function getHomeSectionsInput(Request $request): array
    {
        // Laravel's input() parsed ook PUT/PATCH body (method spoofing + FormData/multipart)
        $raw = $request->input('home_sections');
        if (is_array($raw)) {
            $input = $raw;
        } else {
            try {
                $params = $request->request->all();
                $raw = $params['home_sections'] ?? null;
            } catch (\Throwable $e) {
                $raw = null;
            }
            $input = is_array($raw) ? $raw : [];
            if ($input === [] && $request->getContent() !== '') {
                parse_str($request->getContent(), $parsed);
                $raw = $parsed['home_sections'] ?? null;
                $input = is_array($raw) ? $raw : [];
            }
        }
        // Visibility footer: fallback-JSON bovenaan formulier is leidend (voorkomt verlies door max_input_vars)
        $visibilityFooterFallback = $request->input('_visibility_footer_fallback');
        if ((! is_string($visibilityFooterFallback) || $visibilityFooterFallback === '') && $request->getContent() !== '') {
            parse_str($request->getContent(), $parsedVisibility);
            $fromBody = $parsedVisibility['_visibility_footer_fallback'] ?? null;
            if (is_string($fromBody) && $fromBody !== '') {
                $visibilityFooterFallback = $fromBody;
            }
        }
        if (is_string($visibilityFooterFallback) && $visibilityFooterFallback !== '') {
            $decoded = json_decode($visibilityFooterFallback, true);
            if (is_array($decoded)) {
                if (! isset($input['visibility']) || ! is_array($input['visibility'])) {
                    $input['visibility'] = [];
                }
                foreach ($decoded as $key => $value) {
                    if (is_string($key) && str_starts_with($key, 'footer_')) {
                        $input['visibility'][$key] = $value;
                    }
                }
            }
        }
        // Section order kan ontbreken bij grote body (max_input_vars): vul aan uit fallback-veld bovenaan formulier
        $orderFromFallback = $request->input('_section_order');
        if (is_string($orderFromFallback) && trim($orderFromFallback) !== '') {
            $currentOrder = $input['section_order'] ?? null;
            $orderEmpty = $currentOrder === null || $currentOrder === ''
                || (is_array($currentOrder) && empty(array_filter($currentOrder)))
                || (is_string($currentOrder) && trim($currentOrder) === '');
            if ($orderEmpty) {
                $input['section_order'] = trim($orderFromFallback);
            }
        }
        // email_template template_id kan ontbreken (custom select/JS of max_input_vars): vul aan uit fallback-velden
        $sectionOrder = $input['section_order'] ?? [];
        if (is_string($sectionOrder) && $sectionOrder !== '') {
            $sectionOrder = array_values(array_filter(array_map('trim', explode(',', $sectionOrder))));
        }
        if (is_array($sectionOrder)) {
            foreach ($sectionOrder as $sk) {
                if (! is_string($sk)) {
                    continue;
                }
                $base = self::homeSectionBaseType($sk);
                if ($base !== 'email_template') {
                    continue;
                }
                $tid = $request->input('_email_template_tid_'.$sk);
                if ($tid !== null && $tid !== '' && is_numeric($tid)) {
                    if (! isset($input[$sk]) || ! is_array($input[$sk])) {
                        $input[$sk] = [];
                    }
                    // Altijd fallback gebruiken wanneer aanwezig (select-waarde kan bij grote forms ontbreken)
                    $input[$sk]['template_id'] = (int) $tid;
                }
            }
        }

        return $input;
    }

    private const HOME_SECTION_BASE_TYPES = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block'];

    /**
     * Actieve e-mailtemplates voor website-formulieren (bijv. sectie email_template).
     * Hoofdlijst uit standaard-DB (tenant-filter). Optioneel: templates uit $includeTemplateIds uit $includeFromConnection (module-DB) toevoegen zodat opgeslagen keuze zichtbaar blijft.
     *
     * @param  array<int>  $includeTemplateIds  Ids die altijd in de lijst moeten (bijv. reeds gekozen op pagina)
     * @param  string|null  $includeFromConnection  Bij module-pagina: connection waarop includeTemplateIds opgehaald worden (module-DB)
     */
    private function getEmailTemplatesForWebsiteForm(?string $connection = null, array $includeTemplateIds = [], ?string $includeFromConnection = null): \Illuminate\Support\Collection
    {
        $tenantId = auth()->user()->hasRole('super-admin')
            ? session('selected_tenant')
            : auth()->user()->company_id;

        $query = $connection
            ? \App\Models\EmailTemplate::on($connection)->where('is_active', true)
            : \App\Models\EmailTemplate::where('is_active', true);
        if ($tenantId !== null && $tenantId !== '') {
            $query->where(function ($q) use ($tenantId) {
                $q->whereNull('company_id')->orWhere('company_id', (int) $tenantId);
            });
        }
        $templates = $query->orderBy('name')->get(['id', 'name', 'type']);
        $includeTemplateIds = array_filter(array_map('intval', $includeTemplateIds));
        if ($includeTemplateIds !== []) {
            $existingIds = $templates->pluck('id')->all();
            $missingIds = array_diff($includeTemplateIds, $existingIds);
            if ($missingIds !== []) {
                $extraConn = $includeFromConnection ?? $connection;
                $extraQuery = $extraConn
                    ? \App\Models\EmailTemplate::on($extraConn)->where('is_active', true)->whereIn('id', $missingIds)
                    : \App\Models\EmailTemplate::where('is_active', true)->whereIn('id', $missingIds);
                $extra = $extraQuery->get(['id', 'name', 'type']);
                $templates = $templates->merge($extra)->sortBy('name')->values();
                $missingIds = array_diff($includeTemplateIds, $templates->pluck('id')->all());
                if ($missingIds !== []) {
                    $defaultExtra = \App\Models\EmailTemplate::where('is_active', true)->whereIn('id', $missingIds)->get(['id', 'name', 'type']);
                    $templates = $templates->merge($defaultExtra)->sortBy('name')->values();
                }
            }
        }

        return $templates;
    }

    private static function homeSectionBaseType(string $sectionKey): ?string
    {
        if (in_array($sectionKey, self::HOME_SECTION_BASE_TYPES, true)) {
            return $sectionKey;
        }
        $base = preg_replace('/_\d+$/', '', $sectionKey);

        return in_array($base, self::HOME_SECTION_BASE_TYPES, true) ? $base : null;
    }

    private static function homeSectionOrderValid(string $key): bool
    {
        return self::homeSectionBaseType($key) !== null || FrontendComponentService::isComponentKey($key);
    }

    /** Normaliseer items voor component:taxi.tarieven (rate_type, title, image, card-opties per card). */
    private function normalizeNexaTaxiTarievenSection(array $raw): array
    {
        $items = $raw['items'] ?? [];
        if (! is_array($items)) {
            $items = [];
        }
        $cardSizes = ['small', 'normal', 'large', 'max', 'total_width'];
        $fontStyles = ['normal', 'bold', 'italic'];
        $textAligns = ['left', 'center', 'right'];
        $fontFamilies = ['', 'sans-serif', 'serif', 'monospace', 'Inter', 'Georgia'];
        $fontSizes = array_merge([''], array_map(fn ($px) => $px.'px', range(10, 40, 2)));
        $sectionTitle = isset($raw['title']) ? trim((string) $raw['title']) : 'Onze tarieven';
        if ($sectionTitle === '') {
            $sectionTitle = 'Onze tarieven';
        }
        $sectionTitleFontSize = isset($raw['title_font_size']) ? trim((string) $raw['title_font_size']) : '';
        $sectionTitleFontSize = in_array($sectionTitleFontSize, $fontSizes, true) ? $sectionTitleFontSize : '';
        $sectionTitleFontStyle = isset($raw['title_font_style']) && in_array($raw['title_font_style'], $fontStyles, true)
            ? $raw['title_font_style'] : 'normal';
        $sectionTitleAlign = isset($raw['title_align']) && in_array($raw['title_align'], $textAligns, true)
            ? $raw['title_align'] : 'left';
        $out = [];
        foreach (array_values($items) as $row) {
            $rateType = isset($row['rate_type']) && in_array($row['rate_type'], ['1-4', '5-8', 'overige_kosten'], true) ? $row['rate_type'] : '1-4';
            $cleaning = null;
            if (isset($row['cleaning_costs']) && $row['cleaning_costs'] !== '' && is_numeric($row['cleaning_costs'])) {
                $v = (float) $row['cleaning_costs'];
                $cleaning = $v >= 0 ? $v : null;
            }
            $vehicleId = isset($row['vehicle_id']) && $row['vehicle_id'] !== '' && is_numeric($row['vehicle_id'])
                ? (int) $row['vehicle_id'] : null;
            $imageUrl = isset($row['image_url']) && trim((string) $row['image_url']) !== '' ? trim((string) $row['image_url']) : null;
            if ($vehicleId !== null) {
                $imageUrl = null;
            }
            $cardSize = isset($row['card_size']) && in_array($row['card_size'], $cardSizes, true) ? $row['card_size'] : 'normal';
            $fontStyle = isset($row['font_style']) && in_array($row['font_style'], $fontStyles, true) ? $row['font_style'] : 'normal';
            $textAlign = isset($row['text_align']) && in_array($row['text_align'], $textAligns, true) ? $row['text_align'] : 'left';
            $titleFontFamily = isset($row['title_font_family']) ? trim((string) $row['title_font_family']) : '';
            $titleFontFamily = in_array($titleFontFamily, $fontFamilies, true) ? $titleFontFamily : '';
            $titleFontSize = isset($row['title_font_size']) ? trim((string) $row['title_font_size']) : '';
            $titleFontSize = in_array($titleFontSize, $fontSizes, true) ? $titleFontSize : '';
            $titleFontStyle = isset($row['title_font_style']) && in_array($row['title_font_style'], $fontStyles, true)
                ? $row['title_font_style'] : $fontStyle;
            $titleAlign = isset($row['title_align']) && in_array($row['title_align'], $textAligns, true)
                ? $row['title_align'] : $textAlign;
            $labelFontSize = isset($row['label_font_size']) ? trim((string) $row['label_font_size']) : '';
            $labelFontSize = in_array($labelFontSize, $fontSizes, true) ? $labelFontSize : '';
            $valueFontSize = isset($row['value_font_size']) ? trim((string) $row['value_font_size']) : '';
            $valueFontSize = in_array($valueFontSize, $fontSizes, true) ? $valueFontSize : '';
            $imagePadding = isset($row['image_padding']) ? max(0, min(30, (int) $row['image_padding'])) : 2;
            $imagePadding = (int) (round($imagePadding / 2) * 2);
            $out[] = [
                'rate_type' => $rateType,
                'title' => isset($row['title']) ? trim((string) $row['title']) : '',
                'cleaning_costs' => $cleaning,
                'vehicle_id' => $vehicleId,
                'image_url' => $imageUrl,
                'card_size' => $cardSize,
                'font_style' => $fontStyle,
                'title_font_family' => $titleFontFamily,
                'title_font_size' => $titleFontSize,
                'title_font_style' => $titleFontStyle,
                'title_align' => $titleAlign,
                'label_font_size' => $labelFontSize,
                'value_font_size' => $valueFontSize,
                'text_align' => $textAlign,
                'image_padding' => $imagePadding,
                'image_bg_color' => isset($row['image_bg_color']) ? trim((string) $row['image_bg_color']) : '',
                'text_color' => isset($row['text_color']) ? trim((string) $row['text_color']) : '',
            ];
        }
        if (empty($out)) {
            $out = [
                ['rate_type' => '1-4', 'title' => 't/m 4 personen', 'cleaning_costs' => null, 'vehicle_id' => null, 'image_url' => null, 'card_size' => 'normal', 'font_style' => 'normal', 'title_font_family' => '', 'title_font_size' => '', 'title_font_style' => 'normal', 'title_align' => 'left', 'label_font_size' => '', 'value_font_size' => '', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => ''],
                ['rate_type' => '5-8', 'title' => '5 t/m 8 personen', 'cleaning_costs' => null, 'vehicle_id' => null, 'image_url' => null, 'card_size' => 'normal', 'font_style' => 'normal', 'title_font_family' => '', 'title_font_size' => '', 'title_font_style' => 'normal', 'title_align' => 'left', 'label_font_size' => '', 'value_font_size' => '', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => ''],
            ];
        }
        $priceAnimation = true;
        if (array_key_exists('price_animation', $raw)) {
            $priceAnimation = filter_var($raw['price_animation'], FILTER_VALIDATE_BOOLEAN);
        }
        $imageFadeDuration = 1200;
        if (array_key_exists('image_fade_duration', $raw) && is_numeric($raw['image_fade_duration'])) {
            $imageFadeDuration = max(300, min(5000, (int) $raw['image_fade_duration']));
        }

        return [
            'title' => $sectionTitle,
            'title_font_size' => $sectionTitleFontSize,
            'title_font_style' => $sectionTitleFontStyle,
            'title_align' => $sectionTitleAlign,
            'price_animation' => $priceAnimation,
            'image_fade_duration' => $imageFadeDuration,
            'items' => $out,
        ];
    }

    private function normalizeNexaTaxiBoekingsmoduleSection(array $raw): array
    {
        return app(NexaTaxiBookingPricingService::class)->mergeSectionConfig($raw);
    }

    /**
     * Normaliseer home_sections uit request; ondersteunt dynamische sectie-keys (hero_2, features_2, etc.).
     * Bij themeSlug wordt defaultHomeSectionsForTheme (of bij forNonHome: defaultPageSectionsForNonHome) gebruikt.
     */
    private function normalizeHomeSections(array $input, ?string $themeSlug = null, bool $forNonHome = false): array
    {
        if ($forNonHome && $themeSlug) {
            $defaults = WebsitePage::defaultPageSectionsForNonHome($themeSlug);
        } else {
            $defaults = $themeSlug ? WebsitePage::defaultHomeSectionsForTheme($themeSlug) : WebsitePage::defaultHomeSections();
        }

        $sectionOrder = $defaults['section_order'];
        $orderInput = $input['section_order'] ?? null;
        if (is_array($orderInput)) {
            $sectionOrder = array_values(array_filter($orderInput, fn ($k) => is_string($k) && self::homeSectionOrderValid($k)));
        } elseif (is_string($orderInput) && $orderInput !== '') {
            $sectionOrder = array_values(array_filter(array_map('trim', explode(',', $orderInput)), fn ($k) => self::homeSectionOrderValid($k)));
        }
        // Gebruik alleen de door de gebruiker opgegeven volgorde; niet mergen met defaults, zodat verwijderde secties weg blijven.
        $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));
        $legacyTaxiComponentKeys = [
            'component:taxiroyaal.tarieven' => 'component:taxi.tarieven',
            'component:taxiroyaal.boekingsmodule' => 'component:taxi.boekingsmodule',
        ];
        $sectionOrder = array_map(static fn ($k) => $legacyTaxiComponentKeys[$k] ?? $k, $sectionOrder);
        $sectionOrder = array_values(array_unique($sectionOrder, SORT_REGULAR));

        $sections = [];
        foreach ($sectionOrder as $sectionKey) {
            if (FrontendComponentService::isComponentKey($sectionKey)) {
                if ($sectionKey === 'component:taxi.tarieven') {
                    $sections[$sectionKey] = $this->normalizeNexaTaxiTarievenSection(
                        $input[$sectionKey] ?? $input['component:taxiroyaal.tarieven'] ?? []
                    );
                } elseif ($sectionKey === 'component:taxi.boekingsmodule') {
                    $sections[$sectionKey] = $this->normalizeNexaTaxiBoekingsmoduleSection(
                        $input[$sectionKey] ?? $input['component:taxiroyaal.boekingsmodule'] ?? []
                    );
                } else {
                    $sections[$sectionKey] = [];
                }

                continue;
            }
            $baseType = self::homeSectionBaseType($sectionKey);
            if ($baseType === null) {
                continue;
            }
            $sections[$sectionKey] = $this->normalizeOneHomeSection($input, $sectionKey, $baseType, $defaults);
        }

        $footerInput = $input['footer'] ?? [];
        $quickLinks = [];
        if (! empty($footerInput['quick_links']) && is_array($footerInput['quick_links'])) {
            foreach (array_values($footerInput['quick_links']) as $row) {
                if (is_array($row) && trim((string) ($row['label'] ?? '')) !== '') {
                    $quickLinks[] = [
                        'label' => trim((string) ($row['label'] ?? '')),
                        'url' => trim((string) ($row['url'] ?? '')),
                    ];
                }
            }
        }
        $supportLinks = [];
        if (! empty($footerInput['support_links']) && is_array($footerInput['support_links'])) {
            foreach (array_values($footerInput['support_links']) as $row) {
                if (is_array($row) && trim((string) ($row['label'] ?? '')) !== '') {
                    $supportLinks[] = [
                        'label' => trim((string) ($row['label'] ?? '')),
                        'url' => trim((string) ($row['url'] ?? '')),
                    ];
                }
            }
        }
        $logoHeight = isset($footerInput['logo_height']) ? (int) $footerInput['logo_height'] : null;
        if ($logoHeight !== null && ($logoHeight < 12 || $logoHeight > 30)) {
            $logoHeight = 16;
        }
        $footer = array_filter([
            'tagline' => isset($footerInput['tagline']) ? trim((string) $footerInput['tagline']) : null,
            'logo_url' => isset($footerInput['logo_url']) ? trim((string) $footerInput['logo_url']) : null,
            'logo_alt' => isset($footerInput['logo_alt']) ? trim((string) $footerInput['logo_alt']) : null,
            'logo_height' => $logoHeight,
            'quick_links_title' => isset($footerInput['quick_links_title']) ? trim((string) $footerInput['quick_links_title']) : null,
            'quick_links' => $quickLinks ?: null,
            'support_links_title' => isset($footerInput['support_links_title']) ? trim((string) $footerInput['support_links_title']) : null,
            'support_links' => $supportLinks ?: null,
        ], fn ($v) => $v !== null && $v !== '');
        if (isset($footer['quick_links']) && $footer['quick_links'] === null) {
            unset($footer['quick_links']);
        }
        if (isset($footer['support_links']) && $footer['support_links'] === null) {
            unset($footer['support_links']);
        }
        $footer = array_merge($defaults['footer'], $footer);
        $footer['map_postcode'] = isset($footerInput['map_postcode']) ? trim((string) $footerInput['map_postcode']) : ($defaults['footer']['map_postcode'] ?? '');
        $footer['map_huisnummer'] = isset($footerInput['map_huisnummer']) ? trim((string) $footerInput['map_huisnummer']) : ($defaults['footer']['map_huisnummer'] ?? '');
        $footer['map_street'] = isset($footerInput['map_street']) ? trim((string) $footerInput['map_street']) : ($defaults['footer']['map_street'] ?? '');
        $footer['map_city'] = isset($footerInput['map_city']) ? trim((string) $footerInput['map_city']) : ($defaults['footer']['map_city'] ?? '');
        $footer['map_city_only'] = ! empty($footerInput['map_city_only']);
        $footer['map_lat'] = isset($footerInput['map_lat']) && $footerInput['map_lat'] !== '' ? (is_numeric($footerInput['map_lat']) ? (float) $footerInput['map_lat'] : null) : null;
        $footer['map_lng'] = isset($footerInput['map_lng']) && $footerInput['map_lng'] !== '' ? (is_numeric($footerInput['map_lng']) ? (float) $footerInput['map_lng'] : null) : null;
        if (! empty($footer['map_city_only'])) {
            // Stad-modus: adres wordt op basis van alleen plaats bepaald.
            $footer['map_postcode'] = '';
            $footer['map_huisnummer'] = '';
            $footer['map_street'] = '';
            $footer['map_lat'] = null;
            $footer['map_lng'] = null;
        }
        $footer['map_size'] = isset($footerInput['map_size']) && in_array($footerInput['map_size'], ['small', 'normal', 'large'], true) ? $footerInput['map_size'] : ($defaults['footer']['map_size'] ?? 'normal');
        $mapZoom = isset($footerInput['map_zoom']) && is_numeric($footerInput['map_zoom']) ? (int) $footerInput['map_zoom'] : (int) ($defaults['footer']['map_zoom'] ?? 17);
        $footer['map_zoom'] = $mapZoom >= 1 && $mapZoom <= 20 ? $mapZoom : 17;
        $footer['map_show_address_balloon'] = ! empty($footerInput['map_show_address_balloon']);
        $footer['logo_align'] = isset($footerInput['logo_align']) && in_array($footerInput['logo_align'], ['left', 'center', 'right'], true) ? $footerInput['logo_align'] : ($defaults['footer']['logo_align'] ?? 'left');
        $footer['quick_links_align'] = isset($footerInput['quick_links_align']) && in_array($footerInput['quick_links_align'], ['left', 'center', 'right'], true) ? $footerInput['quick_links_align'] : ($defaults['footer']['quick_links_align'] ?? 'left');
        $footer['support_links_align'] = isset($footerInput['support_links_align']) && in_array($footerInput['support_links_align'], ['left', 'center', 'right'], true) ? $footerInput['support_links_align'] : ($defaults['footer']['support_links_align'] ?? 'left');
        foreach (['social_facebook', 'social_instagram', 'social_x', 'social_linkedin', 'social_youtube', 'social_tiktok'] as $socialKey) {
            $footer[$socialKey] = isset($footerInput[$socialKey]) ? trim((string) $footerInput[$socialKey]) : '';
        }
        $footer['inherit_from_home'] = ! empty($footerInput['inherit_from_home']);

        $visibilityInput = $input['visibility'] ?? [];
        if (! is_array($visibilityInput)) {
            $visibilityInput = [];
        }
        $visibilityOverlay = [];
        foreach (['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken', 'featured_services', 'email_template', 'text_block', 'footer'] as $k) {
            if (array_key_exists($k, $visibilityInput)) {
                $visibilityOverlay[$k] = ! empty($visibilityInput[$k]);
            }
        }
        $visibility = array_merge($defaults['visibility'], $visibilityOverlay);
        foreach (array_keys($visibilityInput) as $key) {
            if (is_string($key) && $key !== '') {
                if (preg_match('/^(hero|stats|why_nexa|features|cta|cards_ronde_hoeken|text_block)(_[a-z0-9_]+)?$/i', $key)) {
                    $visibility[$key] = ! empty($visibilityInput[$key]);
                }
                if (preg_match('/^footer_[a-z0-9_]+$/i', $key)) {
                    $visibility[$key] = ! empty($visibilityInput[$key]);
                }
            }
        }
        // Expliciet alle footer_* visibility-keys uit de request overnemen (o.a. footer_quick_links, footer_support_links, footer_social)
        foreach ($visibilityInput as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'footer_')) {
                $visibility[$key] = ! empty($value);
            }
        }
        // Hoofd-zichtbaarheid per sectie in section_order (o.a. component:taxi.* en andere dynamische keys)
        foreach ($sectionOrder as $sk) {
            if (! is_string($sk) || $sk === '') {
                continue;
            }
            if (array_key_exists($sk, $visibilityInput)) {
                $visibility[$sk] = ! empty($visibilityInput[$sk]);
            }
        }

        $adminCollapsed = $input['admin_collapsed'] ?? [];
        if (is_string($adminCollapsed) && $adminCollapsed !== '') {
            $adminCollapsed = array_values(array_filter(array_map('trim', explode(',', $adminCollapsed))));
        } elseif (is_array($adminCollapsed)) {
            $adminCollapsed = array_values(array_filter($adminCollapsed, fn ($k) => is_string($k) && $k !== ''));
        } else {
            $adminCollapsed = [];
        }

        return array_merge($sections, [
            'footer' => $footer,
            'copyright' => is_string($input['copyright'] ?? null) ? trim($input['copyright']) : ($defaults['copyright'] ?? ''),
            'section_order' => $sectionOrder,
            'visibility' => $visibility,
            'admin_collapsed' => $adminCollapsed,
        ]);
    }

    private function normalizeOneHomeSection(array $input, string $sectionKey, string $baseType, array $defaults): array
    {
        $raw = $input[$sectionKey] ?? [];
        if (! is_array($raw)) {
            $raw = [];
        }
        switch ($baseType) {
            case 'hero':
                $data = array_merge($defaults['hero'], $raw);
                $data = $this->sanitizeButtonColors($data);
                $data['overlay'] = ! empty($raw['overlay']);
                // Behoud hero-afbeeldingen (atom-v2) ook als leeg, zodat "geen custom" = thema-default
                $keepEmptyKeys = ['overlay', 'background_image_url', 'author_image_url'];

                return array_filter($data, fn ($v, $k) => in_array($k, $keepEmptyKeys, true) ? true : $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH);
            case 'stats':
                $stats = [];
                if (! empty($raw) && is_array($raw)) {
                    foreach ([0, 1, 2, 3] as $i) {
                        $row = $raw[$i] ?? null;
                        if (is_array($row)) {
                            $vc = isset($row['value_color']) && is_string($row['value_color']) ? trim($row['value_color']) : '';
                            $vc = $vc !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $vc) ? $vc : '';
                            $vsRaw = $row['value_size'] ?? '22';
                            $vs = in_array($vsRaw, ['small', 'medium', 'large'], true) ? $vsRaw : (in_array((int) $vsRaw, range(10, 30, 2), true) ? (string) (int) $vsRaw : '22');
                            $lsRaw = $row['label_size'] ?? '16';
                            $ls = in_array($lsRaw, ['small', 'medium', 'large'], true) ? $lsRaw : (in_array((int) $lsRaw, range(10, 30, 2), true) ? (string) (int) $lsRaw : '16');
                            $stats[] = [
                                'value' => trim((string) ($row['value'] ?? '')),
                                'label' => trim((string) ($row['label'] ?? '')),
                                'value_color' => $vc,
                                'value_size' => $vs,
                                'label_size' => $ls,
                            ];
                        } else {
                            $defItems = $defaults['stats']['items'] ?? $defaults['stats'];
                            $di = is_array($defItems) && isset($defItems[$i]) && is_array($defItems[$i]) ? $defItems[$i] : null;
                            $stats[] = $di ? ['value' => $di['value'] ?? '', 'label' => $di['label'] ?? '', 'value_color' => $di['value_color'] ?? '', 'value_size' => $di['value_size'] ?? '22', 'label_size' => $di['label_size'] ?? '16'] : ['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'];
                        }
                    }
                }
                $defStats = $defaults['stats']['items'] ?? $defaults['stats'];
                if (! is_array($defStats)) {
                    $defStats = [];
                }
                while (count($stats) < 4) {
                    $di = $defStats[count($stats)] ?? null;
                    $stats[] = is_array($di) ? ['value' => $di['value'] ?? '', 'label' => $di['label'] ?? '', 'value_color' => $di['value_color'] ?? '', 'value_size' => $di['value_size'] ?? '22', 'label_size' => $di['label_size'] ?? '16'] : ['value' => '', 'label' => '', 'value_color' => '', 'value_size' => '22', 'label_size' => '16'];
                }
                $bg = isset($raw['background']) && is_string($raw['background']) ? trim($raw['background']) : '';
                $bg = $bg !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $bg) ? $bg : '';
                $bgImage = isset($raw['background_image']) && is_string($raw['background_image']) ? trim($raw['background_image']) : '';

                return [
                    'items' => array_values($stats),
                    'background' => $bg,
                    'background_image' => $bgImage,
                ];
            case 'why_nexa':
                return array_filter(array_merge($defaults['why_nexa'], $raw));
            case 'features':
                $items = [];
                if (! empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $i => $row) {
                        if (is_array($row)) {
                            $items[] = [
                                'title' => $row['title'] ?? '',
                                'description' => $row['description'] ?? '',
                                'icon' => $row['icon'] ?? ($i === 0 ? 'light-bulb' : 'bolt'),
                                'icon_size' => in_array($row['icon_size'] ?? '', ['small', 'medium', 'large'], true) ? $row['icon_size'] : 'medium',
                                'icon_align' => in_array($row['icon_align'] ?? '', ['left', 'center', 'right'], true) ? $row['icon_align'] : 'center',
                            ];
                        }
                    }
                }
                $defItems = $defaults['features']['items'] ?? [];
                $out = [
                    'section_title' => $raw['section_title'] ?? ($defaults['features']['section_title'] ?? 'Wat Wij Bieden'),
                    'items' => $items ?: $defItems,
                ];
                if (array_key_exists('illustration_url', $raw)) {
                    $out['illustration_url'] = trim((string) $raw['illustration_url']);
                }

                return $out;
            case 'cta':
                $data = array_merge($defaults['cta'], $raw);
                $data = $this->sanitizeButtonColors($data);
                // Behoud background_image_url ook als leeg (Atom-v2 CTA achtergrond)
                $keepEmptyCta = ['background_image_url'];

                return array_filter($data, fn ($v, $k) => in_array($k, $keepEmptyCta, true) ? true : $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH);
            case 'carousel':
                $items = [];
                if (! empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $row) {
                        if (is_array($row) && ! empty($row['uuid'])) {
                            $items[] = [
                                'uuid' => (string) $row['uuid'],
                                'alt' => isset($row['alt']) ? trim((string) $row['alt']) : '',
                            ];
                        }
                    }
                }

                return ['items' => $items];
            case 'cards_ronde_hoeken':
                $items = [];
                if (! empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $row) {
                        if (is_array($row)) {
                            $fontSize = isset($row['font_size']) ? (int) $row['font_size'] : 14;
                            $fontSize = max(10, min(24, $fontSize));
                            $fontStyle = isset($row['font_style']) && in_array($row['font_style'], ['normal', 'bold', 'italic'], true) ? $row['font_style'] : 'normal';
                            $cardSize = isset($row['card_size']) && in_array($row['card_size'], ['small', 'normal', 'large', 'xlarge', 'max', 'total_width'], true) ? $row['card_size'] : 'normal';
                            $textAlign = isset($row['text_align']) && in_array($row['text_align'], ['left', 'center', 'right'], true) ? $row['text_align'] : 'left';
                            $imagePadding = isset($row['image_padding']) ? max(0, min(30, (int) $row['image_padding'])) : 2;
                            $imagePadding = (int) (round($imagePadding / 2) * 2);
                            $imageBgColor = isset($row['image_bg_color']) ? trim((string) $row['image_bg_color']) : '';
                            if ($imageBgColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $imageBgColor)) {
                                $imageBgColor = '';
                            }
                            $textColor = isset($row['text_color']) ? trim((string) $row['text_color']) : '';
                            if ($textColor !== '' && ! preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $textColor)) {
                                $textColor = '';
                            }
                            $items[] = [
                                'image_url' => isset($row['image_url']) ? trim((string) $row['image_url']) : '',
                                'text' => isset($row['text']) ? trim((string) $row['text']) : '',
                                'font_size' => $fontSize,
                                'font_style' => $fontStyle,
                                'card_size' => $cardSize,
                                'text_align' => $textAlign,
                                'image_padding' => $imagePadding,
                                'image_bg_color' => $imageBgColor,
                                'text_color' => $textColor,
                            ];
                        }
                    }
                }
                $defItems = $defaults['cards_ronde_hoeken']['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left', 'image_padding' => 2, 'image_bg_color' => '', 'text_color' => '']];
                $cardsPerRow = isset($raw['cards_per_row']) ? (int) $raw['cards_per_row'] : ($defaults['cards_ronde_hoeken']['cards_per_row'] ?? 4);
                $cardsPerRow = in_array($cardsPerRow, [1, 2, 3, 4, 5, 6], true) ? $cardsPerRow : 4;

                return ['cards_per_row' => $cardsPerRow, 'items' => $items ?: $defItems];
            case 'featured_services':
                $items = [];
                if (! empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $row) {
                        if (is_array($row)) {
                            $iconColor = isset($row['icon_color']) && is_string($row['icon_color']) ? trim($row['icon_color']) : '';
                            $iconColor = $iconColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $iconColor) ? $iconColor : '';
                            $items[] = [
                                'icon' => trim((string) ($row['icon'] ?? 'light-bulb')),
                                'icon_color' => $iconColor,
                                'title' => trim((string) ($row['title'] ?? '')),
                                'description' => trim((string) ($row['description'] ?? '')),
                            ];
                        }
                    }
                }
                $defItems = $defaults['featured_services']['items'] ?? [['icon' => 'light-bulb', 'title' => '', 'description' => '']];
                $blocksPerRow = isset($raw['blocks_per_row']) ? (int) $raw['blocks_per_row'] : ($defaults['featured_services']['blocks_per_row'] ?? 3);
                $blocksPerRow = in_array($blocksPerRow, [2, 3, 4], true) ? $blocksPerRow : 3;
                $blockSize = isset($raw['block_size']) && in_array($raw['block_size'], ['small', 'medium', 'large', 'full'], true) ? $raw['block_size'] : ($defaults['featured_services']['block_size'] ?? 'medium');
                $blockAlign = isset($raw['block_align']) && in_array($raw['block_align'], ['left', 'center', 'right'], true) ? $raw['block_align'] : ($defaults['featured_services']['block_align'] ?? 'center');
                $iconSize = isset($raw['icon_size']) && in_array($raw['icon_size'], ['small', 'medium', 'large'], true) ? $raw['icon_size'] : ($defaults['featured_services']['icon_size'] ?? 'medium');
                $iconAlign = isset($raw['icon_align']) && in_array($raw['icon_align'], ['top', 'center', 'bottom'], true) ? $raw['icon_align'] : ($defaults['featured_services']['icon_align'] ?? 'center');
                $cardBgColor = isset($raw['card_bg_color']) && is_string($raw['card_bg_color']) ? trim($raw['card_bg_color']) : ($defaults['featured_services']['card_bg_color'] ?? '');
                $cardBgColor = $cardBgColor !== '' && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $cardBgColor) ? $cardBgColor : '';
                $animationSpeed = isset($raw['animation_speed']) && in_array($raw['animation_speed'], ['fast', 'normal', 'slow', 'slower'], true) ? $raw['animation_speed'] : ($defaults['featured_services']['animation_speed'] ?? 'slow');

                return [
                    'title' => trim((string) ($raw['title'] ?? ($defaults['featured_services']['title'] ?? 'Diensten'))),
                    'subtitle' => trim((string) ($raw['subtitle'] ?? ($defaults['featured_services']['subtitle'] ?? ''))),
                    'blocks_per_row' => $blocksPerRow,
                    'block_size' => $blockSize,
                    'block_align' => $blockAlign,
                    'icon_size' => $iconSize,
                    'icon_align' => $iconAlign,
                    'card_bg_color' => $cardBgColor,
                    'animation_speed' => $animationSpeed,
                    'items' => $items ?: $defItems,
                ];
            case 'email_template':
                $templateId = isset($raw['template_id']) && $raw['template_id'] !== '' && is_numeric($raw['template_id'])
                    ? (int) $raw['template_id']
                    : null;

                return [
                    'title' => trim((string) ($raw['title'] ?? ($defaults['email_template']['title'] ?? 'Informatie aanvragen'))),
                    'template_id' => $templateId,
                ];
            case 'text_block':
                $alignment = isset($raw['alignment']) && in_array($raw['alignment'], ['left', 'center', 'right', 'full'], true)
                    ? $raw['alignment']
                    : ($defaults['text_block']['alignment'] ?? 'left');
                $sideKey = isset($raw['side_component_key']) && is_string($raw['side_component_key']) ? trim($raw['side_component_key']) : '';
                $content = array_key_exists('content', $raw)
                    ? (is_string($raw['content']) ? $raw['content'] : '')
                    : (string) ($defaults['text_block']['content'] ?? '');
                $imageUrl = array_key_exists('image_url', $raw) && is_string($raw['image_url']) ? trim($raw['image_url']) : '';
                $widthPercent = isset($raw['width_percent']) && is_numeric($raw['width_percent'])
                    ? (int) $raw['width_percent']
                    : (int) ($defaults['text_block']['width_percent'] ?? 100);
                $widthPercent = max(30, min(100, $widthPercent));

                return [
                    'content' => $content,
                    'alignment' => $alignment,
                    'side_component_key' => $sideKey,
                    'image_url' => $imageUrl,
                    'width_percent' => $widthPercent,
                ];
            default:
                return [];
        }
    }

    /**
     * Sanitize hex color fields for hero/cta button colors (cta_primary_bg, cta_primary_color, etc.).
     */
    private function sanitizeButtonColors(array $data): array
    {
        $colorKeys = ['cta_primary_bg', 'cta_primary_border', 'cta_primary_text_color', 'cta_secondary_bg', 'cta_secondary_border', 'cta_secondary_text_color'];
        foreach ($colorKeys as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $v = trim($data[$key]);
                $v = preg_match('/^#?[0-9a-fA-F]{3,6}$/', $v) ? (str_starts_with($v, '#') ? $v : '#'.$v) : '';
                if (strlen($v) === 4) {
                    $v = '#'.$v[1].$v[1].$v[2].$v[2].$v[3].$v[3];
                }
                $data[$key] = $v;
            }
        }

        return $data;
    }

    /**
     * Upload footer logo via AJAX (voor home-secties footer).
     */
    public function uploadFooterLogo(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'logo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'logo.required' => 'Selecteer een logo bestand.',
            'logo.mimes' => 'Alleen JPEG, PNG, JPG, GIF en SVG zijn toegestaan.',
            'logo.max' => 'Het bestand mag maximaal 2MB groot zijn.',
        ]);

        $logoFile = $request->file('logo');
        $moduleName = $this->moduleContext->getModuleNameFromRequest($request);
        $prefix = $moduleName ? $this->moduleContext->getUploadPathPrefix($moduleName) : '';
        $dir = $prefix.'website';
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $logoFile->store($dir, 'public');
        // Publieke URL (werkt ook voor niet-ingelogde bezoekers)
        $url = $this->websiteBuilder->publicFileUrl(ltrim($path, '/'));

        return response()->json([
            'success' => true,
            'logo_url' => $url,
        ]);
    }

    /**
     * Upload hero-afbeelding (achtergrond of ronde foto) via AJAX voor atom-v2 hero-banner.
     */
    public function uploadHeroImage(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();
        // Accept both 'image' and 'file' for compatibility (e.g. WYSIWYG vs home-sections)
        $file = $request->file('image') ?? $request->file('file');
        if (! $file) {
            return response()->json([
                'message' => 'Geen afbeelding ontvangen. Selecteer een bestand (max. 5MB, JPEG/PNG/GIF/WebP).',
                'errors' => ['image' => ['Selecteer een afbeelding.']],
            ], 422);
        }
        if (! $file->isValid()) {
            $err = $file->getError();
            if ($err === \UPLOAD_ERR_INI_SIZE || $err === \UPLOAD_ERR_FORM_SIZE) {
                $uploadMax = ini_get('upload_max_filesize');
                $postMax = ini_get('post_max_size');
                $msg = 'Het bestand wordt door de server geweigerd (te groot). Serverlimiet: upload_max_filesize='.$uploadMax.', post_max_size='.$postMax.'. Stel in php.ini beide in op minimaal 6M en herstart de webserver.';
            } else {
                $msg = 'Upload mislukt. Probeer een kleiner bestand of controleer de serverinstellingen.';
            }

            return response()->json([
                'message' => $msg,
                'errors' => ['image' => [$msg]],
            ], 422);
        }
        $validator = \Illuminate\Support\Facades\Validator::make(
            ['previous_url' => $request->input('previous_url')],
            ['previous_url' => 'nullable|string|max:500']
        );
        if ($validator->fails()) {
            return response()->json(['message' => 'Validatie mislukt.', 'errors' => $validator->errors()], 422);
        }
        if ($file->getSize() > 5120 * 1024) {
            return response()->json([
                'message' => 'Het bestand mag maximaal 5MB groot zijn.',
                'errors' => ['image' => ['Het bestand mag maximaal 5MB groot zijn.']],
            ], 422);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $allowedExt = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if (! in_array($ext, $allowedExt, true)) {
            return response()->json([
                'message' => 'Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.',
                'errors' => ['image' => ['Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.']],
            ], 422);
        }

        $moduleName = $this->moduleContext->getModuleNameFromRequest($request);
        $prefix = $moduleName ? $this->moduleContext->getUploadPathPrefix($moduleName) : '';
        $dir = $prefix.'website/hero';
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $previousUrl = $request->input('previous_url');
        if (is_string($previousUrl) && $previousUrl !== '') {
            $pathFromUrl = preg_replace('#^/storage/#', '', $previousUrl);
            if (! str_contains($pathFromUrl, '..') && (str_starts_with($pathFromUrl, 'website/hero/') || str_starts_with($pathFromUrl, 'modules/'))) {
                Storage::disk('public')->delete($pathFromUrl);
            }
        }
        $path = $file->store($dir, 'public');
        $url = '/storage/'.ltrim($path, '/');

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    /**
     * Upload document voor WYSIWYG (PDF, DOC, etc.); retourneert publieke URL.
     */
    public function uploadWysiwygDocument(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv|max:10240',
        ], [
            'document.required' => 'Selecteer een document.',
            'document.mimes' => 'Toegestaan: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV.',
            'document.max' => 'Maximaal 10MB.',
        ]);

        $file = $request->file('document');
        $moduleName = $this->moduleContext->getModuleNameFromRequest($request);
        $prefix = $moduleName ? $this->moduleContext->getUploadPathPrefix($moduleName) : '';
        $dir = $prefix.'website/documents';
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $file->store($dir, 'public');
        $url = '/storage/'.ltrim($path, '/');

        return response()->json([
            'success' => true,
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function destroy(Request $request, WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        $website_page->delete();

        return redirect()->route('admin.website-pages.index', $this->websitePagesIndexQuery($request))->with('success', 'Pagina verwijderd.');
    }

    /**
     * Uniekheidsregel voor slug: per module én (indien kolom bestaat) per company_id.
     * Bij module-connection gebruiken we een closure i.p.v. Rule::unique(connection.table)
     * om te voorkomen dat Laravel een niet-bestaande connection()-methode op de Unique-rule aanroept.
     *
     * @param  string|null  $connection  Database connection (bijv. module_taxi) of null voor default
     * @param  int|null  $ignoreId  Bij update: id van de huidige pagina om te negeren
     * @param  int|null  $scopeCompanyId  Alleen botsen met rijen voor dit bedrijf; null = alleen rijen zonder company_id (globaal)
     * @return \Closure|Rule
     */
    private function buildSlugUniqueRule(?string $connection, ?string $moduleName, ?int $ignoreId = null, ?int $scopeCompanyId = null)
    {
        if ($connection !== null) {
            return function (string $attribute, mixed $value, \Closure $fail) use ($connection, $moduleName, $ignoreId, $scopeCompanyId): void {
                $table = 'website_pages';
                $query = DB::connection($connection)->table($table)->where('slug', $value);
                if ($moduleName === null) {
                    $query->whereNull('module_name');
                } else {
                    $query->where('module_name', $moduleName);
                }
                if (Schema::connection($connection)->hasColumn($table, 'company_id')) {
                    if ($scopeCompanyId !== null) {
                        $query->where('company_id', $scopeCompanyId);
                    } else {
                        $query->whereNull('company_id');
                    }
                }
                if ($ignoreId !== null) {
                    $query->where('id', '!=', $ignoreId);
                }
                if ($query->exists()) {
                    $fail('Deze slug wordt al gebruikt voor dit bedrijf binnen deze module. Kies een andere slug.');
                }
            };
        }
        $table = 'website_pages';
        $hasCompanyId = Schema::hasColumn($table, 'company_id');
        $rule = Rule::unique($table, 'slug')
            ->where(function ($q) use ($moduleName, $scopeCompanyId, $hasCompanyId) {
                if ($moduleName === null) {
                    $q->whereNull('module_name');
                } else {
                    $q->where('module_name', $moduleName);
                }
                if ($hasCompanyId) {
                    if ($scopeCompanyId !== null) {
                        $q->where('company_id', $scopeCompanyId);
                    } else {
                        $q->whereNull('company_id');
                    }
                }
            });
        if ($ignoreId !== null) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    /**
     * company_id waarmee slug-uniekheid moet worden afgebakend (zelfde bron als bij opslaan), of null voor globale pagina's.
     */
    private function resolveCompanyIdForWebsitePageSlugRule(Request $request, ?WebsitePage $existing): ?int
    {
        if ($existing !== null) {
            $cid = $existing->getAttribute('company_id');
            if ($cid !== null && $cid !== '') {
                return (int) $cid;
            }
        }
        $implicit = $this->resolveWebsitePageCompanyIdFromImplicitContext($request);
        if ($implicit !== null) {
            return $implicit;
        }
        $raw = $request->input('company_id');
        if ($raw !== null && $raw !== '' && is_numeric($raw)) {
            $id = (int) $raw;

            return Company::whereKey($id)->exists() ? $id : null;
        }

        return null;
    }

    /**
     * Genormaliseerde module_name voor validatie (null bij leeg).
     * Gebruikt voor slug-uniekheid per module.
     */
    private function normalizeModuleNameForValidation(mixed $value): ?string
    {
        if ($value === null || ! is_string($value)) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Resolve form module_name naar de canonieke naam uit de modules-tabel.
     * Zorgt dat opslaan en frontend (getBrandingModule()->name) dezelfde connection/naam gebruiken.
     */
    private function resolveCanonicalModuleName(mixed $value): ?string
    {
        $trimmed = $this->normalizeModuleNameForValidation($value);
        if ($trimmed === null) {
            return null;
        }
        $module = Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower($trimmed)])->first();

        return $module ? $module->name : $trimmed;
    }

    /**
     * Bepaal frontend_theme_id uit het request.
     * Als de gebruiker expliciet een thema heeft gekozen (frontend_theme_id), wordt die gebruikt.
     * Anders: bij een module het thema van die module, bij kernpagina's null.
     * Gebruikt voor slug-uniekheid per thema.
     */
    private function resolveFrontendThemeIdFromRequest(Request $request): ?int
    {
        $themeId = $request->input('frontend_theme_id');
        if ($themeId !== null && $themeId !== '') {
            $id = (int) $themeId;
            if ($id > 0 && FrontendTheme::where('id', $id)->exists()) {
                return $id;
            }
        }
        $moduleName = $request->input('module_name');
        if (empty($moduleName) || ! is_string($moduleName)) {
            return null;
        }
        $module = Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower(trim($moduleName))])->first();
        if ($module && $module->frontend_theme_id) {
            return (int) $module->frontend_theme_id;
        }
        $active = $this->websiteBuilder->getActiveTheme();

        return $active ? (int) $active->id : null;
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot website-pagina\'s.');
        }
    }

    /**
     * Querystring voor tenant-wizard (company onboarding) — behouden bij navigatie tussen wizard en website-pagina's.
     *
     * @return array<string, int>
     */
    /**
     * Tenant/wizard-context voor de lijst website-pagina's: gekozen bedrijf in sidebar of wizard_company in URL.
     */
    private function resolveTenantCompanyIdForWebsitePagesList(Request $request): ?int
    {
        if ($request->boolean('from_wizard')) {
            $wizardCompany = $request->input('wizard_company');
            if ($wizardCompany !== null && $wizardCompany !== '' && is_numeric($wizardCompany)) {
                $id = (int) $wizardCompany;

                return Company::whereKey($id)->exists() ? $id : null;
            }
        }
        $tc = $request->input('tenant_company');
        if ($tc !== null && $tc !== '' && is_numeric($tc)) {
            $id = (int) $tc;

            return Company::whereKey($id)->exists() ? $id : null;
        }
        $st = session('selected_tenant');
        if ($st !== null && $st !== '') {
            $id = (int) $st;

            return Company::whereKey($id)->exists() ? $id : null;
        }

        return null;
    }

    /**
     * Querystring + hidden fields voor navigatie (wizard of super-admin met tenant in sidebar).
     *
     * @return array<string, int>
     */
    private function websitePagesIndexQuery(Request $request): array
    {
        $q = [];
        if ($request->boolean('from_wizard')) {
            $companyId = $request->input('wizard_company');
            if ($companyId !== null && $companyId !== '' && is_numeric($companyId)) {
                $q = [
                    'from_wizard' => 1,
                    'wizard_company' => (int) $companyId,
                    'wizard_step' => max(1, min(7, (int) $request->input('wizard_step', 6))),
                ];
            }
        }

        if (auth()->check() && auth()->user()->hasRole('super-admin')) {
            $fromRequest = $request->input('tenant_company');
            if ($fromRequest !== null && $fromRequest !== '' && is_numeric($fromRequest)) {
                if (! isset($q['wizard_company'])) {
                    $q['tenant_company'] = (int) $fromRequest;
                }
            } elseif (! isset($q['wizard_company'])) {
                $st = session('selected_tenant');
                if ($st !== null && $st !== '' && is_numeric($st)) {
                    $q['tenant_company'] = (int) $st;
                }
            }
        }

        return $q;
    }

    private function resolveTenantWizardReturnUrl(Request $request): ?string
    {
        $q = $this->websitePagesIndexQuery($request);
        if ($q === [] || ! isset($q['wizard_company'], $q['wizard_step'])) {
            return null;
        }
        $company = Company::find($q['wizard_company']);
        if (! $company) {
            return null;
        }

        return route('admin.companies.wizard.step', [$company, $q['wizard_step']]);
    }

    /**
     * Koppel website_pages aan een bedrijf (tenant) wanneer de kolom bestaat: wizard, geselecteerde tenant of user.company_id.
     */
    private function mergeCompanyIdIntoWebsitePageSaveData(array &$data, Request $request, string $connectionForSchema, ?WebsitePage $existing): void
    {
        $table = (new WebsitePage)->getTable();
        if (! Schema::connection($connectionForSchema)->hasColumn($table, 'company_id')) {
            return;
        }
        $resolved = $this->resolveWebsitePageCompanyIdForPersistence($request, $existing);
        if ($resolved !== null) {
            $data['company_id'] = $resolved;

            return;
        }
        if ($existing !== null) {
            $data['company_id'] = $existing->getAttribute('company_id');
        }
    }

    /**
     * Tenant/wizard/sessie: welk bedrijf impliciet actief is (zonder formulier-dropdown).
     */
    private function resolveWebsitePageCompanyIdFromImplicitContext(Request $request): ?int
    {
        $wq = $this->websitePagesIndexQuery($request);
        if (isset($wq['wizard_company'])) {
            $id = (int) $wq['wizard_company'];

            return Company::whereKey($id)->exists() ? $id : null;
        }
        if (isset($wq['tenant_company'])) {
            $id = (int) $wq['tenant_company'];

            return Company::whereKey($id)->exists() ? $id : null;
        }
        $st = session('selected_tenant');
        if ($st !== null && $st !== '') {
            $id = (int) $st;

            return Company::whereKey($id)->exists() ? $id : null;
        }
        $user = auth()->user();
        if ($user && $user->company_id) {
            $id = (int) $user->company_id;

            return Company::whereKey($id)->exists() ? $id : null;
        }

        return null;
    }

    /**
     * Definitieve company_id bij opslaan: bestaande koppeling blijft; anders impliciete tenant; anders gekozen dropdown.
     */
    private function resolveWebsitePageCompanyIdForPersistence(Request $request, ?WebsitePage $existing): ?int
    {
        if ($existing !== null) {
            $cid = $existing->getAttribute('company_id');
            if ($cid !== null && $cid !== '') {
                return (int) $cid;
            }
        }
        $implicit = $this->resolveWebsitePageCompanyIdFromImplicitContext($request);
        if ($implicit !== null) {
            return $implicit;
        }
        $raw = $request->input('company_id');
        if ($raw !== null && $raw !== '' && is_numeric($raw)) {
            $id = (int) $raw;

            return Company::whereKey($id)->exists() ? $id : null;
        }

        return null;
    }

    /**
     * Validatieregels voor company_id wanneer de kolom bestaat en er geen impliciete tenant is.
     *
     * @return array<string, array<int, mixed>>
     */
    private function websitePageCompanyIdValidationRules(Request $request, ?WebsitePage $existing, ?string $moduleConnection): array
    {
        $conn = $existing !== null
            ? $existing->getConnection()->getName()
            : ($moduleConnection ?? config('database.default'));
        $table = (new WebsitePage)->getTable();
        if (! Schema::connection($conn)->hasColumn($table, 'company_id')) {
            return [];
        }
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            return [];
        }
        if ($this->resolveWebsitePageCompanyIdFromImplicitContext($request) !== null) {
            return ['company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')]];
        }
        if ($existing !== null) {
            $cid = $existing->getAttribute('company_id');
            if ($cid !== null && $cid !== '') {
                return ['company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')]];
            }
        }

        return [
            'company_id' => ['required', 'integer', Rule::exists('companies', 'id')],
        ];
    }

    /**
     * Context voor super-admin UI: opgeslagen bedrijf, impliciete tenant, of dropdown om te kiezen.
     *
     * @return array{visible: bool, has_company_column: bool, stored_company: ?\App\Models\Company, effective_company: ?\App\Models\Company, stored_id: ?int, show_company_dropdown: bool, companies: \Illuminate\Support\Collection<int, \App\Models\Company>}
     */
    private function buildWebsitePageCompanyContext(Request $request, ?WebsitePage $page): array
    {
        $isSuperAdmin = auth()->check() && auth()->user()->hasRole('super-admin');
        $storedId = $page !== null ? $page->getAttribute('company_id') : null;
        $storedId = ($storedId !== null && $storedId !== '') ? (int) $storedId : null;
        $storedCompany = $storedId ? Company::query()->find($storedId) : null;
        $implicitId = $this->resolveWebsitePageCompanyIdFromImplicitContext($request);
        $implicitCompany = $implicitId ? Company::query()->find($implicitId) : null;
        $conn = $page !== null ? $page->getConnection()->getName() : config('database.default');
        $hasColumn = Schema::connection($conn)->hasColumn((new WebsitePage)->getTable(), 'company_id');
        $visible = $isSuperAdmin && $hasColumn;
        $showCompanyDropdown = $visible && $storedId === null && $implicitId === null;
        $companies = ($visible && $storedId === null)
            ? Company::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return [
            'visible' => $visible,
            'has_company_column' => $hasColumn,
            'stored_company' => $storedCompany,
            'effective_company' => $implicitCompany,
            'stored_id' => $storedId,
            'show_company_dropdown' => $showCompanyDropdown,
            'companies' => $companies,
        ];
    }

    /**
     * @param  iterable<int, WebsitePage>  $pages
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function websitePagesCompanyNameMapForIndex(iterable $pages): \Illuminate\Support\Collection
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            return collect();
        }
        $ids = collect($pages)
            ->pluck('company_id')
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return Company::query()
            ->whereIn('id', $ids)
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => (string) $name]);
    }
}

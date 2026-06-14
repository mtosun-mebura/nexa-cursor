<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\WebsitePage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WebsiteBuilderService
{
    public function __construct(
        protected ModuleManager $moduleManager,
        protected ?ModuleDatabaseService $moduleDb = null
    ) {
        $this->moduleDb = $this->moduleDb ?? app(ModuleDatabaseService::class);
    }

    /**
     * Query WebsitePage op de juiste connection: alleen module-DB als die een eigen {@code website_pages}-tabel heeft.
     * Bij schema-strategy staan module-pagina's meestal in {@code public.website_pages} (met module_name); zonder deze
     * check valt PG via search_path terug op public en levert de module-connectie verkeerde rijen op.
     * Publieke frontend-requests krijgen een tenant/global scope via {@see applyWebsitePageTenantScope()}.
     */
    private function websitePageQuery(?string $forModuleName = null): Builder
    {
        $useModuleConnection = false;
        $connection = null;

        if ($forModuleName !== null && $forModuleName !== ''
            && $this->moduleDb && $this->moduleDb->supportsModuleDatabases()) {
            try {
                $connection = $this->moduleDb->getModuleConnectionName($forModuleName);
                if (! Config::has("database.connections.{$connection}")) {
                    $this->moduleDb->registerConnection($forModuleName);
                }
                if (Config::has("database.connections.{$connection}")
                    && $this->moduleConnectionHasOwnWebsitePagesTable($forModuleName, $connection)) {
                    $useModuleConnection = true;
                }
            } catch (\Throwable) {
                $useModuleConnection = false;
            }
        }

        if ($useModuleConnection && $connection !== null) {
            $q = WebsitePage::on($connection);
            $q->whereRaw('LOWER(module_name) = ?', [strtolower($forModuleName)]);
        } else {
            $q = WebsitePage::query();
            if ($forModuleName !== null && $forModuleName !== '') {
                $q->whereRaw('LOWER(module_name) = ?', [strtolower($forModuleName)]);
            }
        }

        $this->applyWebsitePageTenantScope($q);

        return $q;
    }

    /**
     * Pagina's uit module-connectie (search_path-fallback) terug op de hoofd-DB zetten voor relaties (theme, …).
     */
    protected function ensureWebsitePageOnDefaultConnection(?WebsitePage $page): ?WebsitePage
    {
        if ($page === null) {
            return null;
        }

        $default = (new WebsitePage)->getConnectionName();
        if ($page->getConnectionName() === $default) {
            return $page;
        }

        $reloaded = WebsitePage::query()->whereKey($page->getKey())->first();
        if ($reloaded !== null) {
            return $reloaded;
        }

        $page->setConnection($default);

        return $page;
    }

    protected function resolvedPublicTenantCompanyId(): ?int
    {
        if (! app()->bound('resolved_tenant_id')) {
            return null;
        }
        $id = app('resolved_tenant_id');
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }

    /**
     * Tenant-bedrijf voor een pagina: eigen company_id, anders de opgeloste publieke tenant.
     */
    public function tenantCompanyIdForPage(WebsitePage $page): ?int
    {
        $cid = $page->getAttribute('company_id');
        $cid = ($cid !== null && $cid !== '') ? (int) $cid : null;

        return $cid ?? $this->resolvedPublicTenantCompanyId();
    }

    /**
     * Google Maps API-key per tenant: eerst de (tenant-)instelling in general_settings, daarna pas .env.
     * Zo kan elke tenant een eigen key gebruiken; .env is alleen fallback als er niets is ingesteld.
     */
    public function resolveGoogleMapsApiKeyForPage(WebsitePage $page): string
    {
        $cid = $this->tenantCompanyIdForPage($page);
        $fromSetting = trim((string) (GeneralSetting::get('GOOGLE_MAPS_API_KEY', null, $cid) ?? ''));
        if ($fromSetting !== '') {
            return $fromSetting;
        }

        return trim((string) (config('maps.api_key') ?? env('GOOGLE_MAPS_API_KEY', '')));
    }

    /**
     * Google Maps Map ID per tenant: eerst de (tenant-)instelling, daarna .env.
     */
    public function resolveGoogleMapsMapIdForPage(WebsitePage $page): string
    {
        $cid = $this->tenantCompanyIdForPage($page);
        $fromSetting = trim((string) (GeneralSetting::get('GOOGLE_MAPS_MAP_ID', null, $cid) ?? ''));
        if ($fromSetting !== '') {
            return $fromSetting;
        }

        return trim((string) (config('maps.map_id') ?? env('GOOGLE_MAPS_MAP_ID', '')));
    }

    /**
     * WhatsApp-widget rechtsonder: uitsluitend op basis van de (tenant-)instelling in general_settings,
     * niet op basis van .env. Tonen zodra de instelling aan staat én er een telefoonnummer is.
     *
     * @return array{enabled: bool, phone: string, message: string}
     */
    public function resolveWhatsappWidgetForPage(WebsitePage $page): array
    {
        $cid = $this->tenantCompanyIdForPage($page);
        $enabled = (string) (GeneralSetting::get('WHATSAPP_WIDGET_ENABLED', '0', $cid) ?? '0') === '1';
        $phoneDigits = preg_replace('/\D+/', '', trim((string) (GeneralSetting::get('WHATSAPP_WIDGET_PHONE', '', $cid) ?? '')));
        $message = trim((string) (GeneralSetting::get('WHATSAPP_WIDGET_DEFAULT_MESSAGE', '', $cid) ?? ''));
        if ($message === '') {
            $message = 'Hallo, ik heb een vraag over jullie diensten.';
        }

        return [
            'enabled' => $enabled && $phoneDigits !== '',
            'phone' => (string) $phoneDigits,
            'message' => $message,
        ];
    }

    protected function isAdminLikeRequest(): bool
    {
        if (! function_exists('request') || ! request()) {
            return false;
        }
        $path = request()->path();

        return $path === 'admin' || str_starts_with($path, 'admin/')
            || $path === 'livewire' || str_starts_with($path, 'livewire/');
    }

    /**
     * Actieve module-namen (lowercase) gekoppeld aan een bedrijf, voor website_pages-tenantfilter.
     *
     * @return array<int, string>
     */
    protected function linkedInstalledActiveModuleNamesLowerForCompany(Company $company): array
    {
        return $company->modules()
            ->where('modules.installed', true)
            ->where('modules.active', true)
            ->pluck('modules.name')
            ->filter()
            ->map(fn ($n) => strtolower((string) $n))
            ->values()
            ->all();
    }

    /**
     * WHERE-clause: zelfde zichtbaarheid als op de live site voor één bedrijf (eigen rijen,
     * gedeelde module-rijen zonder company_id voor gekoppelde modules, kern zonder tenant/module).
     *
     * @param  array<int, string>  $linkedLowerModuleNames
     */
    protected function applyWebsitePageVisibilityWhereForCompany(Builder $query, int $tenantCompanyId, array $linkedLowerModuleNames): void
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $connection = $query->getConnection()->getName();
        $defaultConn = (string) config('database.default');
        if (! Schema::connection($connection)->hasColumn($table, 'company_id')
            && ! Schema::connection($defaultConn)->hasColumn($table, 'company_id')) {
            $query->whereRaw('1 = 0');

            return;
        }

        $grammar = $query->getGrammar();
        $moduleCol = $grammar->wrap($table.'.module_name');

        $query->where(function (Builder $q) use ($table, $tenantCompanyId, $linkedLowerModuleNames, $moduleCol) {
            $q->where($table.'.company_id', $tenantCompanyId);
            if ($linkedLowerModuleNames !== []) {
                $q->orWhere(function (Builder $q2) use ($table, $linkedLowerModuleNames, $moduleCol) {
                    $q2->whereNull($table.'.company_id')
                        ->whereNotNull($table.'.module_name')
                        ->whereRaw('LOWER('.$moduleCol.') in ('.implode(',', array_fill(0, count($linkedLowerModuleNames), '?')).')', $linkedLowerModuleNames);
                });
            }
        });
    }

    /**
     * Admin-overzicht met gekozen tenant: alleen rijen met exact dit company_id (geen gedeelde module-pagina's zonder id).
     */
    protected function applyWebsitePageStrictAdminTenantWhere(Builder $query, int $tenantCompanyId): void
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $connection = $query->getConnection()->getName();
        if (Schema::connection($connection)->hasColumn($table, 'company_id')) {
            $query->where($table.'.company_id', $tenantCompanyId);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * Zelfde zichtbaarheid als op de live site voor één bedrijf (tenant).
     */
    protected function applyWebsitePageQueryScopeForCompany(Builder $query, int $tenantCompanyId): void
    {
        $company = Company::query()->find($tenantCompanyId);
        if (! $company) {
            $query->whereRaw('1 = 0');

            return;
        }
        $linked = $this->linkedInstalledActiveModuleNamesLowerForCompany($company);
        $this->applyWebsitePageVisibilityWhereForCompany($query, $tenantCompanyId, $linked);
    }

    /**
     * Beperkt website_pages voor de publieke site: per ingelogd host-tenant eigen content,
     * op centrale hosts alleen rijen zonder company_id (geen tenant-gebonden module-sites).
     */
    protected function applyWebsitePageTenantScope(Builder $query): void
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $connection = $query->getConnection()->getName();
        $defaultConn = (string) config('database.default');
        if (! Schema::connection($connection)->hasColumn($table, 'company_id')
            && ! Schema::connection($defaultConn)->hasColumn($table, 'company_id')) {
            return;
        }
        if ($this->isAdminLikeRequest()) {
            return;
        }

        $tenantId = $this->resolvedPublicTenantCompanyId();

        if ($tenantId !== null) {
            $this->applyWebsitePageQueryScopeForCompany($query, $tenantId);

            return;
        }

        $query->whereNull($table.'.company_id');
    }

    /**
     * Thema toegewezen aan een tenant-bedrijf (alleen als het thema gepubliceerd/is_active is).
     */
    public function getThemeForCompany(?int $companyId): ?FrontendTheme
    {
        if ($companyId === null || $companyId <= 0) {
            return null;
        }

        $company = Company::query()->with('frontendTheme')->find($companyId);
        if (! $company || ! $company->frontend_theme_id) {
            return null;
        }

        $theme = $company->frontendTheme;
        if ($theme && $theme->is_active) {
            return $theme;
        }

        return null;
    }

    /**
     * Bepaalt het thema waarmee een website-pagina wordt getoond.
     * Volgorde: opgeslagen pagina-thema (indien gepubliceerd) → tenant-thema op bedrijf → module-thema → fallback.
     */
    public function getThemeForPage(WebsitePage $page): ?FrontendTheme
    {
        $companyId = $page->getAttribute('company_id');
        $companyId = ($companyId !== null && $companyId !== '') ? (int) $companyId : null;
        if ($companyId === null) {
            $companyId = $this->resolvedPublicTenantCompanyId();
        }

        if ($page->frontend_theme_id) {
            $pageTheme = FrontendTheme::find((int) $page->frontend_theme_id);
            if ($pageTheme?->is_active) {
                return $pageTheme;
            }
        }

        $companyTheme = $this->getThemeForCompany($companyId);
        if ($companyTheme) {
            return $companyTheme;
        }

        if (filled($page->module_name)) {
            $module = Module::where('installed', true)
                ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $page->module_name))])
                ->first();
            if ($module?->frontend_theme_id) {
                $moduleTheme = FrontendTheme::find((int) $module->frontend_theme_id);
                if ($moduleTheme?->is_active) {
                    return $moduleTheme;
                }
            }
        }

        return $this->getActiveTheme($companyId);
    }

    /**
     * Modulenaam (zoals in tabel `modules.name`) voor branding op deze pagina: moet overeenkomen met de module
     * waarvan je in Admin → Modules → configureren de vink "Knop Mijn-omgeving tonen" zet.
     *
     * - Heeft de pagina een `module_name`? → die module (case-insensitive lookup in {@see getSiteBranding()}).
     * - Anders (`null`) → {@see getSiteBranding()} gebruikt {@see getBrandingModule()} (o.a. vastgezette module op het actieve thema).
     *
     * Het actieve thema (Metronic, Atom, …) bepaalt alleen layout/styling via {@see getThemeForPage()}; het wijzigt
     * niet welke `configuration` voor de Mijn-knop geldt — dat is uitsluitend de module hierboven.
     */
    public function getBrandingModuleNameForWebsitePage(WebsitePage $page): ?string
    {
        if (! filled($page->module_name)) {
            return null;
        }
        $name = trim((string) $page->module_name);

        return $name !== '' ? $name : null;
    }

    /**
     * Branding voor een specifieke website-pagina (module + gekoppeld bedrijf).
     * Gebruik in admin-preview en frontend-render zodat logo/favicon uit tenant-instellingen komen.
     */
    public function getSiteBrandingForWebsitePage(WebsitePage $page): array
    {
        $companyId = null;
        if ($page->company_id !== null && $page->company_id !== '') {
            $companyId = (int) $page->company_id;
        }

        return $this->getSiteBranding(
            $this->getBrandingModuleNameForWebsitePage($page),
            false,
            $companyId
        );
    }

    /**
     * Logo-, favicon-, sitenaam en omschrijving voor de website layout.
     * Sitenaam en omschrijving komen van het actieve module-config (indien ingevuld), anders van algemene instellingen.
     *
     * **Mijn-knop (dashboard_link_visible):** Alleen afhankelijk van module-config `configuration.dashboard_link_visible`
     * voor de gekozen module (string `"1"`/`"0"` of bool), niet van het frontend-thema (slug/kleuren).
     * - Expliciete modulenaam: lookup `modules` op `LOWER(name)`; als `dashboard_link_visible` ontbreekt → **uit** (standaard na install);
     *   als aanwezig → {@see boolFromDashboardConfig()}.
     * - Geen modulenaam (`null`, publiek): fallback {@see getBrandingModule()} voor dezelfde regels op die module.
     * - Staging-preview: zie `$forStagingPreview`; zonder modulecontext blijft de knop uit.
     *
     * @param  string|null  $forModuleName  Optioneel: module waarvan de configuratie gebruikt wordt (b.v. staging-URL).
     *                                      Anders: {@see getBrandingModule()}.
     * @param  bool  $forStagingPreview  Staging-thema is niet altijd het actieve site-thema: geen fallback naar
     *                                   {@see getBrandingModule()} zonder expliciete modulenaam. Zonder modulecontext
     *                                   blijft de dashboard-knop uit (regel hieronder).
     * @param  int|null  $forCompanyId  Expliciet bedrijf voor tenant-logo/instellingen (bijv. admin preview van pagina).
     * @return array{logo_url: ?string, logo_dark_url: ?string, logo_size_px: int, favicon_url: ?string, site_name: string, site_description: string, dashboard_link_label: string, dashboard_link_visible: bool, dashboard_link_url: string, dashboard_link_module: ?string}
     */
    public function getSiteBranding(?string $forModuleName = null, bool $forStagingPreview = false, ?int $forCompanyId = null): array
    {
        $logoSizePx = $this->resolveLogoSizePx($forCompanyId);

        $logoPath = GeneralSetting::get('logo', null, $forCompanyId);
        $logoUrl = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoUrl = $this->publicFileUrl(ltrim($logoPath, '/'));
        }
        $logoDarkUrl = null;
        $logoMode = GeneralSetting::get('logo_mode', 'single', $forCompanyId);
        if ($logoMode === 'light_dark') {
            $logoDarkPath = GeneralSetting::get('logo_dark', null, $forCompanyId);
            if ($logoDarkPath && Storage::disk('public')->exists($logoDarkPath)) {
                $logoDarkUrl = $this->publicFileUrl(ltrim($logoDarkPath, '/'));
            }
        }

        $faviconPath = GeneralSetting::get('favicon', null, $forCompanyId);
        $faviconUrl = null;
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            $faviconUrl = $this->publicFileUrl(ltrim($faviconPath, '/'));
        }

        $siteName = GeneralSetting::get('site_name', config('app.name', 'Nexa'), $forCompanyId);
        $siteDescription = GeneralSetting::get('site_description', '', $forCompanyId);
        $dashboardLinkLabel = GeneralSetting::get('dashboard_link_label', 'Mijn Nexa', $forCompanyId);
        $dashboardLinkVisible = GeneralSetting::get('dashboard_link_visible', '1', $forCompanyId) === '1';
        $dashboardLinkUrl = route('dashboard');
        $dashboardLinkModule = null;

        $explicitModuleRequested = $forModuleName !== null && trim((string) $forModuleName) !== '';
        // Staging zonder expliciete module: niet terugvallen op getBrandingModule() (ander actief thema) of op "dashboard globaal aan"
        if ($forStagingPreview && ! $explicitModuleRequested) {
            $dashboardLinkVisible = false;
        }

        $brandingModule = null;
        if ($explicitModuleRequested) {
            $brandingModule = Module::whereRaw('LOWER(name) = ?', [strtolower(trim((string) $forModuleName))])->first();
        }
        // Geen fallback naar getBrandingModule() als er expliciet een module is gevraagd: voorkomt verkeerde module + knop "aan"
        if (! $brandingModule && ! $explicitModuleRequested && ! $forStagingPreview) {
            $brandingModule = $this->getBrandingModule();
        }
        if ($brandingModule) {
            $config = is_array($brandingModule->configuration) ? $brandingModule->configuration : [];
            if (! empty($config['app_name'])) {
                $siteName = $config['app_name'];
            }
            if (isset($config['app_description']) && (string) $config['app_description'] !== '') {
                $siteDescription = (string) $config['app_description'];
            }
            if (isset($config['dashboard_link_label']) && (string) $config['dashboard_link_label'] !== '') {
                $dashboardLinkLabel = (string) $config['dashboard_link_label'];
            }
            if (array_key_exists('dashboard_link_visible', $config)) {
                $dashboardLinkVisible = $this->boolFromDashboardConfig($config['dashboard_link_visible']);
            } else {
                $dashboardLinkVisible = false;
            }
        } elseif ($explicitModuleRequested) {
            // Gevraagde modulenaam bestaat niet in DB: geen globale fallback die de knop weer aanzet
            $dashboardLinkVisible = false;
        }

        if ($brandingModule) {
            $dashboardLinkModule = strtolower((string) $brandingModule->name);
            $dashboardLinkUrl = $this->portalDashboardUrlForModuleName($dashboardLinkModule);

            if ($dashboardLinkVisible) {
                if (! $this->moduleManager->isActive($dashboardLinkModule)) {
                    $dashboardLinkVisible = false;
                } elseif (! $this->tenantHasModuleNamed($dashboardLinkModule)) {
                    $dashboardLinkVisible = false;
                }
            }
        }

        $this->applyCompanyLogoFallback($logoUrl, $logoDarkUrl, $forCompanyId);

        $logoUrl = $logoUrl ? $this->storageUrlToDisplayUrl($logoUrl) : null;
        $logoDarkUrl = $logoDarkUrl ? $this->storageUrlToDisplayUrl($logoDarkUrl) : null;
        $faviconUrl = $faviconUrl ? $this->storageUrlToDisplayUrl($faviconUrl) : null;

        return [
            'logo_url' => $logoUrl,
            'logo_dark_url' => $logoDarkUrl,
            'logo_size_px' => $logoSizePx,
            'favicon_url' => $faviconUrl,
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'dashboard_link_label' => $dashboardLinkLabel,
            'dashboard_link_visible' => (bool) $dashboardLinkVisible,
            'dashboard_link_url' => $dashboardLinkUrl,
            'dashboard_link_module' => $dashboardLinkModule,
        ];
    }

    /**
     * Bepaal welke frontend-module leidend is (taxi, skillmatching, …) op basis van route, intended URL en tenant.
     */
    public function resolvePublicFrontendModuleName(?Request $request = null): ?string
    {
        $request = $request ?? request();
        if ($request === null) {
            return null;
        }

        if ($request->routeIs('taxi.portal.*')) {
            return 'taxi';
        }

        foreach ($this->collectFrontendIntendedUrls($request) as $intended) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';
            if ($path !== '' && Str::startsWith($path, '/mijn-taxi')) {
                return 'taxi';
            }
            if ($path !== '' && $this->isSkillmatchingFrontendPath($path)) {
                return 'skillmatching';
            }
        }

        $brandingModule = $this->getBrandingModule();
        if ($brandingModule && filled($brandingModule->name)) {
            return strtolower((string) $brandingModule->name);
        }

        return null;
    }

    /**
     * Of Skillmatching-app-links (dashboard, vacatures, matches, …) getoond mogen worden.
     */
    public function shouldShowSkillmatchingFrontendAppLinks(?string $moduleName = null): bool
    {
        $moduleName = $moduleName ?? $this->resolvePublicFrontendModuleName();

        $tenant = $this->resolveBrandingCompany();

        return $this->moduleManager->isActive('skillmatching')
            && $moduleName === 'skillmatching'
            && ($tenant === null || $tenant->hasSkillmatchingModule());
    }

    /**
     * View-data voor frontend-layouts (website + app): module-context en zichtbaarheid nav-links.
     *
     * @param  array<string, mixed>  $existingData
     * @return array{showSkillmatchingAppLinks: bool, showGuestSkillmatchingLinks: bool, frontendResolvedModuleName: ?string}
     */
    public function frontendPortalViewData(array $existingData = []): array
    {
        $moduleName = null;
        if (! empty($existingData['brandingModuleName'])) {
            $moduleName = strtolower(trim((string) $existingData['brandingModuleName']));
        } elseif (request()->routeIs('taxi.portal.*')) {
            $moduleName = 'taxi';
        } elseif (isset($existingData['page']) && filled($existingData['page']->module_name ?? null)) {
            $moduleName = strtolower(trim((string) $existingData['page']->module_name));
        } else {
            $moduleName = $this->resolvePublicFrontendModuleName();
        }

        return [
            'showSkillmatchingAppLinks' => $this->shouldShowSkillmatchingFrontendAppLinks($moduleName),
            'showGuestSkillmatchingLinks' => $moduleName !== 'taxi',
            'frontendResolvedModuleName' => $moduleName,
        ];
    }

    /**
     * Frontend-portaal-URL per module (Skillmatching-dashboard of Mijn Taxi).
     */
    public function portalDashboardUrlForModuleName(string $moduleName): string
    {
        return match (strtolower(trim($moduleName))) {
            'skillmatching' => route('dashboard'),
            'taxi' => route('taxi.portal.dashboard'),
            default => route('home'),
        };
    }

    /**
     * Of de huidige tenant de opgegeven module heeft (company_modules). Zonder tenant-context: true.
     */
    public function tenantHasModuleNamed(string $moduleName): bool
    {
        $company = $this->resolveBrandingCompany();
        if ($company === null) {
            return true;
        }

        return $company->hasModuleNamed($moduleName);
    }

    /**
     * @return list<string>
     */
    private function collectFrontendIntendedUrls(Request $request): array
    {
        $urls = [];

        foreach ([$request->query('intended'), $request->input('intended')] as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                $urls[] = $candidate;
            }
        }

        if ($request->hasSession()) {
            $fromSession = $request->session()->get('url.intended');
            if (is_string($fromSession) && $fromSession !== '') {
                $urls[] = $fromSession;
            }
        }

        return array_values(array_unique($urls));
    }

    private function isSkillmatchingFrontendPath(string $path): bool
    {
        foreach (['/dashboard', '/matches', '/agenda', '/applications', '/profile', '/settings', '/jobs'] as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tenant voor branding/portaal (host of ingelogde gebruiker).
     */
    public function resolveBrandingCompany(): ?Company
    {
        if (app()->bound('resolved_tenant') && app('resolved_tenant') instanceof Company) {
            return app('resolved_tenant');
        }

        $companyId = app()->bound('resolved_tenant_id') ? (int) app('resolved_tenant_id') : null;
        if ($companyId) {
            return Company::find($companyId);
        }

        return null;
    }

    /**
     * Logo-hoogte uit Algemene instellingen (zelfde bereik als admin #logo_size).
     */
    public function resolveLogoSizePx(?int $forCompanyId = null): int
    {
        $raw = GeneralSetting::get('logo_size', '26', $forCompanyId);
        $px = is_numeric($raw) ? (int) $raw : 26;

        return max(10, min(100, $px));
    }

    /**
     * Bedrijf voor tenant-instellingen of ingelogde gebruiker (frontend skillmatching e.d.).
     */
    public function resolveBrandingCompanyId(): ?int
    {
        $scoped = GeneralSetting::resolveScopeCompanyId();
        if ($scoped !== null) {
            return $scoped;
        }

        if ($this->isAdminLikeRequest()) {
            return null;
        }

        if (function_exists('auth') && auth()->check()) {
            $companyId = auth()->user()->company_id ?? null;
            if ($companyId) {
                return (int) $companyId;
            }
        }

        return null;
    }

    /**
     * Als er geen logo in general_settings staat: logo uit bedrijfsprofiel (wizard).
     */
    private function applyCompanyLogoFallback(?string &$logoUrl, ?string &$logoDarkUrl, ?int $forCompanyId = null): void
    {
        if ($logoUrl !== null && $logoUrl !== '') {
            return;
        }

        $companyId = $forCompanyId ?? $this->resolveBrandingCompanyId();
        if ($companyId === null) {
            return;
        }

        $company = Company::query()->find($companyId);
        if (! $company || ! $company->logo_blob) {
            return;
        }

        // Admin preview (geen tenant-host): data-URI i.p.v. /brand/company/… (die route vereist tenant-context).
        if ($this->isAdminLikeRequest()) {
            $logoUrl = $this->companyLogoDataUri($company, false);
            if ($company->logo_dark_blob) {
                $logoDarkUrl = $this->companyLogoDataUri($company, true);
            }

            return;
        }

        $logoUrl = route('frontend.company-brand.logo', $company);
        if ($company->logo_dark_blob) {
            $logoDarkUrl = route('frontend.company-brand.logo.dark', $company);
        }
    }

    private function companyLogoDataUri(Company $company, bool $dark): ?string
    {
        $blob = $dark ? $company->logo_dark_blob : $company->logo_blob;
        $mime = $dark ? $company->logo_dark_mime_type : $company->logo_mime_type;
        if (! $blob && $dark && $company->logo_blob) {
            $blob = $company->logo_blob;
            $mime = $company->logo_mime_type;
        }
        if (! $blob || trim((string) $blob) === '') {
            return null;
        }

        return 'data:'.($mime ?: 'image/png').';base64,'.trim((string) $blob);
    }

    /**
     * Module-config kan true/false, 1/0 of "1"/"0" zijn (JSON/cast).
     */
    private function boolFromDashboardConfig(mixed $value): bool
    {
        if ($value === true || $value === 1) {
            return true;
        }
        if ($value === false || $value === 0) {
            return false;
        }
        if (is_string($value)) {
            $s = strtolower(trim($value));
            if (in_array($s, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }

            return in_array($s, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * Publieke URL voor een bestand in storage/app/public (bruikbaar voor niet-ingelogde bezoekers).
     * Gebruikt de /file/ route zodat logo/favicon altijd laden.
     */
    public function publicFileUrl(string $path): string
    {
        $path = str_replace(['../', '..'], '', $path);
        $encoded = str_replace('/', '--', trim($path, '/'));

        return url('/file/'.$encoded);
    }

    /**
     * Publieke favicon-URL + MIME-type voor &lt;link rel="icon"&gt; en /favicon.ico.
     * Gebruikt /file/ (geen admin-auth), zodat browsers het icoon in de tab kunnen laden.
     *
     * @return array{url: string, type: string}
     */
    public function publicFaviconMeta(?int $forCompanyId = null): array
    {
        $default = [
            'url' => asset('images/nexa-x-logo.png'),
            'type' => 'image/png',
        ];

        $path = $forCompanyId !== null
            ? GeneralSetting::get('favicon', null, $forCompanyId)
            : GeneralSetting::get('favicon');

        if ((! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path))
            && $forCompanyId === null
            && ! app()->runningInConsole()
            && request()
        ) {
            $st = session('selected_tenant');
            if ($st !== null && $st !== '' && is_numeric($st)) {
                $path = GeneralSetting::get('favicon', null, (int) $st);
            }
        }

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            return $default;
        }

        $mtime = Storage::disk('public')->lastModified($path);
        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return [
            'url' => $this->publicFileUrl(ltrim($path, '/')).'?v='.$mtime,
            'type' => $mime,
        ];
    }

    /**
     * Zet een opgeslagen storage-URL (relatief of volledig) om naar een werkende weergave-URL via /file/.
     * Gebruik overal waar img src of background-image uit de database komt (bv. /storage/vehicles/..., http://.../storage/...).
     */
    public function storageUrlToDisplayUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }
        $u = trim((string) $url);
        if (str_starts_with($u, 'data:')) {
            return $u;
        }
        $path = null;
        if (str_starts_with($u, '/storage/')) {
            $path = preg_replace('#^/storage/#', '', $u);
        } elseif (preg_match('#^https?://[^/]+/storage/(.+)$#', $u, $m)) {
            $path = preg_replace('/[#?].*$/', '', $m[1]);
        }
        if ($path !== null) {
            return $this->publicFileUrl($path);
        }
        if (str_starts_with($u, 'http://') || str_starts_with($u, 'https://')) {
            if (preg_match('~^https?://[^/]+(/file/[^?#]+)~', $u, $m)) {
                return url($m[1]);
            }

            return $u;
        }

        return url($u);
    }

    /**
     * Module waarvan de website op localhost getoond wordt (branding, menu, home).
     * Prefereert het aan het actieve thema gekoppelde module (active_module_id), anders
     * een actieve module die het actieve thema gebruikt én minstens één actieve home heeft.
     *
     * Op een tenant-host ({@see ResolveTenantFromHost}): alleen modules die aan dat bedrijf hangen,
     * zodat een ander domein niet dezelfde module-site toont.
     */
    public function getBrandingModule(): ?Module
    {
        $tenantId = $this->resolvedPublicTenantCompanyId();

        return $tenantId !== null
            ? $this->getBrandingModuleForResolvedCompany($tenantId)
            : $this->getBrandingModuleForCentralHost();
    }

    private function moduleHasVisibleHome(string $moduleName): bool
    {
        if ($moduleName === '' || $moduleName === '0') {
            return false;
        }

        return $this->websitePageQuery($moduleName)->active()->where('page_type', 'home')->exists();
    }

    /**
     * Branding voor een company-domein: alleen gekoppelde geïnstalleerde actieve modules.
     */
    private function getBrandingModuleForResolvedCompany(int $companyId): ?Module
    {
        $company = Company::query()->find($companyId);
        if (! $company) {
            return null;
        }

        $mods = $company->modules()
            ->where('modules.installed', true)
            ->where('modules.active', true)
            ->orderBy('modules.id')
            ->get();

        if ($mods->isEmpty()) {
            return null;
        }

        $companyTheme = $this->getThemeForCompany($companyId);
        if ($companyTheme && $companyTheme->active_module_id) {
            $pinned = $mods->firstWhere('id', (int) $companyTheme->active_module_id);
            if ($pinned && $pinned->active) {
                $name = $pinned->name;
                if ($name !== null && $name !== '') {
                    return $pinned;
                }
            }
        }

        if ($companyTheme) {
            foreach ($mods->where('frontend_theme_id', $companyTheme->id) as $module) {
                $name = $module->name;
                if ($name === null || $name === '') {
                    continue;
                }
                if ($this->moduleHasVisibleHome((string) $name)) {
                    return $module;
                }
            }
        }

        foreach ($mods as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            if ($this->moduleHasVisibleHome((string) $name)) {
                return $module;
            }
        }

        return $mods->first();
    }

    /**
     * Branding op centrale / niet-tenant hosts: geen tenant-gebonden module-pagina's als "home" gebruiken.
     */
    private function getBrandingModuleForCentralHost(): ?Module
    {
        $activeTheme = $this->getActiveTheme();
        if (! $activeTheme) {
            return $this->firstActiveModuleWithVisibleHome()
                ?? Module::where('active', true)->where('installed', true)->orderBy('id')->first();
        }

        if ($activeTheme->active_module_id) {
            $pinned = Module::find($activeTheme->active_module_id);
            if ($pinned && $pinned->active && $pinned->installed) {
                $name = $pinned->name;
                if ($name !== null && $name !== '' && $this->moduleHasVisibleHome((string) $name)) {
                    return $pinned;
                }
            }
        }

        $candidates = Module::where('frontend_theme_id', $activeTheme->id)
            ->where('active', true)
            ->where('installed', true)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            if ($this->moduleHasVisibleHome((string) $name)) {
                return $module;
            }
        }

        return $this->firstActiveModuleWithVisibleHome()
            ?? $candidates->first()
            ?? Module::where('active', true)->where('installed', true)->orderBy('id')->first();
    }

    private function firstActiveModuleWithVisibleHome(): ?Module
    {
        foreach (Module::where('active', true)->where('installed', true)->orderBy('id')->get() as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            if ($this->moduleHasVisibleHome((string) $name)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Gepubliceerd thema voor context: tenant-bedrijf eerst, anders eerste beschikbare thema.
     */
    public function getActiveTheme(?int $companyId = null): ?FrontendTheme
    {
        if ($companyId === null) {
            $companyId = $this->resolvedPublicTenantCompanyId();
        }

        $companyTheme = $this->getThemeForCompany($companyId);
        if ($companyTheme) {
            return $companyTheme;
        }

        return FrontendTheme::getActive();
    }

    /**
     * Module om te tonen voor staging/startpagina voor dit thema: vastgezette module (active_module_id) indien actief,
     * anders eerste actieve module met dit thema (liefst met home, zie {@see getFirstModuleNameWithWebsiteForTheme}).
     */
    public function getStagingModuleNameForTheme(int $themeId): ?string
    {
        $theme = FrontendTheme::find($themeId);
        if ($theme && $theme->active_module_id) {
            $pinned = Module::find($theme->active_module_id);
            if ($pinned && $pinned->active) {
                $name = $pinned->name;
                if ($name !== null && $name !== '') {
                    return $name;
                }
            }
        }

        return $this->getFirstModuleNameWithWebsiteForTheme($themeId);
    }

    /**
     * Modulenaam voor staging-query (?module=): zelfde als {@see getStagingModuleNameForTheme},
     * met fallback naar eerste actieve geïnstalleerde module (sitebreed) als geen module aan dit thema hangt.
     * Zo blijft "Website tonen" in de admin herkenbaar voor staging/branding.
     */
    public function resolveStagingModuleQueryParameterForTheme(int $themeId): ?string
    {
        $name = $this->getStagingModuleNameForTheme($themeId);
        if ($name !== null && $name !== '') {
            return $name;
        }

        foreach ($this->moduleManager->getActiveModules() as $mod) {
            $n = is_object($mod) ? $mod->getName() : ($mod['name'] ?? null);
            if ($n !== null && $n !== '') {
                return $n;
            }
        }

        foreach ($this->moduleManager->getInstalledModules() as $mod) {
            $n = is_object($mod) ? $mod->getName() : ($mod['name'] ?? null);
            if ($n !== null && $n !== '') {
                return $n;
            }
        }

        return Module::where('installed', true)
            ->orderBy('id')
            ->first()?->name;
    }

    /**
     * Eerste actieve module die dit thema gebruikt én minstens één actieve home-pagina heeft.
     * Voor staging-URL "Website tonen" per thema.
     */
    public function getFirstModuleNameWithWebsiteForTheme(int $themeId): ?string
    {
        $candidates = Module::where('frontend_theme_id', $themeId)
            ->where('active', true)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $module) {
            $name = $module->name;
            if ($name === null || $name === '') {
                continue;
            }
            if ($this->moduleHasVisibleHome((string) $name)) {
                return $name;
            }
        }

        return $candidates->first()?->name;
    }

    /**
     * Modulenaam (key) van de actieve frontend-module (branding), voor filtering in o.a. componenten-overzicht.
     */
    public function getActiveModuleName(): ?string
    {
        $module = $this->getBrandingModule();

        return $module ? $module->name : null;
    }

    /**
     * Homepagina voor de startpagina: uit module-DB als actieve module die eigen DB heeft, anders hoofddatabase.
     * Geen filter op thema: er is één homepagina (per module); we tonen die in het actieve thema.
     */
    public function getHomePage(): ?WebsitePage
    {
        return $this->getHomePageForModule(null);
    }

    /**
     * Homepagina voor een specifieke module (of branding-module als null).
     */
    public function getHomePageForModule(?string $moduleName = null): ?WebsitePage
    {
        if ($moduleName === null) {
            $brandingModule = $this->getBrandingModule();
            $moduleName = $brandingModule ? $brandingModule->name : null;
        }

        $query = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'home');
        $this->orderWebsiteHomePagesForTenant($query);
        $page = $this->ensureWebsitePageOnDefaultConnection($query->first());
        if ($page !== null) {
            return $page;
        }

        $fallback = $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'home');
        $this->orderWebsiteHomePagesForTenant($fallback);

        return $this->ensureWebsitePageOnDefaultConnection($fallback->first());
    }

    /**
     * Boekingsmodule-config van de tenant-home (zelfde aanbiedingen/logica als op de website).
     *
     * @return array{config: array, tenant_company_id: ?int, page: ?\App\Models\WebsitePage}
     */
    public function resolveBookingModuleSection(
        string $sectionKey = 'component:taxi.boekingsmodule',
        ?string $moduleName = 'taxi'
    ): array {
        $pricing = app(NexaTaxiBookingPricingService::class);
        $fallback = [
            'config' => $pricing->getDefaultSectionConfig(),
            'tenant_company_id' => $this->resolvedPublicTenantCompanyId(),
            'page' => null,
        ];

        $page = $this->getHomePageForModule($moduleName);
        if ($page === null) {
            return $fallback;
        }

        $homeSections = $page->getHomeSections();
        $raw = $homeSections[$sectionKey] ?? [];
        if (! is_array($raw) || $raw === []) {
            $raw = $homeSections['component:taxiroyaal.boekingsmodule'] ?? [];
        }

        $tenantCompanyId = $page->company_id !== null && (int) $page->company_id > 0
            ? (int) $page->company_id
            : $this->resolvedPublicTenantCompanyId();

        return [
            'config' => $pricing->mergeSectionConfig(is_array($raw) ? $raw : []),
            'tenant_company_id' => $tenantCompanyId,
            'page' => $page,
        ];
    }

    /**
     * Copyrightregel van de tenant-home (zelfde tekst als website-footer op de homepage).
     */
    public function resolvePortalCopyright(?string $moduleName = null): ?string
    {
        $moduleName = $moduleName !== null && $moduleName !== '' ? $moduleName : 'taxi';

        $companyId = $this->resolvedPublicTenantCompanyId();
        if (($companyId === null || $companyId <= 0) && auth()->check()) {
            $userCompanyId = auth()->user()->company_id;
            if ($userCompanyId) {
                $companyId = (int) $userCompanyId;
            }
        }

        if ($companyId !== null && $companyId > 0) {
            $copyright = $this->withResolvedTenant($companyId, function () use ($moduleName) {
                return $this->copyrightFromPortalHomePageSources($moduleName);
            });
            if ($copyright !== null) {
                return $copyright;
            }
        }

        return $this->copyrightFromPortalHomePageSources($moduleName);
    }

    protected function copyrightFromPortalHomePageSources(string $moduleName): ?string
    {
        $booking = $this->resolveBookingModuleSection('component:taxi.boekingsmodule', $moduleName);
        $pages = [];
        if (! empty($booking['page']) && $booking['page'] instanceof WebsitePage) {
            $pages[] = $booking['page'];
        }

        foreach ($this->homePageModuleCandidatesForPortal($moduleName) as $candidateModule) {
            $homePage = $this->getHomePageForModule($candidateModule);
            if ($homePage !== null) {
                $pages[] = $homePage;
            }
        }

        $seen = [];
        foreach ($pages as $homePage) {
            $id = (int) $homePage->id;
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;

            $copyright = $this->copyrightTextFromHomePage($homePage);
            if ($copyright !== null) {
                return $copyright;
            }
        }

        return null;
    }

    protected function copyrightTextFromHomePage(WebsitePage $homePage): ?string
    {
        $copyright = trim((string) ($homePage->getHomeSections()['copyright'] ?? ''));
        if ($copyright === '') {
            return null;
        }

        return str_replace('{year}', date('Y'), $copyright);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function withResolvedTenant(int $companyId, callable $callback): mixed
    {
        $hadTenant = app()->bound('resolved_tenant');
        $hadTenantId = app()->bound('resolved_tenant_id');
        $prevTenant = $hadTenant ? app('resolved_tenant') : null;
        $prevTenantId = $hadTenantId ? app('resolved_tenant_id') : null;

        $company = Company::query()->find($companyId);
        if ($company !== null) {
            app()->instance('resolved_tenant', $company);
            app()->instance('resolved_tenant_id', $companyId);
        }

        try {
            return $callback();
        } finally {
            if ($hadTenant) {
                app()->instance('resolved_tenant', $prevTenant);
            } else {
                app()->forgetInstance('resolved_tenant');
            }
            if ($hadTenantId) {
                app()->instance('resolved_tenant_id', $prevTenantId);
            } else {
                app()->forgetInstance('resolved_tenant_id');
            }
        }
    }

    /**
     * Footer-secties van de tenant-home (zelfde bron als frontend home).
     */
    public function getHomeFooterSections(?string $moduleName = null): array
    {
        $homePage = $this->getHomePageForModule($moduleName);
        if ($homePage === null) {
            return [];
        }

        $sections = $homePage->getHomeSections();
        $footerVisible = (bool) ($sections['visibility']['footer'] ?? true);
        $copyright = trim((string) ($sections['copyright'] ?? ''));

        if ($footerVisible && $copyright !== '') {
            return $sections;
        }

        $theme = $this->getThemeForPage($homePage);
        $themeSlug = $theme ? $theme->slug : 'modern';
        if (! in_array($themeSlug, ['modern', 'atom-v2', 'nextly-template', 'next-landing-vpn'], true)) {
            return [];
        }

        if ($footerVisible && ! empty($sections['footer'])) {
            return $sections;
        }

        return [];
    }

    /**
     * @return list<string|null>
     */
    protected function homePageModuleCandidatesForPortal(?string $moduleName): array
    {
        $candidates = [null];
        if ($moduleName !== null && $moduleName !== '') {
            $candidates[] = $moduleName;
        }

        return array_values(array_unique($candidates, SORT_REGULAR));
    }

    /**
     * True wanneer er een tenant-home is met inhoud die op inactief staat, waardoor de live site
     * niet via {@see getHomePage()} naar de website-builder gaat.
     */
    public function tenantHasInactiveConfiguredHomePage(?int $companyId = null): bool
    {
        $companyId = $companyId ?? $this->resolvedPublicTenantCompanyId();
        if ($companyId === null || $this->getHomePage() !== null) {
            return false;
        }

        $brandingModule = $this->getBrandingModuleForResolvedCompany($companyId);
        $moduleName = $brandingModule?->name;
        $table = (new WebsitePage)->getTable();
        $page = $this->websitePageQuery($moduleName)
            ->where('page_type', 'home')
            ->where($table.'.company_id', $companyId)
            ->where('is_active', false)
            ->first();

        if ($page === null) {
            return false;
        }

        $sections = $page->home_sections;

        return is_array($sections) && $sections !== [];
    }

    /**
     * Op tenant-hosts: eigen bedrijf-pagina's vóór gedeelde module-pagina's.
     */
    protected function orderWebsitePagesForTenant(Builder $query): void
    {
        $tenantId = $this->resolvedPublicTenantCompanyId();
        if ($tenantId === null) {
            $query->orderBy('sort_order')->orderBy('id');

            return;
        }

        $table = $query->getModel()->getTable();
        $grammar = $query->getGrammar();
        $companyCol = $grammar->wrap($table.'.company_id');
        $query->orderByRaw(
            'CASE WHEN '.$companyCol.' = ? THEN 0 WHEN '.$companyCol.' IS NULL THEN 1 ELSE 2 END',
            [$tenantId]
        )
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @deprecated Use {@see orderWebsitePagesForTenant()} */
    protected function orderWebsiteHomePagesForTenant(Builder $query): void
    {
        $this->orderWebsitePagesForTenant($query);
    }

    /**
     * Zet alle website-pagina's van een tenant op actief (hoofd- én module-connection indien van toepassing).
     */
    public function publishTenantWebsitePages(int $companyId): int
    {
        if ($companyId <= 0) {
            return 0;
        }

        $updated = 0;
        foreach ($this->tenantWebsitePageConnectionNames() as $conn) {
            $updated += WebsitePage::on($conn)
                ->where('company_id', $companyId)
                ->where('is_active', false)
                ->update(['is_active' => true]);
        }

        return $updated;
    }

    /**
     * Inactieve tenant-pagina's die op de live site ontbreken (menu of footer-slugs).
     *
     * @return Collection<int, WebsitePage>
     */
    public function getInactiveTenantWebsitePages(?int $companyId = null): Collection
    {
        $companyId = $companyId ?? $this->resolvedPublicTenantCompanyId();
        if ($companyId === null) {
            return collect();
        }

        $pages = collect();
        foreach ($this->tenantWebsitePageConnectionNames() as $conn) {
            $chunk = WebsitePage::on($conn)
                ->where('company_id', $companyId)
                ->where('is_active', false)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
            foreach ($chunk as $page) {
                if (! $pages->contains(fn (WebsitePage $p) => $p->slug === $page->slug && $p->page_type === $page->page_type)) {
                    $pages->push($page);
                }
            }
        }

        return $pages->values();
    }

    public function tenantHasInactiveWebsitePages(?int $companyId = null): bool
    {
        return $this->getInactiveTenantWebsitePages($companyId)->isNotEmpty();
    }

    /**
     * @return list<string>
     */
    private function tenantWebsitePageConnectionNames(): array
    {
        $connections = [(string) config('database.default')];
        if ($this->moduleDb && $this->moduleDb->supportsModuleDatabases()) {
            foreach (Module::where('installed', true)->pluck('name') as $name) {
                if ($name === null || $name === '') {
                    continue;
                }
                $conn = $this->moduleDb->getModuleConnectionName((string) $name);
                if (Config::has("database.connections.{$conn}") && ! in_array($conn, $connections, true)) {
                    $connections[] = $conn;
                }
            }
        }

        return $connections;
    }

    protected function firstActiveWebsitePage(Builder $query): ?WebsitePage
    {
        $this->orderWebsitePagesForTenant($query);

        return $this->ensureWebsitePageOnDefaultConnection($query->first());
    }

    /**
     * Marketing-landingspagina voor het centrale domein (geen tenant). Geen tenant-scope: vaste slug + null company.
     */
    public function getCentralMarketingWelcomePage(): ?WebsitePage
    {
        $q = WebsitePage::query()
            ->where('slug', WebsitePage::CENTRAL_WELCOME_SLUG)
            ->whereNull('module_name')
            ->where('is_active', true);
        $table = (new WebsitePage)->getTable();
        if (Schema::hasColumn($table, 'company_id')) {
            $q->whereNull($table.'.company_id');
        }

        return $q->first();
    }

    /**
     * About-pagina: eerst uit actieve/branding module, anders uit core (module_name null).
     */
    public function getAboutPage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->firstActiveWebsitePage(
            $this->websitePageQuery($moduleName)->active()->where('page_type', 'about')
        );
        if ($page !== null) {
            return $page;
        }

        return $this->firstActiveWebsitePage(
            $this->websitePageQuery(null)->active()->forModule(null)->where('page_type', 'about')
        );
    }

    /**
     * Contactpagina: eerst uit actieve/branding module (frontend pagina's), anders uit core.
     * Zo overruleert de contactpagina uit de module de statische Nexa Skillmatching contactpagina.
     */
    public function getContactPage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->firstActiveWebsitePage(
            $this->websitePageQuery($moduleName)->active()->where('page_type', 'contact')
        );
        if ($page !== null) {
            return $page;
        }

        return $this->firstActiveWebsitePage(
            $this->websitePageQuery(null)->active()->forModule(null)->where('page_type', 'contact')
        );
    }

    public function getPageBySlug(string $slug): ?WebsitePage
    {
        if (WebsitePage::isCentralMarketingWelcomeSlug($slug)) {
            return null;
        }
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->firstActiveWebsitePage(
            $this->websitePageQuery($moduleName)->active()->where('slug', $slug)
        );
        if (! $page && $moduleName !== null) {
            $page = $this->firstActiveWebsitePage(
                $this->websitePageQuery(null)->active()->where('slug', $slug)
            );
        }
        if (! $page) {
            return null;
        }
        if ($page->module_name !== null && ! $this->moduleManager->isActive($page->module_name)) {
            return null;
        }

        return $page;
    }

    /**
     * Pagina's voor het hoofdmenu: alle actieve pagina's voor de huidige module in sort_order.
     * Als er een module met eigen DB is: module-pagina's + ontbrekende home/about/contact uit core,
     * zodat o.a. Contact altijd in het menu staat als die alleen in de hoofddatabase bestaat.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getActiveMenuPages(): Collection
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;

        $corePages = $this->websitePageQuery(null)->active()
            ->whereNull('module_name')
            ->showInMenu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $hasModuleDb = $moduleName !== null && $moduleName !== ''
            && $this->moduleDb
            && $this->moduleDb->supportsModuleDatabases();

        if (! $hasModuleDb) {
            if ($moduleName !== null && $moduleName !== '') {
                $moduleOnly = $this->websitePageQuery($moduleName)->active()->showInMenu();
                $this->orderWebsitePagesForTenant($moduleOnly);

                return $this->excludeCentralWelcomeFromMenu($moduleOnly->get());
            }

            return $this->excludeCentralWelcomeFromMenu($corePages);
        }

        $moduleQuery = $this->websitePageQuery($moduleName)->active()->showInMenu();
        $this->orderWebsitePagesForTenant($moduleQuery);
        $modulePages = $moduleQuery->get();

        $coreTypes = ['home', 'about', 'contact'];
        $moduleHasType = $modulePages->keyBy('page_type');

        foreach ($corePages as $core) {
            if (in_array($core->page_type, $coreTypes, true)
                && ! $moduleHasType->has($core->page_type)) {
                $modulePages->push($core);
            }
        }

        return $this->excludeCentralWelcomeFromMenu(
            $modulePages->sortBy(['sort_order', 'id'])->values()
        );
    }

    /**
     * Centrale marketing-welkom (slug nexa-centraal-welkom) hoort niet in het hoofdmenu;
     * de paginatitel blijft beschikbaar voor &lt;title&gt; in de layout.
     *
     * @param  Collection<int, WebsitePage>  $pages
     * @return Collection<int, WebsitePage>
     */
    private function excludeCentralWelcomeFromMenu(Collection $pages): Collection
    {
        return $pages->filter(function (WebsitePage $p) {
            return ! WebsitePage::isCentralMarketingWelcomeSlug($p->slug);
        })->values();
    }

    /**
     * Pagina's voor het staging-menu: alleen voor gegeven module (uit module-DB), of alle actieve menu-pagina's.
     *
     * @return Collection<int, WebsitePage>
     */
    public function getMenuPagesForStaging(?string $moduleName): Collection
    {
        if ($moduleName !== null && $moduleName !== '') {
            return $this->excludeCentralWelcomeFromMenu(
                $this->websitePageQuery($moduleName)->active()
                    ->showInMenu()
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->get()
            );
        }

        return $this->getActiveMenuPages();
    }

    /**
     * Contact-emailtemplate ID uit instellingen (GeneralSetting).
     */
    public function getContactEmailTemplateId(): ?int
    {
        $id = GeneralSetting::get('contact_email_template_id');
        if ($id === null || $id === '') {
            return null;
        }

        return (int) $id;
    }

    /**
     * Bestaat `website_pages` echt in het schema/database van deze module-connectie?
     *
     * Bij schema-strategy heeft elke module-connectie `search_path = "<module_schema>,public"`. Zonder eigen
     * `website_pages` valt PG terug op `public.website_pages` en zou een query op die connectie de hoofd-DB
     * rijen retourneren. Daarom controleren we hier eerst of de tabel daadwerkelijk in het module-schema
     * (of de module-DB bij `database`-strategy) staat.
     */
    protected function moduleConnectionHasOwnWebsitePagesTable(string $moduleName, string $connection): bool
    {
        try {
            $driver = (string) (Config::get("database.connections.{$connection}.driver") ?? '');

            if ($this->moduleDb->usesSchemaStrategy() && $driver === 'pgsql') {
                $schema = $this->moduleDb->getModuleSchemaName($moduleName);
                $row = DB::connection($connection)->selectOne(
                    'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                    [$schema, 'website_pages']
                );

                return $row !== null;
            }

            return Schema::connection($connection)->hasTable('website_pages');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Kernpagina's uit hoofddatabase (module_name null) + alle module-pagina's.
     * Bij per-module databases: uit elke geïnstalleerde module-DB. Bij single-DB: zelfde DB, module_name gezet.
     * Zelfde bron als Admin → Website-pagina's index.
     *
     * @param  int|null  $restrictToTenantCompanyId  Super-admin met gekozen tenant/wizard: filter op dat bedrijf. Null = alle rijen.
     * @param  bool  $strictTenantCompanyPagesOnly  True = alleen pagina's met dit company_id (admin-index bij tenant-switch). False = zichtbaarheid zoals op de publieke site (eigen + gedeelde module-rijen).
     * @return Collection<int, WebsitePage>
     */
    public function loadAllPagesForAdminIndex(?int $restrictToTenantCompanyId = null, bool $strictTenantCompanyPagesOnly = false): Collection
    {
        $restrictLinkedLower = null;
        $linkedModuleNamesLower = null;
        if ($restrictToTenantCompanyId !== null) {
            $tenantCompany = Company::query()->find($restrictToTenantCompanyId);
            if (! $tenantCompany) {
                return collect();
            }
            $linkedModuleNamesLower = $this->linkedInstalledActiveModuleNamesLowerForCompany($tenantCompany);
            if (! $strictTenantCompanyPagesOnly) {
                $restrictLinkedLower = $linkedModuleNamesLower;
            }
        }

        $applyTenantScope = function (Builder $q) use ($restrictToTenantCompanyId, $restrictLinkedLower, $strictTenantCompanyPagesOnly): void {
            if ($restrictToTenantCompanyId === null) {
                return;
            }
            if ($strictTenantCompanyPagesOnly) {
                $this->applyWebsitePageStrictAdminTenantWhere($q, $restrictToTenantCompanyId);

                return;
            }
            $this->applyWebsitePageVisibilityWhereForCompany($q, $restrictToTenantCompanyId, $restrictLinkedLower ?? []);
        };

        $kernelQuery = WebsitePage::query()
            ->whereNull('module_name')
            ->with('theme');
        $applyTenantScope($kernelQuery);
        $kernel = $kernelQuery
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        if (! $this->moduleDb->supportsModuleDatabases()) {
            $moduleQuery = WebsitePage::query()
                ->whereNotNull('module_name')
                ->with('theme');
            if ($linkedModuleNamesLower !== null) {
                if ($linkedModuleNamesLower === []) {
                    $moduleQuery->whereRaw('1 = 0');
                } else {
                    $moduleQuery->whereIn(DB::raw('LOWER(module_name)'), $linkedModuleNamesLower);
                }
            }
            $applyTenantScope($moduleQuery);
            $modulePagesOnMain = $moduleQuery
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            return $kernel->concat($modulePagesOnMain)->sortBy([
                ['sort_order', 'asc'],
                ['title', 'asc'],
            ])->values();
        }

        $modulePages = collect();
        foreach (Module::where('installed', true)->pluck('name') as $moduleName) {
            if ($moduleName === null || $moduleName === '') {
                continue;
            }
            if ($linkedModuleNamesLower !== null
                && ! in_array(strtolower((string) $moduleName), $linkedModuleNamesLower, true)) {
                continue;
            }
            $conn = $this->moduleDb->getModuleConnectionName($moduleName);
            if (! Config::has("database.connections.{$conn}")) {
                try {
                    $this->moduleDb->registerConnection($moduleName);
                } catch (\Throwable) {
                    continue;
                }
            }
            if (! Config::has("database.connections.{$conn}")) {
                continue;
            }
            // Geen eigen website_pages in het module-schema: rijen staan in public.website_pages (module_name gezet).
            // Die hier ophalen vanuit de hoofddatabase — niet overslaan, anders ontbreken alle module-pagina's in de admin-index.
            if (! $this->moduleConnectionHasOwnWebsitePagesTable($moduleName, $conn)) {
                try {
                    $onSharedMain = WebsitePage::query()->with('theme')
                        ->whereRaw('LOWER(module_name) = ?', [strtolower((string) $moduleName)]);
                    $applyTenantScope($onSharedMain);
                    $modulePages = $modulePages->concat(
                        $onSharedMain
                            ->orderBy('sort_order')
                            ->orderBy('title')
                            ->get()
                    );
                } catch (\Throwable) {
                    continue;
                }

                continue;
            }
            try {
                $onModule = WebsitePage::on($conn)->with('theme');
                $applyTenantScope($onModule);
                // Defensief: alleen rijen die echt bij deze module horen — zo glippen kernrijen
                // (module_name IS NULL) niet alsnog mee via een eventuele search_path-fallback.
                $onModule->whereRaw('LOWER(module_name) = ?', [strtolower((string) $moduleName)]);
                $modulePages = $modulePages->concat(
                    $onModule
                        ->orderBy('sort_order')
                        ->orderBy('title')
                        ->get()
                );
            } catch (\Throwable) {
                continue;
            }
        }

        return $kernel->concat($modulePages)->sortBy([
            ['sort_order', 'asc'],
            ['title', 'asc'],
        ])->values();
    }
}

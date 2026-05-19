<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\WebsitePage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
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
     * Query WebsitePage op de juiste connection: module-DB als actieve module die eigen DB heeft, anders default.
     * Bij single-DB: filter op module_name wanneer een modulenaam is meegegeven.
     * Publieke frontend-requests krijgen een tenant/global scope via {@see applyWebsitePageTenantScope()}.
     */
    private function websitePageQuery(?string $forModuleName = null): Builder
    {
        if ($forModuleName !== null && $forModuleName !== ''
            && $this->moduleDb && $this->moduleDb->supportsModuleDatabases()) {
            $q = WebsitePage::on($this->moduleDb->getModuleConnectionName($forModuleName));
        } else {
            $q = WebsitePage::query();
            if ($forModuleName !== null && $forModuleName !== '') {
                $q->whereRaw('LOWER(module_name) = ?', [strtolower($forModuleName)]);
            }
        }
        $this->applyWebsitePageTenantScope($q);

        return $q;
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
        $defaultConn = (string) config('database.default');
        if (! Schema::connection($connection)->hasColumn($table, 'company_id')
            && ! Schema::connection($defaultConn)->hasColumn($table, 'company_id')) {
            return;
        }
        $query->where($table.'.company_id', $tenantCompanyId);
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
     * Bepaalt het thema waarmee een website-pagina wordt getoond: altijd het actieve thema.
     * Pagina-inhoud (componenten) blijft gelijk; alleen de styling past zich aan het actieve thema aan.
     */
    public function getThemeForPage(WebsitePage $page): ?FrontendTheme
    {
        return $this->getActiveTheme();
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
     * @return array{logo_url: ?string, logo_dark_url: ?string, logo_size_px: int, favicon_url: ?string, site_name: string, site_description: string, dashboard_link_label: string, dashboard_link_visible: bool}
     */
    public function getSiteBranding(?string $forModuleName = null, bool $forStagingPreview = false): array
    {
        $logoSizePx = $this->resolveLogoSizePx();

        $logoPath = GeneralSetting::get('logo');
        $logoUrl = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $logoUrl = $this->publicFileUrl(ltrim($logoPath, '/'));
        }
        $logoDarkUrl = null;
        $logoMode = GeneralSetting::get('logo_mode', 'single');
        if ($logoMode === 'light_dark') {
            $logoDarkPath = GeneralSetting::get('logo_dark');
            if ($logoDarkPath && Storage::disk('public')->exists($logoDarkPath)) {
                $logoDarkUrl = $this->publicFileUrl(ltrim($logoDarkPath, '/'));
            }
        }

        $faviconPath = GeneralSetting::get('favicon');
        $faviconUrl = null;
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            $faviconUrl = $this->publicFileUrl(ltrim($faviconPath, '/'));
        }

        $siteName = GeneralSetting::get('site_name', config('app.name', 'Nexa'));
        $siteDescription = GeneralSetting::get('site_description', '');
        $dashboardLinkLabel = GeneralSetting::get('dashboard_link_label', 'Mijn Nexa');
        $dashboardLinkVisible = GeneralSetting::get('dashboard_link_visible', '1') === '1';

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

        $this->applyCompanyLogoFallback($logoUrl, $logoDarkUrl);

        return [
            'logo_url' => $logoUrl,
            'logo_dark_url' => $logoDarkUrl,
            'logo_size_px' => $logoSizePx,
            'favicon_url' => $faviconUrl,
            'site_name' => $siteName,
            'site_description' => $siteDescription,
            'dashboard_link_label' => $dashboardLinkLabel,
            'dashboard_link_visible' => (bool) $dashboardLinkVisible,
        ];
    }

    /**
     * Logo-hoogte uit Algemene instellingen (zelfde bereik als admin #logo_size).
     */
    public function resolveLogoSizePx(): int
    {
        $raw = GeneralSetting::get('logo_size', '26');
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
    private function applyCompanyLogoFallback(?string &$logoUrl, ?string &$logoDarkUrl): void
    {
        if ($logoUrl !== null && $logoUrl !== '') {
            return;
        }

        $companyId = $this->resolveBrandingCompanyId();
        if ($companyId === null) {
            return;
        }

        $company = Company::query()->find($companyId);
        if (! $company || ! $company->logo_blob) {
            return;
        }

        $logoUrl = route('frontend.company-brand.logo', $company);
        if ($company->logo_dark_blob) {
            $logoDarkUrl = route('frontend.company-brand.logo.dark', $company);
        }
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
     * Zet een opgeslagen storage-URL (relatief of volledig) om naar een werkende weergave-URL via /file/.
     * Gebruik overal waar img src of background-image uit de database komt (bv. /storage/vehicles/..., http://.../storage/...).
     */
    public function storageUrlToDisplayUrl(?string $url): string
    {
        if ($url === null || trim($url) === '') {
            return '';
        }
        $u = trim((string) $url);
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

        $activeTheme = $this->getActiveTheme();
        if ($activeTheme && $activeTheme->active_module_id) {
            $pinned = $mods->firstWhere('id', (int) $activeTheme->active_module_id);
            if ($pinned && $pinned->active) {
                $name = $pinned->name;
                if ($name !== null && $name !== '') {
                    return $pinned;
                }
            }
        }

        if ($activeTheme) {
            foreach ($mods->where('frontend_theme_id', $activeTheme->id) as $module) {
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

    public function getActiveTheme(): ?FrontendTheme
    {
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
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $query = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->orderBy('id');
        $page = $query->first();
        if ($page !== null) {
            return $page;
        }

        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'home')
            ->orderBy('sort_order')
            ->first();
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
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
        if ($page !== null) {
            return $page;
        }

        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'about')
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * Contactpagina: eerst uit actieve/branding module (frontend pagina's), anders uit core.
     * Zo overruleert de contactpagina uit de module de statische Nexa Skillmatching contactpagina.
     */
    public function getContactPage(): ?WebsitePage
    {
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('page_type', 'contact')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
        if ($page !== null) {
            return $page;
        }

        return $this->websitePageQuery(null)->active()
            ->forModule(null)
            ->where('page_type', 'contact')
            ->orderBy('sort_order')
            ->first();
    }

    public function getPageBySlug(string $slug): ?WebsitePage
    {
        if (WebsitePage::isCentralMarketingWelcomeSlug($slug)) {
            return null;
        }
        $brandingModule = $this->getBrandingModule();
        $moduleName = $brandingModule ? $brandingModule->name : null;
        $page = $this->websitePageQuery($moduleName)->active()
            ->where('slug', $slug)
            ->first();
        if (! $page && $moduleName !== null) {
            $page = $this->websitePageQuery(null)->active()
                ->where('slug', $slug)
                ->first();
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
            return $this->excludeCentralWelcomeFromMenu($corePages);
        }

        $modulePages = $this->websitePageQuery($moduleName)->active()
            ->showInMenu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

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
        if ($restrictToTenantCompanyId !== null) {
            $tenantCompany = Company::query()->find($restrictToTenantCompanyId);
            if (! $tenantCompany) {
                return collect();
            }
            if (! $strictTenantCompanyPagesOnly) {
                $restrictLinkedLower = $this->linkedInstalledActiveModuleNamesLowerForCompany($tenantCompany);
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

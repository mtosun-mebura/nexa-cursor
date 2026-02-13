<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\Vacancy;
use App\Models\WebsitePage;
use App\Services\ModuleManager;
use App\Services\ModuleThemePageService;
use Illuminate\Support\Facades\Cache;
use App\Services\WebsiteBuilderService;
use Database\Seeders\FrontendThemeSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminFrontendThemeController extends Controller
{
    public function __construct(
        protected ModuleManager $moduleManager,
        protected WebsiteBuilderService $websiteBuilder,
        protected ModuleThemePageService $moduleThemePages
    ) {}

    /**
     * Serve theme preview bestand met correct Content-Type (SVG vaak verkeerd bij php artisan serve).
     */
    public function servePreview(Request $request)
    {
        $this->ensureSuperAdmin();
        $path = $request->query('path');
        if (!$path || !\Illuminate\Support\Str::startsWith($path, 'frontend-themes/')) {
            abort(404);
        }
        $fullPath = public_path($path);
        if (!File::isFile($fullPath) || !File::exists($fullPath)) {
            abort(404);
        }
        $ext = strtolower(File::extension($fullPath));
        $mimes = [
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mime = $mimes[$ext] ?? 'application/octet-stream';
        return response(File::get($fullPath), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function showSetup()
    {
        $this->ensureSuperAdmin();
        $activeTheme = FrontendTheme::getActive();
        $setup = [
            'php_version' => PHP_VERSION,
            'laravel_version' => \Illuminate\Foundation\Application::VERSION,
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
            'active_theme' => $activeTheme ? $activeTheme->name : '—',
            'theme_slug' => $activeTheme ? $activeTheme->slug : null,
            'extensions' => array_filter(get_loaded_extensions(), fn ($e) => !str_starts_with($e, 'xdebug')),
        ];
        sort($setup['extensions']);
        return view('admin.frontend-themes.setup', compact('setup'));
    }

    public function index()
    {
        $this->ensureSuperAdmin();
        // Zorg dat alle thema's (Modern + Atom v2, Nextly, Next Landing VPN) bestaan
        $newSlugs = ['atom-v2', 'nextly-template', 'next-landing-vpn'];
        if (FrontendTheme::count() === 0 || FrontendTheme::whereIn('slug', $newSlugs)->count() < count($newSlugs)) {
            (new FrontendThemeSeeder())->run();
        }
        // Zorg dat thema Modern de NEXA Home-screenshot gebruikt
        $modern = FrontendTheme::where('slug', 'modern')->first();
        $modernPreviewPath = 'frontend-themes/modern-home.png';
        if ($modern && file_exists(public_path($modernPreviewPath))) {
            if ($modern->preview_path !== $modernPreviewPath) {
                $modern->update(['preview_path' => $modernPreviewPath]);
            }
        }
        $themes = FrontendTheme::orderBy('slug')->get();
        $installedModules = $this->moduleManager->getInstalledModules();
        $activeTheme = FrontendTheme::getActive();
        $activeThemeId = $activeTheme ? $activeTheme->id : $themes->first()?->id;

        // Modulemodels opzoeken met dezelfde case-insensitive lookup als bij opslaan (key = getName())
        $moduleModels = collect();
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
                $moduleModels->put($name, $moduleModel);
            }
        }

        $stagingUrlTop = $activeThemeId
            ? route('admin.frontend-themes.staging', ['theme_id' => $activeThemeId, 'module' => ''])
            : null;

        $moduleFirstPageUrls = [];
        $moduleStagingUrls = [];
        foreach ($installedModules as $module) {
            $moduleName = $module->getName();
            $firstPage = WebsitePage::active()
                ->forModule($moduleName)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->first();
            $moduleFirstPageUrls[$moduleName] = $firstPage ? $this->urlForWebsitePage($firstPage) : null;
            $moduleModel = $moduleModels->get($moduleName);
            $themeId = $moduleModel?->frontend_theme_id ?? $activeThemeId;
            $moduleStagingUrls[$moduleName] = $themeId
                ? route('admin.frontend-themes.staging', ['theme_id' => $themeId, 'module' => $moduleName])
                : $stagingUrlTop;
        }

        $websiteUrl = url('/');
        return view('admin.frontend-themes.index', compact(
            'themes', 'installedModules', 'moduleModels', 'moduleFirstPageUrls', 'moduleStagingUrls',
            'websiteUrl', 'stagingUrlTop', 'activeThemeId'
        ));
    }

    /**
     * URL voor een website-pagina (home, about, contact of slug).
     */
    private function urlForWebsitePage(WebsitePage $page): string
    {
        return match ($page->page_type) {
            'home' => route('home'),
            'about' => route('about'),
            'contact' => route('contact'),
            default => route('website.page', ['slug' => $page->slug]),
        };
    }

    /**
     * Staging-pagina: gekozen thema met pagina's (en functionaliteit) van de module.
     * GET admin/frontend-themes/staging?theme_id=1&module=Skillmatching&page=home
     */
    public function staging(Request $request): View|RedirectResponse|Response
    {
        $this->ensureSuperAdmin();
        $module = $request->query('module');
        $pageParam = $request->query('page');

        // Thema strikt uit query: alleen bij aanwezige theme_id dat thema laden, anders actieve
        $theme = null;
        $requestedThemeId = $request->query('theme_id');
        if ($requestedThemeId !== null && $requestedThemeId !== '') {
            $theme = FrontendTheme::find((int) $requestedThemeId);
        }
        if (!$theme) {
            $theme = FrontendTheme::getActive();
        }
        if (!$theme) {
            return redirect()->route('admin.frontend-themes.index')
                ->with('error', 'Geen thema gekozen.');
        }

        $menuPages = $this->websiteBuilder->getMenuPagesForStaging($module ?: null);
        $menuPages = $menuPages->values();

        $page = null;
        if ($pageParam) {
            $page = $menuPages->first(fn (WebsitePage $p) =>
                $p->page_type === $pageParam || $p->slug === $pageParam
            );
        }
        if (!$page && $menuPages->isNotEmpty()) {
            // Zonder page-parameter: toon home zodat staging o.a. recente vacatures toont (modern thema)
            $page = $menuPages->first(fn (WebsitePage $p) => $p->page_type === 'home' || $p->slug === 'home')
                ?? $menuPages->first();
        }
        // Geen actieve pagina's: toon demo-home voor het gekozen thema zodat het thema altijd bekeken kan worden
        if (!$page) {
            $page = WebsitePage::demoPageForTheme($theme, $module ?: null);
            $menuPages = collect([$page]);
        }

        $themeSlug = $theme->slug;
        $themeSettings = $theme->getSettings();
        $branding = $this->websiteBuilder->getSiteBranding();
        $showContactForm = $page->page_type === 'contact';

        $stagingParams = [
            'theme_id' => $theme->id,
            'module' => $module ?? '',
        ];

        $jobs = collect();
        $isHomePage = $page->page_type === 'home' || $page->slug === 'home';
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
        $useThemeHomeLayout = $themeHasHomeSections && (
            !empty($page->home_sections) || $page->page_type === 'home' || $page->slug === 'home'
        );
        $homeSections = $useThemeHomeLayout ? $page->getHomeSections() : [];
        // Atom v2: laad thema-styles op alle paginatypes (staging) voor dezelfde weergave als home
        $loadAtomV2Styles = ($themeSlug === 'atom-v2');

        $response = response()->view('frontend.website.page', [
            'page' => $page,
            'theme' => $theme,
            'themeSlug' => $themeSlug,
            'themeSettings' => $themeSettings,
            'menuPages' => $menuPages,
            'branding' => $branding,
            'showContactForm' => $showContactForm,
            'isStaging' => true,
            'stagingThemeId' => $theme->id,
            'stagingModule' => $module ?? '',
            'stagingParams' => $stagingParams,
            'stagingBackUrl' => route('admin.frontend-themes.index'),
            'stagingPublishUrl' => route('admin.frontend-themes.publish'),
            'stagingRequestedThemeId' => $requestedThemeId,
            'jobs' => $jobs,
            'homeSections' => $homeSections,
            'useModernHomeLayout' => $useThemeHomeLayout,
            'loadAtomV2Styles' => $loadAtomV2Styles,
        ]);

        // Staging mag niet gecached worden: bij ander thema moet direct het nieuwe thema getoond worden
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        $response->header('X-Staging-Theme-Id', (string) $theme->id);
        $response->header('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Publiceren: thema actief maken en demo-/staging-URL's in content omzetten naar daadwerkelijke URL's.
     */
    public function publish(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $request->validate(['theme_id' => 'required|exists:frontend_themes,id']);

        $theme = FrontendTheme::findOrFail($request->theme_id);
        FrontendTheme::where('is_active', true)->update(['is_active' => false]);
        $theme->update(['is_active' => true]);

        $stagingBasePath = parse_url(route('admin.frontend-themes.staging', [], true), PHP_URL_PATH);
        $productionBase = rtrim(config('app.url'), '/');

        $updated = 0;
        foreach (WebsitePage::all() as $websitePage) {
            if (!$websitePage->content || !is_string($websitePage->content)) {
                continue;
            }
            $original = $websitePage->content;
            $content = $this->replaceStagingUrlsWithProduction($original, $stagingBasePath, $productionBase);
            if ($content !== $original) {
                $websitePage->update(['content' => $content]);
                $updated++;
            }
        }

        $message = "Thema \"{$theme->name}\" is gepubliceerd en actief.";
        if ($updated > 0) {
            $message .= " In {$updated} pagina('s) zijn staging-URL's omgezet naar de daadwerkelijke URL's.";
        }
        return redirect()->route('admin.frontend-themes.index')->with('success', $message);
    }

    /**
     * Vervang staging-URL's in content door daadwerkelijke frontend-URL's (op basis van page-parameter).
     */
    private function replaceStagingUrlsWithProduction(string $content, string $stagingBasePath, string $productionBase): string
    {
        $pattern = '#https?://[^\s"\'<>\]\)]+' . preg_quote($stagingBasePath, '#') . '[^\s"\'<>\]\)]*#';
        return (string) preg_replace_callback($pattern, function (array $m) use ($productionBase): string {
            $url = $m[0];
            if (preg_match('/[?&]page=([^&\s"\'\]\)]+)/', $url, $pageMatch)) {
                $page = $pageMatch[1];
                if ($page === 'home') {
                    return $productionBase . '/' . ltrim(route('home', [], false), '/');
                }
                if ($page === 'about') {
                    return $productionBase . '/' . ltrim(route('about', [], false), '/');
                }
                if ($page === 'contact') {
                    return $productionBase . '/' . ltrim(route('contact', [], false), '/');
                }
                return $productionBase . '/' . ltrim(route('website.page', ['slug' => $page], false), '/');
            }
            return $productionBase;
        }, $content);
    }

    /**
     * Thema voor een module vastleggen. Bij keuze van een thema worden de thema-bestanden
     * naar de module gekopieerd. Huidige pagina's van het oude thema worden op inactief gezet
     * (niet verwijderd); pagina's van het gekozen thema worden weer actief; ontbrekende
     * home-pagina voor het nieuwe thema wordt aangemaakt met dezelfde opzet als de bestaande.
     */
    public function updateModuleTheme(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'module_name' => 'required|string|max:255',
            'frontend_theme_id' => 'nullable|exists:frontend_themes,id',
        ]);
        $module = Module::where('installed', true)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->module_name))])
            ->firstOrFail();
        $oldThemeId = $module->frontend_theme_id;
        $newThemeId = $request->input('frontend_theme_id') ? (int) $request->input('frontend_theme_id') : null;

        $module->update(['frontend_theme_id' => $newThemeId]);

        $this->moduleThemePages->syncPagesForModuleThemeChange($module, $oldThemeId, $newThemeId);

        if ($newThemeId) {
            $theme = FrontendTheme::find($newThemeId);
            if ($theme && $theme->slug) {
                $themeCopy = app(\App\Services\ThemeCopyService::class);
                $themeCopy->copySingleThemeToModule($theme->slug, $module->name);
            }
        }

        return redirect()->route('admin.frontend-themes.index')
            ->with('success', "Thema voor module \"{$module->display_name}\" bijgewerkt. Pagina's van het vorige thema staan op inactief; pagina's van het gekozen thema zijn actief.");
    }

    /**
     * Maak dit thema het actieve standaardthema. Alle website_pages (kern + module):
     * - frontend_theme_id ongelijk aan gekozen thema of null → is_active = false.
     * - frontend_theme_id gelijk aan gekozen thema → is_active = true.
     */
    public function setActive(FrontendTheme $frontend_theme)
    {
        $this->ensureSuperAdmin();
        $chosenThemeId = $frontend_theme->id;

        FrontendTheme::where('is_active', true)->update(['is_active' => false]);
        $frontend_theme->update(['is_active' => true]);

        WebsitePage::query()
            ->where(function ($q) use ($chosenThemeId) {
                $q->where('frontend_theme_id', '!=', $chosenThemeId)
                    ->orWhereNull('frontend_theme_id');
            })
            ->update(['is_active' => false]);

        WebsitePage::where('frontend_theme_id', $chosenThemeId)->update(['is_active' => true]);

        return redirect()->route('admin.frontend-themes.index')->with('success', "Thema \"{$frontend_theme->name}\" is nu actief. Pagina's van andere thema's staan op inactief.");
    }

    public function edit(FrontendTheme $frontend_theme)
    {
        $this->ensureSuperAdmin();
        return view('admin.frontend-themes.edit', compact('frontend_theme'));
    }

    public function update(Request $request, FrontendTheme $frontend_theme)
    {
        $this->ensureSuperAdmin();
        $settings = $frontend_theme->settings ?? [];
        $settings['primary_color'] = $request->input('primary_color', $settings['primary_color'] ?? '#2563eb');
        $settings['font_heading'] = $request->input('font_heading', $settings['font_heading'] ?? 'Inter');
        $settings['font_body'] = $request->input('font_body', $settings['font_body'] ?? 'Inter');
        $settings['footer_text'] = $request->input('footer_text', $settings['footer_text'] ?? '');
        $settings['dark_mode_available'] = $request->boolean('dark_mode_available');
        $frontend_theme->update(['settings' => $settings]);
        return redirect()->route('admin.frontend-themes.index')->with('success', 'Thema-instellingen opgeslagen.');
    }

    protected function ensureSuperAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot frontend-thema\'s.');
        }
    }
}

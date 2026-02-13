<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\Vacancy;
use App\Models\WebsitePage;
use Illuminate\Support\Facades\Cache;
use App\Services\FrontendComponentService;
use App\Services\ModuleManager;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminWebsitePageController extends Controller
{
    public function __construct(
        protected ModuleManager $moduleManager,
        protected WebsiteBuilderService $websiteBuilder
    ) {}

    /**
     * Lijst website-pagina's. Alleen pagina's tonen waarvan het thema gelijk is aan het
     * thema waarin ze zijn opgezet: voor module-pagina's het huidige thema van de module,
     * voor core-pagina's het actieve thema (of geen thema).
     */
    public function index()
    {
        $this->ensureSuperAdmin();
        $activeThemeId = $this->websiteBuilder->getActiveTheme()?->id;
        $moduleThemeIds = $this->getModuleThemeIdsByModuleName();

        $pages = WebsitePage::with('theme')->orderBy('sort_order')->orderBy('title')->get()->filter(function (WebsitePage $page) use ($activeThemeId, $moduleThemeIds) {
            if (!$page->is_active) {
                return false;
            }
            if ($page->module_name === null) {
                return $page->frontend_theme_id === null || (int) $page->frontend_theme_id === (int) $activeThemeId;
            }
            $moduleThemeId = $moduleThemeIds->get(strtolower($page->module_name));
            if ($moduleThemeId === null) {
                $moduleThemeId = $activeThemeId;
            }
            return (int) $page->frontend_theme_id === (int) $moduleThemeId;
        })->values();

        return view('admin.website-pages.index', compact('pages'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();
        $installedModules = $this->moduleManager->getInstalledModules();
        $themes = FrontendTheme::orderBy('slug')->get();
        $defaultTheme = $this->websiteBuilder->getActiveTheme();
        $moduleThemes = $this->getModuleThemesForForm($installedModules);
        return view('admin.website-pages.create', compact('installedModules', 'themes', 'defaultTheme', 'moduleThemes'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();
        $frontendThemeId = $this->resolveFrontendThemeIdFromRequest($request);
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('website_pages', 'slug')->where('frontend_theme_id', $frontendThemeId),
            ],
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'page_type' => 'required|in:home,about,contact,custom,module',
            'module_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if (!empty($data['module_name'])) {
            $module = Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower(trim($data['module_name']))])->first();
            $data['frontend_theme_id'] = $module && $module->frontend_theme_id
                ? $module->frontend_theme_id
                : ($this->websiteBuilder->getActiveTheme()?->id);
        } else {
            $data['module_name'] = null;
            $data['frontend_theme_id'] = null;
        }
        $themeSlug = $data['frontend_theme_id'] ? (FrontendTheme::find($data['frontend_theme_id'])?->slug ?? 'modern') : 'modern';
        $input = $this->getHomeSectionsInput($request);
        if ($data['page_type'] === 'home') {
            $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, false);
        } else {
            $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, true);
        }
        WebsitePage::create($data);
        return redirect()->route('admin.website-pages.index')->with('success', 'Pagina aangemaakt.');
    }

    /**
     * Redirect to edit form (resource has no dedicated show view).
     */
    public function show(WebsitePage $website_page)
    {
        return redirect()->route('admin.website-pages.edit', $website_page);
    }

    public function edit(WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        $installedModules = $this->moduleManager->getInstalledModules();
        $themes = FrontendTheme::orderBy('slug')->get();
        $defaultTheme = $this->websiteBuilder->getActiveTheme();
        $moduleThemes = $this->getModuleThemesForForm($installedModules);
        return view('admin.website-pages.edit', [
            'page' => $website_page,
            'installedModules' => $installedModules,
            'themes' => $themes,
            'defaultTheme' => $defaultTheme,
            'moduleThemes' => $moduleThemes,
        ]);
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
        if (!$theme) {
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
            'type' => 'required|string|in:hero,stats,why_nexa,features,cta,carousel,cards_ronde_hoeken',
            'theme' => 'nullable|string|max:64',
        ]);
        $type = $valid['type'];
        $theme = $request->input('theme', 'modern');
        $defaults = WebsitePage::defaultHomeSectionsForTheme($theme);
        $sections = [
            'section_order' => [$type],
            'visibility' => $defaults['visibility'] ?? [],
            'hero' => $defaults['hero'] ?? [],
            'stats' => $defaults['stats'] ?? [],
            'why_nexa' => $defaults['why_nexa'] ?? [],
            'features' => $defaults['features'] ?? [],
            'cta' => $defaults['cta'] ?? [],
            'carousel' => $defaults['carousel'] ?? ['items' => []],
            'cards_ronde_hoeken' => $defaults['cards_ronde_hoeken'] ?? ['items' => [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']]],
            'footer' => $defaults['footer'] ?? [],
            'copyright' => $defaults['copyright'] ?? '',
        ];
        $html = view('admin.website-pages.partials.home-sections', [
            'homeSections' => $sections,
            'themeSlug' => $theme,
            'isNonHomePage' => false,
            'sectionCardOnly' => true,
        ])->render();

        // Eerste .home-section-card extraheren (bij sectionCardOnly rendert de partial precies één kaart)
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="section-card-wrapper">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
        $branding = $this->websiteBuilder->getSiteBranding();
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
        $useThemeHomeLayout = $themeHasHomeSections && ($website_page->page_type === 'home' || $website_page->slug === 'home' || !empty($website_page->home_sections));
        $homeSections = $useThemeHomeLayout ? $website_page->getHomeSections() : [];
        // Atom v2: laad thema-styles op alle paginatypes (preview) voor dezelfde weergave als home
        $loadAtomV2Styles = ($themeSlug === 'atom-v2');

        return view('frontend.website.page', [
            'page' => $website_page,
            'theme' => $theme,
            'themeSlug' => $themeSlug,
            'themeSettings' => $themeSettings,
            'menuPages' => $menuPages,
            'branding' => $branding,
            'showContactForm' => $website_page->page_type === 'contact',
            'isPreview' => true,
            'previewEditUrl' => route('admin.website-pages.edit', $website_page),
            'jobs' => $jobs,
            'useModernHomeLayout' => $useThemeHomeLayout,
            'homeSections' => $homeSections,
            'loadAtomV2Styles' => $loadAtomV2Styles,
        ]);
    }

    public function update(Request $request, WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        $frontendThemeId = $this->resolveFrontendThemeIdFromRequest($request);
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('website_pages', 'slug')->ignore($website_page->id)->where('frontend_theme_id', $frontendThemeId),
            ],
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'meta_description' => 'nullable|string|max:500',
            'page_type' => 'required|in:home,about,contact,custom,module',
            'module_name' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if (!empty($data['module_name'])) {
            $module = Module::where('installed', true)->whereRaw('LOWER(name) = ?', [strtolower(trim($data['module_name']))])->first();
            $data['frontend_theme_id'] = $module && $module->frontend_theme_id
                ? $module->frontend_theme_id
                : ($this->websiteBuilder->getActiveTheme()?->id);
        } else {
            $data['module_name'] = null;
            $data['frontend_theme_id'] = null;
        }
        $themeSlug = $data['frontend_theme_id'] ? (FrontendTheme::find($data['frontend_theme_id'])?->slug ?? 'modern') : 'modern';
        // home_sections niet meenemen in validate() om BadRequestException te voorkomen (ParameterBag verwacht array;
        // bij PUT kan de structuur anders zijn). We halen het veilig op en normaliseren zelf.
        $input = $this->getHomeSectionsInput($request);
        if (!is_array($input)) {
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
        if (empty($input) && !empty($website_page->home_sections)) {
            $input = $website_page->getHomeSections();
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
        try {
            if ($data['page_type'] === 'home') {
                $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, false);
            } else {
                $data['home_sections'] = $this->normalizeHomeSections($input, $themeSlug, true);
            }
            $website_page->update($data);
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
                ->withErrors(['home_sections' => 'Opslaan mislukt: ' . $e->getMessage()]);
        }
        return redirect()->route('admin.website-pages.index')->with('success', 'Pagina bijgewerkt.');
    }

    /**
     * Haal home_sections uit de request zonder ParameterBag::all($key) aan te roepen
     * (die gooit BadRequestException als de waarde geen array is).
     * Ondersteunt POST (form) en fallback parse van body bij lege request bag.
     */
    private function getHomeSectionsInput(Request $request): array
    {
        // Laravel's input() parsed ook PUT/PATCH body (method spoofing); request->request kan leeg zijn bij PUT
        $raw = $request->input('home_sections');
        if (is_array($raw)) {
            return $raw;
        }
        try {
            $params = $request->request->all();
            $raw = $params['home_sections'] ?? null;
        } catch (\Throwable $e) {
            $raw = null;
        }
        if (is_array($raw)) {
            return $raw;
        }
        // Fallback: body parsen (bij grote forms of edge cases)
        $content = $request->getContent();
        if (is_string($content) && $content !== '') {
            parse_str($content, $parsed);
            $raw = $parsed['home_sections'] ?? null;
            if (is_array($raw)) {
                return $raw;
            }
        }
        return [];
    }

    private const HOME_SECTION_BASE_TYPES = ['hero', 'stats', 'why_nexa', 'features', 'cta', 'carousel', 'cards_ronde_hoeken'];

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
        $sectionOrder = array_values(array_unique(array_merge($sectionOrder, $defaults['section_order'])));

        $sections = [];
        foreach ($sectionOrder as $sectionKey) {
            if (FrontendComponentService::isComponentKey($sectionKey)) {
                $sections[$sectionKey] = [];
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
        if (!empty($footerInput['quick_links']) && is_array($footerInput['quick_links'])) {
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
        if (!empty($footerInput['support_links']) && is_array($footerInput['support_links'])) {
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

        $visibilityInput = $input['visibility'] ?? [];
        $visibility = array_merge($defaults['visibility'], [
            'hero' => !empty($visibilityInput['hero']),
            'stats' => !empty($visibilityInput['stats']),
            'why_nexa' => !empty($visibilityInput['why_nexa']),
            'features' => !empty($visibilityInput['features']),
            'cta' => !empty($visibilityInput['cta']),
            'cards_ronde_hoeken' => !empty($visibilityInput['cards_ronde_hoeken']),
            'footer' => !empty($visibilityInput['footer']),
        ]);
        foreach (array_keys($visibilityInput) as $key) {
            if (preg_match('/^(hero|stats|why_nexa|features|cta|cards_ronde_hoeken)(_[a-z0-9_]+)?$/i', $key)) {
                $visibility[$key] = !empty($visibilityInput[$key]);
            }
        }

        return array_merge($sections, [
            'footer' => $footer,
            'copyright' => is_string($input['copyright'] ?? null) ? trim($input['copyright']) : ($defaults['copyright'] ?? ''),
            'section_order' => $sectionOrder,
            'visibility' => $visibility,
        ]);
    }

    private function normalizeOneHomeSection(array $input, string $sectionKey, string $baseType, array $defaults): array
    {
        $raw = $input[$sectionKey] ?? [];
        if (!is_array($raw)) {
            $raw = [];
        }
        switch ($baseType) {
            case 'hero':
                $data = array_merge($defaults['hero'], $raw);
                $data = $this->sanitizeButtonColors($data);
                $data['overlay'] = !empty($raw['overlay']);
                // Behoud hero-afbeeldingen (atom-v2) ook als leeg, zodat "geen custom" = thema-default
                $keepEmptyKeys = ['overlay', 'background_image_url', 'author_image_url'];
                return array_filter($data, fn ($v, $k) => in_array($k, $keepEmptyKeys, true) ? true : $v !== '' && $v !== null, ARRAY_FILTER_USE_BOTH);
            case 'stats':
                $stats = [];
                if (!empty($raw) && is_array($raw)) {
                    foreach (array_values($raw) as $row) {
                        if (is_array($row) && (isset($row['value']) || isset($row['label']))) {
                            $stats[] = [
                                'value' => $row['value'] ?? '',
                                'label' => $row['label'] ?? '',
                            ];
                        }
                    }
                }
                $defStats = $defaults['stats'];
                while (count($stats) < 4) {
                    $stats[] = $defStats[count($stats)] ?? ['value' => '', 'label' => ''];
                }
                return $stats;
            case 'why_nexa':
                return array_filter(array_merge($defaults['why_nexa'], $raw));
            case 'features':
                $items = [];
                if (!empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $i => $row) {
                        if (is_array($row)) {
                            $items[] = [
                                'title' => $row['title'] ?? '',
                                'description' => $row['description'] ?? '',
                                'icon' => $row['icon'] ?? ($i === 0 ? 'light-bulb' : 'bolt'),
                                'icon_size' => in_array($row['icon_size'] ?? '', ['small', 'medium', 'large'], true) ? $row['icon_size'] : 'medium',
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
                if (!empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $row) {
                        if (is_array($row) && !empty($row['uuid'])) {
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
                if (!empty($raw['items']) && is_array($raw['items'])) {
                    foreach (array_values($raw['items']) as $row) {
                        if (is_array($row)) {
                            $fontSize = isset($row['font_size']) ? (int) $row['font_size'] : 14;
                            $fontSize = max(10, min(24, $fontSize));
                            $fontStyle = isset($row['font_style']) && in_array($row['font_style'], ['normal', 'bold', 'italic'], true) ? $row['font_style'] : 'normal';
                            $cardSize = isset($row['card_size']) && in_array($row['card_size'], ['small', 'normal', 'large', 'max'], true) ? $row['card_size'] : 'normal';
                            $textAlign = isset($row['text_align']) && in_array($row['text_align'], ['left', 'center', 'right'], true) ? $row['text_align'] : 'left';
                            $items[] = [
                                'image_url' => isset($row['image_url']) ? trim((string) $row['image_url']) : '',
                                'text' => isset($row['text']) ? trim((string) $row['text']) : '',
                                'font_size' => $fontSize,
                                'font_style' => $fontStyle,
                                'card_size' => $cardSize,
                                'text_align' => $textAlign,
                            ];
                        }
                    }
                }
                $defItems = $defaults['cards_ronde_hoeken']['items'] ?? [['image_url' => '', 'text' => '', 'font_size' => 14, 'font_style' => 'normal', 'card_size' => 'normal', 'text_align' => 'left']];
                return ['items' => $items ?: $defItems];
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
                $v = preg_match('/^#?[0-9a-fA-F]{3,6}$/', $v) ? (str_starts_with($v, '#') ? $v : '#' . $v) : '';
                if (strlen($v) === 4) {
                    $v = '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
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
        $dir = 'website';
        if (!Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $logoFile->store($dir, 'public');
        // Relatief pad zodat de afbeelding vanaf dezelfde origin wordt geladen (voorkomt ERR_CONNECTION_CLOSED)
        $url = '/storage/' . ltrim($path, '/');

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
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ], [
            'image.required' => 'Selecteer een afbeelding.',
            'image.mimes' => 'Alleen JPEG, PNG, JPG, GIF en WebP zijn toegestaan.',
            'image.max' => 'Het bestand mag maximaal 5MB groot zijn.',
        ]);

        $file = $request->file('image');
        $dir = 'website/hero';
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $file->store($dir, 'public');
        $url = '/storage/' . ltrim($path, '/');

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }

    public function destroy(WebsitePage $website_page)
    {
        $this->ensureSuperAdmin();
        $website_page->delete();
        return redirect()->route('admin.website-pages.index')->with('success', 'Pagina verwijderd.');
    }

    /**
     * Bepaal frontend_theme_id uit het request (zelfde logica als bij store/update).
     * Gebruikt voor slug-uniekheid per thema.
     */
    private function resolveFrontendThemeIdFromRequest(Request $request): ?int
    {
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
        if (!auth()->check() || !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admins hebben toegang tot website-pagina\'s.');
        }
    }
}

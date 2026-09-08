<?php

namespace App\Services;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\FrontendTheme;
use App\Models\WebsiteMedia;
use App\Models\WebsitePage;
use App\Services\AiWebsite\AiScalar;
use App\Services\AiWebsite\ComponentRegistry\AiComponentRegistry;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class WebsiteAiGeneratorService
{
    public const MAX_PAGES = 12;

    /** @var list<string> */
    private const ALLOWED_ICONS = [
        'light-bulb', 'bolt', 'briefcase', 'cog-6-tooth', 'user-group', 'map-pin',
        'phone', 'clock', 'shield-check', 'truck', 'star', 'heart', 'globe-alt',
        'chat-bubble-left-right', 'calendar', 'check-badge',
    ];

    public function __construct(
        protected WebsiteAiSourceReader $sourceReader,
        protected FrontendComponentService $components,
        protected AiComponentRegistry $registry,
    ) {}

    /**
     * @param  array{
     *     company_id: int,
     *     source_url?: string|null,
     *     context: string,
     *     max_pages: int,
     *     frontend_theme_id: int,
     *     primary_color?: string,
     *     secondary_color?: string,
     *     generate_images?: bool,
     *     replace_existing?: bool,
     * }  $input
     * @return array{created: list<array{id: int, slug: string, title: string}>, updated: list<array{id: int, slug: string, title: string}>, skipped: list<string>, images: int, used_openai: bool, source_pages: int}
     */
    public function generate(array $input): array
    {
        $company = Company::query()->findOrFail((int) $input['company_id']);
        $theme = FrontendTheme::query()->findOrFail((int) $input['frontend_theme_id']);
        $maxPages = max(1, min(self::MAX_PAGES, (int) $input['max_pages']));
        $primary = $this->normalizeHex($input['primary_color'] ?? '') ?: (string) (($theme->settings['primary_color'] ?? null) ?: '#1e3a8a');
        $secondary = $this->normalizeHex($input['secondary_color'] ?? '') ?: (string) (($theme->settings['secondary_color'] ?? null) ?: FrontendTheme::defaultSecondaryFor($primary));
        $generateImages = (bool) ($input['generate_images'] ?? true);
        $replaceExisting = (bool) ($input['replace_existing'] ?? false);
        $context = trim((string) $input['context']);

        $source = $this->sourceReader->read(isset($input['source_url']) ? (string) $input['source_url'] : '');
        $fromLlm = $this->tryOpenAiPlan($company, $theme, $context, $maxPages, $source, $primary, $secondary);
        $plan = $this->normalizePlan(
            $fromLlm ?? $this->fallbackPlan($company, $theme, $context, $maxPages, $source),
            $company,
            $theme,
            $maxPages,
        );

        $company->update([
            'frontend_theme_id' => $theme->id,
            'website_theme_settings' => [
                'primary_color' => $primary,
                'secondary_color' => $secondary,
            ],
        ]);

        $created = [];
        $updated = [];
        $skipped = [];
        $imageCount = 0;
        $sort = WebsitePage::nextSortOrderForTenant(null, (int) $company->id);
        $menuPages = [];
        $moduleName = $this->resolveWebsiteModuleName($company);

        foreach ($plan['pages'] as $pagePlan) {
            $slug = (string) $pagePlan['slug'];
            $existing = $this->findExistingPage($company, $slug, $moduleName);
            if ($existing && ! $replaceExisting) {
                $skipped[] = $slug;
                $menuPages[] = [
                    'label' => (string) ($existing->menu_title ?: $existing->title),
                    'url' => $this->publicPath($existing->page_type, $existing->slug),
                ];

                continue;
            }

            $isHome = $pagePlan['page_type'] === 'home';
            $sections = $isHome
                ? WebsitePage::defaultHomeSectionsForTheme($theme->slug)
                : WebsitePage::defaultPageSectionsForNonHome($theme->slug);
            $sections = $this->applyPagePlanToSections($sections, $pagePlan, $theme, $isHome, $company);
            $sections = $this->applyBrandColors($sections, $primary, $secondary);

            if ($generateImages) {
                $imageCount += $this->applyGeneratedImages(
                    $sections,
                    $pagePlan,
                    trim((string) ($company->slug ?? '')) ?: Str::slug((string) $company->name),
                    $slug
                );
            }

            $payload = [
                'slug' => $slug,
                'title' => $pagePlan['title'],
                'menu_title' => $pagePlan['menu_title'],
                'meta_description' => $pagePlan['meta_description'],
                'page_type' => $pagePlan['page_type'],
                'module_name' => $this->pageModuleName($existing, $moduleName),
                'frontend_theme_id' => $theme->id,
                'company_id' => $company->id,
                'is_active' => true,
                'show_in_menu' => (bool) $pagePlan['show_in_menu'],
                'home_sections' => $sections,
            ];

            if ($existing) {
                $existing->fill($payload);
                $existing->save();
                $page = $existing->fresh();
                $updated[] = ['id' => (int) $page->id, 'slug' => $page->slug, 'title' => $page->title];
            } else {
                $payload['sort_order'] = $isHome ? min($sort, 1) : $sort;
                $page = WebsitePage::query()->create($payload);
                $sort++;
                $created[] = ['id' => (int) $page->id, 'slug' => $page->slug, 'title' => $page->title];
            }

            if ($page->show_in_menu) {
                $menuPages[] = [
                    'label' => (string) ($page->menu_title ?: $page->title),
                    'url' => $this->publicPath($page->page_type, $page->slug),
                ];
            }
        }

        $this->collapseDuplicateHomes($company, $moduleName);
        $this->syncHomeFooter($company, $plan, $menuPages, $theme, $moduleName);

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $imageCount,
            'used_openai' => $fromLlm !== null,
            'source_pages' => count($source['pages'] ?? []),
        ];
    }

    /**
     * Schrijf geplande pagina’s als concept (niet publiceren).
     *
     * @param  list<array<string, mixed>>  $pages
     * @param  list<array<string, mixed>>  $sitemap
     * @return array{created: list<array{id: int, slug: string, title: string}>, updated: list<array{id: int, slug: string, title: string}>, skipped: list<string>, images: int, homepage_page_id: int|null}
     */
    public function persistDraftPages(
        Company $company,
        FrontendTheme $theme,
        array $pages,
        array $sitemap,
        string $primary,
        string $secondary,
        bool $generateImages,
        bool $replaceExisting,
    ): array {
        $primary = $this->normalizeHex($primary) ?: (string) (($theme->settings['primary_color'] ?? null) ?: '#1e3a8a');
        $secondary = $this->normalizeHex($secondary) ?: (string) (($theme->settings['secondary_color'] ?? null) ?: FrontendTheme::defaultSecondaryFor($primary));
        $company->update([
            'frontend_theme_id' => $theme->id,
            'website_theme_settings' => [
                'primary_color' => $primary,
                'secondary_color' => $secondary,
            ],
        ]);

        $created = [];
        $updated = [];
        $skipped = [];
        $imageCount = 0;
        $moduleName = $this->resolveWebsiteModuleName($company);
        $sort = WebsitePage::nextSortOrderForTenant(null, (int) $company->id);
        $homePage = $this->findExistingPage($company, 'home', $moduleName);
        $contactTemplateId = $this->resolveContactEmailTemplateId($company);

        foreach ($pages as $rawPage) {
            if (! is_array($rawPage)) {
                continue;
            }
            $pagePlan = $this->pagePlanFromArray($rawPage);
            $slug = (string) $pagePlan['slug'];
            $isHome = $pagePlan['page_type'] === 'home' || $slug === 'home';
            $existing = $this->findExistingPage($company, $slug, $moduleName);

            if ($existing && ! $replaceExisting) {
                $skipped[] = $slug;
                if ($isHome) {
                    $homePage = $existing;
                }

                continue;
            }

            $sections = $isHome
                ? WebsitePage::defaultHomeSectionsForTheme((string) $theme->slug)
                : WebsitePage::defaultPageSectionsForNonHome((string) $theme->slug);
            $sections = $this->applyPagePlanToSections($sections, $pagePlan, $theme, $isHome, $company);
            $sections = $this->finalizeSectionLayout($sections, $pagePlan, (string) $theme->slug, $company, $isHome, $contactTemplateId);
            $sections = $this->applyBrandColors($sections, $primary, $secondary);

            if ($generateImages) {
                $imageCount += $this->applyGeneratedImagesToMediaLibrary(
                    $sections,
                    $pagePlan,
                    trim((string) ($company->slug ?? '')) ?: Str::slug((string) $company->name),
                    $slug
                );
            }

            $payload = [
                'slug' => $slug,
                'title' => $pagePlan['title'],
                'menu_title' => $pagePlan['menu_title'],
                'meta_description' => $pagePlan['meta_description'],
                'page_type' => $pagePlan['page_type'],
                'module_name' => $this->pageModuleName($existing, $moduleName),
                'frontend_theme_id' => $theme->id,
                'company_id' => $company->id,
                'is_active' => false,
                'show_in_menu' => (bool) $pagePlan['show_in_menu'],
                'home_sections' => $sections,
            ];

            if ($existing) {
                $existing->fill($payload);
                $existing->save();
                $page = $existing->fresh();
                $updated[] = ['id' => (int) $page->id, 'slug' => $page->slug, 'title' => $page->title];
            } else {
                $payload['sort_order'] = $isHome ? min($sort, 1) : $sort;
                $page = WebsitePage::query()->create($payload);
                $sort++;
                $created[] = ['id' => (int) $page->id, 'slug' => $page->slug, 'title' => $page->title];
            }
            if ($isHome) {
                $homePage = $page;
            }
        }

        $this->collapseDuplicateHomes($company, $moduleName);
        $homePage = $this->findExistingPage($company, 'home', $moduleName) ?? $homePage;

        $menuPages = [];
        foreach ($sitemap as $item) {
            if (! is_array($item) || ! ($item['show_in_menu'] ?? true)) {
                continue;
            }
            $slug = (string) ($item['slug'] ?? '');
            $type = (string) ($item['type'] ?? 'custom');
            $menuPages[] = [
                'label' => (string) ($item['title'] ?? $slug),
                'url' => $this->publicPath($type === 'home' ? 'home' : $type, $slug !== '' ? $slug : 'pagina'),
            ];
        }
        $first = is_array($pages[0] ?? null) ? $pages[0] : [];
        $this->syncHomeFooter(
            $company,
            ['footer' => is_array($first['footer'] ?? null) ? $first['footer'] : []],
            $menuPages,
            $theme,
            $moduleName
        );

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $imageCount,
            'homepage_page_id' => $homePage?->id ? (int) $homePage->id : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    private function pagePlanFromArray(array $page): array
    {
        $title = AiScalar::string($page['title'] ?? '') ?: 'Pagina';
        $pageType = AiScalar::string($page['page_type'] ?? $page['type'] ?? 'custom');
        $slug = Str::slug(AiScalar::string($page['slug'] ?? $title));
        if ($pageType === 'home' || $slug === 'home') {
            $pageType = 'home';
            $slug = 'home';
        } elseif ($pageType === 'about' && $slug === '') {
            $slug = 'over-ons';
        } elseif ($pageType === 'contact' && $slug === '') {
            $slug = 'contact';
        } elseif ($slug === '') {
            $slug = 'pagina';
        }

        return [
            'slug' => $slug,
            'title' => mb_substr($title, 0, 255),
            'menu_title' => mb_substr(AiScalar::string($page['menu_title'] ?? '') ?: $title, 0, 80),
            'meta_description' => mb_substr(AiScalar::string($page['meta_description'] ?? ''), 0, 500),
            'page_type' => in_array($pageType, ['home', 'about', 'contact', 'custom'], true) ? $pageType : 'custom',
            'show_in_menu' => array_key_exists('show_in_menu', $page) ? (bool) $page['show_in_menu'] : true,
            'hero' => is_array($page['hero'] ?? null) ? $page['hero'] : [],
            'why_nexa' => is_array($page['why_nexa'] ?? null) ? $page['why_nexa'] : [],
            'features' => is_array($page['features'] ?? null) ? $page['features'] : [],
            'stats' => is_array($page['stats'] ?? null) ? $page['stats'] : [],
            'cta' => is_array($page['cta'] ?? null) ? $page['cta'] : [],
            'featured_services' => is_array($page['featured_services'] ?? null) ? $page['featured_services'] : [],
            'text_block' => is_array($page['text_block'] ?? null) ? $page['text_block'] : [],
            'email_template' => is_array($page['email_template'] ?? null) ? $page['email_template'] : [],
            'components' => AiScalar::componentIds($page['components'] ?? []),
            'component_copy' => is_array($page['component_copy'] ?? null) ? $page['component_copy'] : [],
            'section_order' => is_array($page['section_order'] ?? null) ? $page['section_order'] : [],
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     * @return array<string, mixed>
     */
    private function finalizeSectionLayout(
        array $sections,
        array $pagePlan,
        string $themeSlug,
        Company $company,
        bool $isHome,
        ?int $contactTemplateId,
    ): array {
        $planned = is_array($pagePlan['section_order'] ?? null) ? $pagePlan['section_order'] : [];
        $order = $this->registry->filterSectionOrder($themeSlug, $planned !== [] ? $planned : ($sections['section_order'] ?? ['hero']));

        if ($this->hasMeaningfulFeaturedServices($sections['featured_services'] ?? [])) {
            $shouldKeepFeatured = $planned === [] || in_array('featured_services', $planned, true);
            if ($shouldKeepFeatured) {
                $order = $this->insertBeforeCta($order, 'featured_services');
            } else {
                $order = array_values(array_filter($order, fn ($key) => $key !== 'featured_services'));
            }
        }
        $text = trim((string) data_get($sections, 'text_block.content', data_get($pagePlan, 'text_block.content', '')));
        if ($text !== '') {
            $block = is_array($sections['text_block'] ?? null) ? $sections['text_block'] : WebsitePage::defaultHomeSections()['text_block'];
            $block['content'] = $text;
            $this->applyTextBlockLayoutFromPlan($block, $pagePlan);
            $sections['text_block'] = $block;
            $order = $this->insertBeforeCta($order, 'text_block');
        }

        $isContact = ($pagePlan['page_type'] ?? '') === 'contact' || ($pagePlan['slug'] ?? '') === 'contact';
        if ($isContact && $contactTemplateId) {
            $email = is_array($sections['email_template'] ?? null)
                ? $sections['email_template']
                : WebsitePage::defaultHomeSections()['email_template'];
            $title = trim((string) ($pagePlan['email_template']['title'] ?? $email['title'] ?? ''));
            $email['title'] = $title !== '' ? $title : 'Stuur een bericht';
            $email['template_id'] = $contactTemplateId;
            $sections['email_template'] = $email;
            $heroIndex = array_search('text_block', $order, true);
            if (! in_array('email_template', $order, true)) {
                if ($heroIndex !== false) {
                    array_splice($order, (int) $heroIndex + 1, 0, ['email_template']);
                } else {
                    $order = $this->insertAfterHero($order, 'email_template');
                }
            }
        }

        foreach ($this->allowedComponentKeys($pagePlan['components'] ?? []) as $componentKey) {
            if (str_contains($componentKey, 'boekingsmodule')) {
                continue;
            }
            $this->ensureComponentSection($sections, $componentKey, $pagePlan);
            if (! in_array($componentKey, $order, true)) {
                $order = $this->insertBeforeCta($order, $componentKey);
            }
        }

        $wantsBooking = $company->hasTaxiModule();
        foreach (AiScalar::componentIds($pagePlan['components'] ?? []) as $id) {
            if (str_contains($id, 'boekingsmodule')) {
                $wantsBooking = true;
                break;
            }
        }
        if ($isHome && $wantsBooking) {
            $bookingKey = 'component:taxi.boekingsmodule_v2';
            $this->ensureComponentSection($sections, $bookingKey, $pagePlan);
            $order = array_values(array_filter(
                $order,
                fn ($key) => ! is_string($key) || ! str_contains($key, 'boekingsmodule')
            ));
            $order = $this->insertAfterHero($order, $bookingKey);
        }

        $sections['section_order'] = array_values(array_unique($order));
        $this->applyComponentCopy($sections, $pagePlan);

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     */
    private function ensureComponentSection(array &$sections, string $componentKey, array $pagePlan): void
    {
        $id = FrontendComponentService::componentIdFromKey($componentKey);
        if ($id === null || $id === '') {
            return;
        }
        if (in_array($id, ['taxi.boekingsmodule_v2', 'taxi.algemene_boekingsmodule', 'taxi.boekingsmodule'], true)) {
            $current = is_array($sections[$componentKey] ?? null) ? $sections[$componentKey] : [];
            $sections[$componentKey] = array_replace_recursive(
                app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig(),
                $current
            );

            return;
        }
        if (isset($sections[$componentKey]) && is_array($sections[$componentKey]) && $sections[$componentKey] !== []) {
            return;
        }
        $sections[$componentKey] = $this->components->defaultSectionData($id);
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     */
    private function applyComponentCopy(array &$sections, array $pagePlan): void
    {
        $copy = is_array($pagePlan['component_copy'] ?? null) ? $pagePlan['component_copy'] : [];
        foreach ($copy as $id => $data) {
            if (! is_array($data)) {
                continue;
            }
            $key = str_starts_with((string) $id, 'component:') ? (string) $id : 'component:'.$id;
            if (! isset($sections[$key]) || ! is_array($sections[$key])) {
                continue;
            }
            $sections[$key] = array_replace_recursive($sections[$key], $data);
        }
    }

    /**
     * @param  list<string>  $order
     * @return list<string>
     */
    private function insertAfterHero(array $order, string $key): array
    {
        $order = array_values(array_filter($order, fn ($item) => $item !== $key));
        $heroIndex = array_search('hero', $order, true);
        $at = $heroIndex === false ? 0 : $heroIndex + 1;
        array_splice($order, $at, 0, [$key]);

        return array_values($order);
    }

    private function resolveContactEmailTemplateId(Company $company): ?int
    {
        $email = trim((string) ($company->email ?? '')) ?: trim((string) ($company->contact_email ?? ''));
        $existing = EmailTemplate::query()
            ->where('company_id', $company->id)
            ->where('type', 'informatieaanvraag')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
        if ($existing) {
            if ($email !== '' && trim((string) $existing->recipient_email) === '') {
                $existing->recipient_type = 'email';
                $existing->recipient_email = $email;
                $existing->save();
            }

            return (int) $existing->id;
        }

        $template = EmailTemplate::query()->create([
            'name' => 'Contactformulier '.$company->name,
            'subject' => 'Nieuwe aanvraag via de website van '.$company->name,
            'type' => 'informatieaanvraag',
            'html_content' => '<p>Er is een nieuwe aanvraag via de website.</p>{{ DYNAMIC_FORM_FIELDS }}',
            'is_active' => true,
            'company_id' => $company->id,
            'recipient_type' => 'email',
            'recipient_email' => $email !== '' ? $email : null,
        ]);

        return (int) $template->id;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     */
    private function applyGeneratedImagesToMediaLibrary(array &$sections, array $pagePlan, string $companySlug, string $slug): int
    {
        return $this->attachGeneratedImages($sections, $pagePlan, $companySlug, $slug, true);
    }

    public function generateAndStoreWebsiteImage(string $prompt, string $companySlug, string $pageSlug): ?string
    {
        return $this->generateAndStoreImageInMediaLibrary($this->withPhotorealism($prompt), $companySlug, $pageSlug);
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     */
    private function attachGeneratedImages(array &$sections, array $pagePlan, string $companySlug, string $slug, bool $mediaLibrary): int
    {
        $count = 0;
        $heroPrompt = trim((string) data_get($pagePlan, 'hero.image_prompt', ''));
        if ($heroPrompt === '') {
            $heroPrompt = 'Photorealistic cinematic photograph for a professional Dutch company website hero';
        }
        $heroUrl = $this->storeGeneratedImage($heroPrompt, $companySlug, $slug.'-hero', $mediaLibrary);
        if ($heroUrl !== null && isset($sections['hero']) && is_array($sections['hero'])) {
            $sections['hero']['background_image_url'] = $heroUrl;
            $count++;
        }

        $textContent = trim((string) data_get($sections, 'text_block.content', data_get($pagePlan, 'text_block.content', '')));
        if ($textContent !== '') {
            $sidePrompt = trim((string) data_get($pagePlan, 'text_block.image_prompt', ''));
            if ($sidePrompt === '') {
                $sidePrompt = $heroPrompt !== ''
                    ? $heroPrompt
                    : 'Photorealistic cinematic still matching a Dutch professional service website story';
            }
            $sidePrompt .= '. Vertical-friendly 4:3 composition for a text-and-image website section.';
            $sideUrl = $this->storeGeneratedImage($sidePrompt, $companySlug, $slug.'-text', $mediaLibrary);
            if ($sideUrl !== null) {
                $block = is_array($sections['text_block'] ?? null) ? $sections['text_block'] : WebsitePage::defaultHomeSections()['text_block'];
                $block['image_url'] = $sideUrl;
                $alignment = strtolower(AiScalar::string(data_get($pagePlan, 'text_block.alignment', $block['alignment'] ?? 'left')));
                $block['alignment'] = in_array($alignment, ['left', 'right'], true) ? $alignment : 'left';
                $block['width_percent'] = 100;
                $sections['text_block'] = $block;
                $count++;
            }
        }

        return $count;
    }

    private function storeGeneratedImage(string $prompt, string $companySlug, string $pageSlug, bool $mediaLibrary): ?string
    {
        $prompt = $this->withPhotorealism($prompt);

        return $mediaLibrary
            ? $this->generateAndStoreImageInMediaLibrary($prompt, $companySlug, $pageSlug)
            : $this->generateAndStoreImage($prompt, $companySlug, $pageSlug);
    }

    private function withPhotorealism(string $prompt): string
    {
        $prompt = trim($prompt);
        $suffix = ' Photorealistic live-action photograph or premium cinematic motion still of a real scene. Not a sketch, not a pencil drawing, not a cartoon, not an illustration, not a wireframe, not a 3D clay render. Sharp focus, realistic materials and lighting, high production quality, no text, no logos, no watermark.';
        if (! str_contains(mb_strtolower($prompt), 'photoreal')) {
            $prompt = 'Photorealistic. '.$prompt;
        }

        return mb_substr($prompt.$suffix, 0, 3500);
    }

    private function generateAndStoreImageInMediaLibrary(string $prompt, string $companySlug, string $pageSlug): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = (string) config('ai_website.image_model', config('services.openai.image_model', 'dall-e-3'));
        $payload = [
            'model' => $model,
            'prompt' => mb_substr($prompt, 0, 3500),
            'n' => 1,
            'size' => (string) config('services.openai.image_size', '1792x1024'),
        ];
        if ($model === 'dall-e-3') {
            $payload['quality'] = (string) config('ai_website.image_quality', config('services.openai.image_quality', 'hd'));
            if (! in_array($payload['quality'], ['standard', 'hd'], true)) {
                $payload['quality'] = 'hd';
            }
        } elseif (str_starts_with($model, 'gpt-image')) {
            $payload['quality'] = (string) config('ai_website.image_quality', config('services.openai.image_quality', 'high'));
            if (! in_array($payload['quality'], ['low', 'medium', 'high'], true)) {
                $payload['quality'] = 'high';
            }
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/images/generations', $payload);
            if (! $response->successful()) {
                Log::warning('Website AI image: OpenAI HTTP-fout', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return null;
            }
            $b64 = $response->json('data.0.b64_json');
            $remoteUrl = $response->json('data.0.url');
            $binary = null;
            if (is_string($b64) && $b64 !== '') {
                $decoded = base64_decode($b64, true);
                $binary = $decoded !== false ? $decoded : null;
            } elseif (is_string($remoteUrl) && $remoteUrl !== '') {
                $download = Http::timeout(60)->get($remoteUrl);
                if ($download->successful()) {
                    $binary = $download->body();
                }
            }
            if ($binary === null || $binary === '') {
                return null;
            }

            $uuid = (string) Str::uuid();
            $encryptedPath = 'website_media/'.$uuid.'.enc';
            $media = WebsiteMedia::query()->create([
                'uuid' => $uuid,
                'original_filename' => 'ai-'.Str::slug($companySlug.'-'.$pageSlug).'.png',
                'mime_type' => 'image/png',
                'encrypted_path' => $encryptedPath,
                'size' => strlen($binary),
            ]);
            Storage::disk('local')->put($encryptedPath, Crypt::encrypt($binary));

            return '/website-media/'.$media->uuid;
        } catch (Throwable $e) {
            Log::warning('Website AI image: uitzondering', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>|null
     */
    private function tryOpenAiPlan(
        Company $company,
        FrontendTheme $theme,
        string $context,
        int $maxPages,
        array $source,
        string $primary,
        string $secondary,
    ): ?array {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = config('services.openai.model', 'gpt-4o-mini');
        $allowedSections = array_values(array_map(
            fn (array $row) => $row['type'],
            WebsitePage::getAvailableHomeSectionTypesForTheme((string) $theme->slug)
        ));
        $allowedComponents = $this->components->all()
            ->map(fn ($c) => (string) ($c->id ?? ''))
            ->filter()
            ->values()
            ->all();

        $prompt = [
            'opdracht' => 'Ontwerp een professionele Nederlandse bedrijfswebsite in JSON. Gebruik alleen de opgegeven secties en componenten van de NEXA website builder. Schrijf wervende, concrete copy. Geen em-dash. Neem bruikbare feiten over uit de oude website, maar herschrijf alles fris. Kies per pagina een andere subset van 3 tot 4 componenten; herhaal niet altijd FAQ+quotes+comparison. image_prompt bij hero en text_block: fotorealistische foto of hoogwaardige cinematic still, geen schets. Footer support-links alleen als die pagina’s in pages staan.',
            'bedrijf' => [
                'naam' => $company->name,
                'plaats' => trim((string) ($company->city ?? '')),
                'branche' => trim((string) ($company->industry ?? '')),
                'telefoon' => trim((string) ($company->phone ?? '')),
                'e-mail' => trim((string) ($company->email ?? '')),
                'website' => trim((string) ($company->website ?? '')),
                'omschrijving' => trim((string) ($company->description ?? '')),
            ],
            'brief' => $context,
            'max_paginas' => $maxPages,
            'thema' => ['slug' => $theme->slug, 'naam' => $theme->name],
            'kleuren' => ['primary' => $primary, 'secondary' => $secondary],
            'toegestane_secties' => $allowedSections,
            'toegestane_componenten' => $allowedComponents,
            'toegestane_iconen' => self::ALLOWED_ICONS,
            'oude_website' => $source['summary'] ?? '',
            'gewenst_json' => [
                'brand' => ['name' => '', 'tagline' => ''],
                'pages' => [[
                    'slug' => 'home',
                    'title' => '',
                    'menu_title' => '',
                    'page_type' => 'home|about|contact|custom',
                    'meta_description' => '',
                    'show_in_menu' => true,
                    'hero' => [
                        'title' => '',
                        'title_highlight' => '',
                        'subtitle' => '',
                        'cta_primary_text' => '',
                        'cta_primary_url' => '/contact',
                        'cta_secondary_text' => '',
                        'cta_secondary_url' => '/',
                        'image_prompt' => 'fotorealistische hero-foto, live-action, geen schets, geen tekst in beeld',
                    ],
                    'why_nexa' => ['title' => '', 'subtitle' => ''],
                    'features' => ['section_title' => '', 'items' => [['title' => '', 'description' => '', 'icon' => 'bolt']]],
                    'stats' => ['items' => [['value' => '', 'label' => '']]],
                    'cta' => ['title' => '', 'subtitle' => '', 'cta_primary_text' => '', 'cta_primary_url' => '/contact'],
                    'featured_services' => ['title' => '', 'subtitle' => '', 'items' => [['icon' => 'briefcase', 'title' => '', 'description' => '']]],
                    'text_block' => ['content' => '<p></p>', 'alignment' => 'left', 'image_prompt' => 'fotorealistische foto naast de tekst, geen schets, geen tekst in beeld'],
                    'components' => ['taxi.boekingsmodule_v2'],
                ]],
                'footer' => ['tagline' => '', 'copyright' => ''],
            ],
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(90)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.45,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Je bent een Nederlandse webdesigner en copywriter voor de NEXA website builder. Antwoord uitsluitend met geldig JSON. Maak maximaal het gevraagde aantal pagina\'s. De eerste pagina is altijd home. Gebruik bestaande secties en component-ids. image_prompt is in het Engels, fotorealistisch, geen letters in het beeld.',
                        ],
                        ['role' => 'user', 'content' => json_encode($prompt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
                    ],
                ]);
            if (! $response->successful()) {
                Log::warning('Website AI plan: OpenAI HTTP-fout', ['status' => $response->status()]);

                return null;
            }
            $decoded = json_decode((string) $response->json('choices.0.message.content'), true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            Log::warning('Website AI plan: OpenAI uitzondering', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function fallbackPlan(Company $company, FrontendTheme $theme, string $context, int $maxPages, array $source): array
    {
        $brand = trim((string) $company->name) ?: 'Ons bedrijf';
        $city = trim((string) ($company->city ?? '')) ?: 'Nederland';
        $brief = $context !== '' ? $context : 'Professionele dienstverlening in '.$city.'.';
        $isTaxi = $this->looksLikeTaxi($company, $context, $source);
        $phone = trim((string) ($company->phone ?? ''));

        $blueprints = [
            [
                'slug' => 'home',
                'title' => $brand,
                'menu_title' => 'Home',
                'page_type' => 'home',
                'show_in_menu' => true,
                'meta_description' => mb_substr($brand.' in '.$city.'. '.$brief, 0, 160),
                'hero' => [
                    'title' => $isTaxi ? 'Betrouwbaar vervoer in '.$city : 'Welkom bij '.$brand,
                    'title_highlight' => $city,
                    'subtitle' => $brief,
                    'cta_primary_text' => $isTaxi ? 'Direct boeken' : 'Neem contact op',
                    'cta_primary_url' => $isTaxi ? '/contact' : '/contact',
                    'cta_secondary_text' => 'Over ons',
                    'cta_secondary_url' => '/over-ons',
                    'image_prompt' => $isTaxi
                        ? 'Photorealistic premium taxi at dusk in a Dutch city street, cinematic lighting, no text'
                        : 'Photorealistic professional Dutch storefront and team atmosphere, cinematic lighting, no text',
                ],
                'why_nexa' => [
                    'title' => 'Over '.$brand,
                    'subtitle' => $brief,
                ],
                'features' => [
                    'section_title' => $isTaxi ? 'Waarom bij ons boeken' : 'Waarom '.$brand,
                    'items' => [
                        ['title' => 'Persoonlijk contact', 'description' => 'Korte lijnen, duidelijke afspraken en een vast aanspreekpunt.', 'icon' => 'chat-bubble-left-right'],
                        ['title' => 'Op tijd en betrouwbaar', 'description' => 'U kunt rekenen op een strakke planning en nette uitvoering.', 'icon' => 'clock'],
                        ['title' => 'Lokaal verankerd', 'description' => 'Wij kennen '.$city.' en de regio als geen ander.', 'icon' => 'map-pin'],
                    ],
                ],
                'stats' => [
                    'items' => [
                        ['value' => '24/7', 'label' => $isTaxi ? 'Bereikbaar' : 'Service'],
                        ['value' => $city, 'label' => 'Werkgebied'],
                        ['value' => $phone !== '' ? $phone : 'Direct', 'label' => 'Contact'],
                        ['value' => '100%', 'label' => 'Inzet'],
                    ],
                ],
                'cta' => [
                    'title' => $isTaxi ? 'Klaar om te rijden?' : 'Zullen we kennismaken?',
                    'subtitle' => $phone !== '' ? 'Bel '.$phone.' of stuur een bericht via de contactpagina.' : 'Neem contact op voor een snelle reactie.',
                    'cta_primary_text' => 'Contact',
                    'cta_primary_url' => '/contact',
                    'cta_secondary_text' => 'Home',
                    'cta_secondary_url' => '/',
                ],
                'featured_services' => [
                    'title' => 'Diensten',
                    'subtitle' => 'Wat wij voor u regelen.',
                    'items' => $isTaxi ? [
                        ['icon' => 'truck', 'title' => 'Taxivervoer', 'description' => 'Luchthaven, zakelijk en privevervoer, netjes en op tijd.'],
                        ['icon' => 'briefcase', 'title' => 'Zakelijk vervoer', 'description' => 'Vaste ritten en accountafspraken voor bedrijven.'],
                        ['icon' => 'user-group', 'title' => 'Contractvervoer', 'description' => 'School, zorg en terugkerende ritten met duidelijke communicatie.'],
                    ] : [
                        ['icon' => 'briefcase', 'title' => 'Advies', 'description' => 'Eerlijk advies, afgestemd op uw situatie.'],
                        ['icon' => 'cog-6-tooth', 'title' => 'Uitvoering', 'description' => 'Van aanvraag tot afronding, helder georganiseerd.'],
                        ['icon' => 'shield-check', 'title' => 'Nazorg', 'description' => 'Bereikbaar na de klus, zonder gedoe.'],
                    ],
                ],
                'components' => $isTaxi ? ['taxi.boekingsmodule_v2'] : [],
            ],
            [
                'slug' => 'over-ons',
                'title' => 'Over '.$brand,
                'menu_title' => 'Over ons',
                'page_type' => 'about',
                'show_in_menu' => true,
                'meta_description' => mb_substr('Leer '.$brand.' in '.$city.' kennen. '.$brief, 0, 160),
                'hero' => [
                    'title' => 'Het verhaal van '.$brand,
                    'title_highlight' => $brand,
                    'subtitle' => $brief,
                    'cta_primary_text' => 'Contact',
                    'cta_primary_url' => '/contact',
                    'cta_secondary_text' => '',
                    'cta_secondary_url' => '',
                    'image_prompt' => 'Photorealistic portrait of a professional Dutch service team, warm natural light, no text',
                ],
                'text_block' => [
                    'content' => '<p>'.e($brand).' is gevestigd in '.e($city).'. '.e($brief).'</p><p>Wij werken met korte lijnen en houden u op de hoogte, van de eerste aanvraag tot de afronding.</p>',
                ],
                'components' => [],
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'menu_title' => 'Contact',
                'page_type' => 'contact',
                'show_in_menu' => true,
                'meta_description' => mb_substr('Neem contact op met '.$brand.' in '.$city.'.', 0, 160),
                'hero' => [
                    'title' => 'Neem contact op',
                    'title_highlight' => 'contact',
                    'subtitle' => $phone !== '' ? 'Bel '.$phone.' of stuur een bericht. We reageren snel.' : 'Stuur een bericht. We reageren snel.',
                    'cta_primary_text' => $phone !== '' ? 'Bel ons' : 'Home',
                    'cta_primary_url' => $phone !== '' ? 'tel:'.preg_replace('/\s+/', '', $phone) : '/',
                    'cta_secondary_text' => '',
                    'cta_secondary_url' => '',
                    'image_prompt' => 'Photorealistic reception desk of a Dutch mobility company, daylight, no text',
                ],
                'text_block' => [
                    'content' => '<p>We helpen u graag verder. Vermeld uw gewenste datum, aantal personen en eventuele bijzonderheden.</p>',
                ],
                'components' => $isTaxi ? ['taxi.boekingsmodule_v2'] : [],
            ],
            [
                'slug' => 'diensten',
                'title' => 'Diensten',
                'menu_title' => 'Diensten',
                'page_type' => 'custom',
                'show_in_menu' => true,
                'meta_description' => mb_substr('Bekijk de diensten van '.$brand.' in '.$city.'.', 0, 160),
                'hero' => [
                    'title' => 'Onze diensten',
                    'title_highlight' => 'diensten',
                    'subtitle' => 'Overzicht van wat '.$brand.' voor u kan betekenen.',
                    'cta_primary_text' => 'Offerte of boeking',
                    'cta_primary_url' => '/contact',
                    'cta_secondary_text' => '',
                    'cta_secondary_url' => '',
                    'image_prompt' => $isTaxi
                        ? 'Photorealistic luxury people carrier on a Dutch motorway, golden hour, no text'
                        : 'Photorealistic professional workspace, Dutch interior, no text',
                ],
                'featured_services' => [
                    'title' => 'Wat wij doen',
                    'subtitle' => '',
                    'items' => $isTaxi ? [
                        ['icon' => 'truck', 'title' => 'Privé- en zakelijk vervoer', 'description' => 'Van luchthaven tot congres, met vaste chauffeurs.'],
                        ['icon' => 'clock', 'title' => 'Op afroep of gepland', 'description' => 'Direct een rit of vooruit plannen, zoals het u uitkomt.'],
                        ['icon' => 'shield-check', 'title' => 'Veilig en verzekerd', 'description' => 'Nette voertuigen, ervaren chauffeurs en duidelijke tarieven.'],
                    ] : [
                        ['icon' => 'star', 'title' => 'Maatwerk', 'description' => 'Oplossingen die passen bij uw bedrijf.'],
                        ['icon' => 'clock', 'title' => 'Snel schakelen', 'description' => 'Korte doorlooptijd zonder gedoe.'],
                        ['icon' => 'check-badge', 'title' => 'Kwaliteit', 'description' => 'Afspraken nakomen is de basis.'],
                    ],
                ],
                'components' => $isTaxi ? ['taxi.tarieven'] : [],
            ],
            [
                'slug' => 'tarieven',
                'title' => 'Tarieven',
                'menu_title' => 'Tarieven',
                'page_type' => 'custom',
                'show_in_menu' => true,
                'meta_description' => mb_substr('Tarieven van '.$brand.' in '.$city.'.', 0, 160),
                'hero' => [
                    'title' => 'Heldere tarieven',
                    'title_highlight' => 'tarieven',
                    'subtitle' => 'Geen verrassingen achteraf. Vraag gerust een vaste prijs aan.',
                    'cta_primary_text' => 'Vraag een prijs',
                    'cta_primary_url' => '/contact',
                    'cta_secondary_text' => '',
                    'cta_secondary_url' => '',
                    'image_prompt' => 'Photorealistic close-up of a clean taxi meter and steering wheel, no text',
                ],
                'components' => $isTaxi ? ['taxi.tarieven'] : [],
                'text_block' => [
                    'content' => '<p>De prijs hangt af van afstand, tijdstip en voertuig. Neem contact op voor een offerte op maat.</p>',
                ],
            ],
        ];

        $pages = array_slice($blueprints, 0, $maxPages);

        return [
            'brand' => ['name' => $brand, 'tagline' => $brief],
            'pages' => $pages,
            'footer' => [
                'tagline' => $brief,
                'copyright' => '© {year} '.$brand.'. Alle rechten voorbehouden.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array{brand: array{name: string, tagline: string}, pages: list<array<string, mixed>>, footer: array{tagline: string, copyright: string}}
     */
    private function normalizePlan(array $raw, Company $company, FrontendTheme $theme, int $maxPages): array
    {
        $brandName = trim((string) data_get($raw, 'brand.name', $company->name)) ?: (string) $company->name;
        $tagline = trim((string) data_get($raw, 'brand.tagline', ''));
        $pagesIn = is_array($raw['pages'] ?? null) ? array_values($raw['pages']) : [];
        $pages = [];
        $usedSlugs = [];

        foreach ($pagesIn as $row) {
            if (count($pages) >= $maxPages || ! is_array($row)) {
                break;
            }
            $pageType = $this->normalizePageType((string) ($row['page_type'] ?? 'custom'), (string) ($row['slug'] ?? ''));
            $slug = $this->normalizeSlug((string) ($row['slug'] ?? ''), $pageType, $usedSlugs);
            $usedSlugs[] = $slug;
            $title = trim((string) ($row['title'] ?? '')) ?: ($pageType === 'home' ? $brandName : Str::headline($slug));
            $pages[] = [
                'slug' => $slug,
                'title' => mb_substr($title, 0, 255),
                'menu_title' => mb_substr(trim((string) ($row['menu_title'] ?? '')) ?: $title, 0, 80),
                'page_type' => $pageType,
                'meta_description' => mb_substr(trim((string) ($row['meta_description'] ?? $tagline)), 0, 500),
                'show_in_menu' => array_key_exists('show_in_menu', $row) ? (bool) $row['show_in_menu'] : true,
                'hero' => is_array($row['hero'] ?? null) ? $row['hero'] : [],
                'why_nexa' => is_array($row['why_nexa'] ?? null) ? $row['why_nexa'] : [],
                'features' => is_array($row['features'] ?? null) ? $row['features'] : [],
                'stats' => is_array($row['stats'] ?? null) ? $row['stats'] : [],
                'cta' => is_array($row['cta'] ?? null) ? $row['cta'] : [],
                'featured_services' => is_array($row['featured_services'] ?? null) ? $row['featured_services'] : [],
                'text_block' => is_array($row['text_block'] ?? null) ? $row['text_block'] : [],
                'components' => is_array($row['components'] ?? null) ? $row['components'] : [],
            ];
        }

        if ($pages === []) {
            $fallback = $this->fallbackPlan($company, $theme, $tagline, $maxPages, ['pages' => [], 'summary' => '']);

            return $this->normalizePlan($fallback, $company, $theme, $maxPages);
        }

        if (($pages[0]['page_type'] ?? '') !== 'home') {
            $pages[0]['page_type'] = 'home';
            $pages[0]['slug'] = 'home';
        }

        return [
            'brand' => ['name' => $brandName, 'tagline' => $tagline],
            'pages' => $pages,
            'footer' => [
                'tagline' => trim((string) data_get($raw, 'footer.tagline', $tagline)),
                'copyright' => trim((string) data_get($raw, 'footer.copyright', '')) ?: ('© {year} '.$brandName.'. Alle rechten voorbehouden.'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     * @return array<string, mixed>
     */
    private function applyPagePlanToSections(array $sections, array $pagePlan, FrontendTheme $theme, bool $isHome, Company $company): array
    {
        $hero = is_array($sections['hero'] ?? null) ? $sections['hero'] : [];
        foreach (['title', 'title_highlight', 'subtitle', 'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url'] as $field) {
            $value = AiScalar::string(data_get($pagePlan, 'hero.'.$field, ''));
            if ($value !== '') {
                $hero[$field] = $value;
            }
        }
        $sections['hero'] = $hero;

        $order = is_array($sections['section_order'] ?? null) ? array_values($sections['section_order']) : ['hero'];

        if ($isHome) {
            $this->mergeAssocSection($sections, 'why_nexa', $pagePlan['why_nexa'] ?? [], ['title', 'subtitle']);
            $this->mergeFeatures($sections, $pagePlan['features'] ?? []);
            $this->mergeStats($sections, $pagePlan['stats'] ?? []);
            $this->mergeAssocSection($sections, 'cta', $pagePlan['cta'] ?? [], ['title', 'subtitle', 'cta_primary_text', 'cta_primary_url', 'cta_secondary_text', 'cta_secondary_url']);
            $this->mergeFeaturedServices($sections, $pagePlan['featured_services'] ?? []);
            if ($this->hasMeaningfulFeaturedServices($sections['featured_services'] ?? [])) {
                $order = $this->insertBeforeCta($order, 'featured_services');
            }
            $homeText = AiScalar::string(data_get($pagePlan, 'text_block.content', ''));
            if ($homeText !== '') {
                $block = is_array($sections['text_block'] ?? null) ? $sections['text_block'] : WebsitePage::defaultHomeSections()['text_block'];
                $block['content'] = $homeText;
                $this->applyTextBlockLayoutFromPlan($block, $pagePlan);
                $sections['text_block'] = $block;
                $order = $this->insertBeforeCta($order, 'text_block');
            }
        } else {
            $text = AiScalar::string(data_get($pagePlan, 'text_block.content', ''));
            if ($text !== '') {
                $block = is_array($sections['text_block'] ?? null) ? $sections['text_block'] : WebsitePage::defaultHomeSections()['text_block'];
                $block['content'] = $text;
                $this->applyTextBlockLayoutFromPlan($block, $pagePlan);
                $sections['text_block'] = $block;
                $order[] = 'text_block';
            }
            $this->mergeFeaturedServices($sections, $pagePlan['featured_services'] ?? []);
            if ($this->hasMeaningfulFeaturedServices($sections['featured_services'] ?? [])) {
                $order[] = 'featured_services';
            }
        }

        foreach ($this->allowedComponentKeys($pagePlan['components'] ?? []) as $componentKey) {
            if (! in_array($componentKey, $order, true)) {
                $order[] = $componentKey;
            }
            if (! isset($sections[$componentKey]) || ! is_array($sections[$componentKey])) {
                $id = FrontendComponentService::componentIdFromKey($componentKey);
                $sections[$componentKey] = $id ? $this->components->defaultSectionData($id) : [];
            }
        }

        $sections['section_order'] = array_values(array_unique($order));
        $sections = $this->fillFooter($sections, $company, $isHome);

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $pagePlan
     */
    private function applyTextBlockLayoutFromPlan(array &$block, array $pagePlan): void
    {
        $alignment = strtolower(AiScalar::string(data_get($pagePlan, 'text_block.alignment', '')));
        if (in_array($alignment, ['left', 'right', 'center', 'full'], true)) {
            $block['alignment'] = $alignment;
        }
        $imageUrl = AiScalar::string(data_get($pagePlan, 'text_block.image_url', ''));
        if ($imageUrl !== '') {
            $block['image_url'] = $imageUrl;
        }
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $fields
     */
    private function mergeAssocSection(array &$sections, string $key, array $incoming, array $fields): void
    {
        $block = is_array($sections[$key] ?? null) ? $sections[$key] : [];
        foreach ($fields as $field) {
            $value = AiScalar::string($incoming[$field] ?? '');
            if ($value !== '') {
                $block[$field] = $value;
            }
        }
        $sections[$key] = $block;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $incoming
     */
    private function mergeFeatures(array &$sections, array $incoming): void
    {
        $block = is_array($sections['features'] ?? null) ? $sections['features'] : ['items' => []];
        $title = AiScalar::string($incoming['section_title'] ?? '');
        if ($title !== '') {
            $block['section_title'] = $title;
        }
        $items = is_array($incoming['items'] ?? null) ? array_values($incoming['items']) : [];
        $mapped = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $itemTitle = AiScalar::string($item['title'] ?? '');
            if ($itemTitle === '') {
                continue;
            }
            $mapped[] = [
                'title' => $itemTitle,
                'description' => AiScalar::string($item['description'] ?? ''),
                'icon' => $this->normalizeIcon(AiScalar::string($item['icon'] ?? 'bolt') ?: 'bolt'),
                'icon_size' => 'medium',
                'icon_align' => 'center',
            ];
            if (count($mapped) >= 6) {
                break;
            }
        }
        if ($mapped !== []) {
            $block['items'] = $mapped;
        }
        $sections['features'] = $block;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $incoming
     */
    private function mergeStats(array &$sections, array $incoming): void
    {
        $block = is_array($sections['stats'] ?? null) ? $sections['stats'] : ['items' => []];
        $items = is_array($incoming['items'] ?? null) ? array_values($incoming['items']) : [];
        $mapped = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $value = AiScalar::string($item['value'] ?? '');
            $label = AiScalar::string($item['label'] ?? '');
            if ($value === '' && $label === '') {
                continue;
            }
            $mapped[] = [
                'value' => $value !== '' ? $value : '—',
                'label' => $label,
                'value_color' => '',
                'value_size' => '22',
                'label_size' => '16',
            ];
            if (count($mapped) >= 4) {
                break;
            }
        }
        if ($mapped !== []) {
            $block['items'] = $mapped;
        }
        $sections['stats'] = $block;
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $incoming
     */
    private function mergeFeaturedServices(array &$sections, array $incoming): void
    {
        $block = is_array($sections['featured_services'] ?? null)
            ? $sections['featured_services']
            : WebsitePage::defaultHomeSections()['featured_services'];
        foreach (['title', 'subtitle'] as $field) {
            $value = AiScalar::string($incoming[$field] ?? '');
            if ($value !== '') {
                $block[$field] = $value;
            }
        }
        $speed = AiScalar::string($incoming['animation_speed'] ?? '');
        if (in_array($speed, ['fast', 'normal', 'slow', 'slower'], true)) {
            $block['animation_speed'] = $speed;
        } elseif (! isset($block['animation_speed']) || $block['animation_speed'] === '') {
            $block['animation_speed'] = 'slow';
        }
        $items = is_array($incoming['items'] ?? null) ? array_values($incoming['items']) : [];
        $mapped = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = AiScalar::string($item['title'] ?? '');
            if ($title === '') {
                continue;
            }
            $mapped[] = [
                'icon' => $this->normalizeIcon(AiScalar::string($item['icon'] ?? 'briefcase') ?: 'briefcase'),
                'title' => $title,
                'description' => AiScalar::string($item['description'] ?? ''),
            ];
            if (count($mapped) >= 6) {
                break;
            }
        }
        if ($mapped !== []) {
            $block['items'] = $mapped;
        }
        $sections['featured_services'] = $block;
    }

    /**
     * @param  mixed  $block
     */
    private function hasMeaningfulFeaturedServices(mixed $block): bool
    {
        if (! is_array($block)) {
            return false;
        }
        $items = is_array($block['items'] ?? null) ? $block['items'] : [];
        foreach ($items as $item) {
            if (is_array($item) && trim((string) ($item['title'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $order
     * @return list<string>
     */
    private function insertBeforeCta(array $order, string $key): array
    {
        if (in_array($key, $order, true)) {
            return $order;
        }
        $ctaIndex = array_search('cta', $order, true);
        if ($ctaIndex === false) {
            $order[] = $key;

            return $order;
        }
        array_splice($order, (int) $ctaIndex, 0, [$key]);

        return array_values($order);
    }

    /**
     * @param  mixed  $components
     * @return list<string>
     */
    private function allowedComponentKeys(mixed $components): array
    {
        if (! is_array($components)) {
            return [];
        }
        $keys = [];
        foreach (AiScalar::componentIds($components) as $id) {
            if ($id === '' || $this->components->getById($id) === null) {
                continue;
            }
            $key = 'component:'.$id;
            if ($this->components->isAllowedComponentSectionKey($key)) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    private function applyBrandColors(array $sections, string $primary, string $secondary): array
    {
        $onPrimary = $this->contrastingText($primary);
        if (isset($sections['hero']) && is_array($sections['hero'])) {
            $sections['hero']['cta_primary_bg'] = $primary;
            $sections['hero']['cta_primary_border'] = $primary;
            $sections['hero']['cta_primary_text_color'] = $onPrimary;
            $sections['hero']['cta_secondary_bg'] = 'transparent';
            $sections['hero']['cta_secondary_border'] = '#ffffff';
            $sections['hero']['cta_secondary_text_color'] = '#ffffff';
            $sections['hero']['title_highlight_color'] = $primary;
            $sections['hero']['overlay'] = true;
            $sections['hero']['overlay_color_from'] = $secondary;
            $sections['hero']['overlay_color_to'] = $primary;
            $sections['hero']['overlay_opacity'] = $sections['hero']['overlay_opacity'] ?? 72;
        }
        if (isset($sections['cta']) && is_array($sections['cta'])) {
            $sections['cta']['cta_primary_bg'] = $primary;
            $sections['cta']['cta_primary_border'] = $primary;
            $sections['cta']['cta_primary_text_color'] = $onPrimary;
        }
        if (isset($sections['featured_services']) && is_array($sections['featured_services'])) {
            $items = is_array($sections['featured_services']['items'] ?? null) ? $sections['featured_services']['items'] : [];
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $items[$i]['icon_color'] = $primary;
            }
            $sections['featured_services']['items'] = $items;
        }
        if (isset($sections['stats']) && is_array($sections['stats'])) {
            $items = is_array($sections['stats']['items'] ?? null) ? $sections['stats']['items'] : [];
            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $items[$i]['value_color'] = $primary;
            }
            $sections['stats']['items'] = $items;
        }
        foreach ($sections as $key => $block) {
            if (! is_string($key) || ! str_contains($key, 'boekingsmodule') || ! is_array($block)) {
                continue;
            }
            $style = is_array($block['style'] ?? null) ? $block['style'] : [];
            $style['primary_color'] = $primary;
            $style['active_tab_color'] = $primary;
            $block['style'] = $style;
            $sections[$key] = $block;
        }

        return $sections;
    }

    private function contrastingText(string $hex): string
    {
        $hex = ltrim($this->normalizeHex($hex) ?: '#111827', '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.62 ? '#111827' : '#ffffff';
    }

    /**
     * @param  array<string, mixed>  $sections
     * @param  array<string, mixed>  $pagePlan
     */
    private function applyGeneratedImages(array &$sections, array $pagePlan, string $companySlug, string $slug): int
    {
        return $this->attachGeneratedImages($sections, $pagePlan, $companySlug, $slug, false);
    }

    private function generateAndStoreImage(string $prompt, string $companySlug, string $pageSlug): ?string
    {
        $apiKey = config('services.openai.api_key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        $model = (string) config('services.openai.image_model', 'dall-e-3');
        $payload = [
            'model' => $model,
            'prompt' => mb_substr($prompt, 0, 3500),
            'n' => 1,
            'size' => (string) config('services.openai.image_size', '1792x1024'),
        ];
        if ($model === 'dall-e-3') {
            $payload['quality'] = (string) config('services.openai.image_quality', 'hd');
            if (! in_array($payload['quality'], ['standard', 'hd'], true)) {
                $payload['quality'] = 'hd';
            }
        } elseif (str_starts_with($model, 'gpt-image')) {
            $payload['quality'] = (string) config('services.openai.image_quality', 'high');
            if (! in_array($payload['quality'], ['low', 'medium', 'high'], true)) {
                $payload['quality'] = 'high';
            }
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/images/generations', $payload);
            if (! $response->successful()) {
                Log::warning('Website AI image: OpenAI HTTP-fout', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return null;
            }
            $b64 = $response->json('data.0.b64_json');
            $remoteUrl = $response->json('data.0.url');
            $binary = null;
            if (is_string($b64) && $b64 !== '') {
                $decoded = base64_decode($b64, true);
                $binary = $decoded !== false ? $decoded : null;
            } elseif (is_string($remoteUrl) && $remoteUrl !== '') {
                $download = Http::timeout(60)->get($remoteUrl);
                if ($download->successful()) {
                    $binary = $download->body();
                }
            }
            if ($binary === null || $binary === '') {
                return null;
            }
            $dir = 'website/hero';
            $name = 'ai-'.Str::slug($companySlug.'-'.$pageSlug).'-'.Str::lower(Str::random(8)).'.png';
            $path = $dir.'/'.$name;
            Storage::disk('public')->put($path, $binary);

            return '/storage/'.$path;
        } catch (Throwable $e) {
            Log::warning('Website AI image: uitzondering', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $sections
     * @return array<string, mixed>
     */
    private function fillFooter(array $sections, Company $company, bool $isHome): array
    {
        $footer = is_array($sections['footer'] ?? null) ? $sections['footer'] : [];
        if (! $isHome) {
            $footer['inherit_from_home'] = true;
            $sections['footer'] = $footer;

            return $sections;
        }
        $footer['map_postcode'] = trim((string) ($company->postal_code ?? ''));
        $footer['map_huisnummer'] = trim((string) (($company->house_number ?? '').' '.($company->house_number_extension ?? '')));
        $footer['map_street'] = trim((string) ($company->street ?? ''));
        $footer['map_city'] = trim((string) ($company->city ?? ''));
        $footer['map_lat'] = $company->latitude;
        $footer['map_lng'] = $company->longitude;
        $footer['support_links'] = [];
        $sections['footer'] = $footer;
        $sections['copyright'] = '© {year} '.$company->name.'. Alle rechten voorbehouden.';

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @param  list<array{label: string, url: string}>  $menuPages
     */
    private function syncHomeFooter(Company $company, array $plan, array $menuPages, FrontendTheme $theme, ?string $moduleName): void
    {
        $home = $this->findExistingPage($company, 'home', $moduleName);
        if (! $home) {
            return;
        }
        $sections = $home->home_sections;
        if (! is_array($sections)) {
            $sections = WebsitePage::defaultHomeSectionsForTheme((string) $theme->slug);
        }
        $footer = is_array($sections['footer'] ?? null) ? $sections['footer'] : [];
        $tagline = trim((string) data_get($plan, 'footer.tagline', ''));
        if ($tagline !== '') {
            $footer['tagline'] = $tagline;
        }
        if ($menuPages !== []) {
            $footer['quick_links'] = array_map(
                fn (array $row) => ['label' => $row['label'], 'url' => $row['url']],
                $menuPages
            );
        }
        $footer['support_links'] = $this->supportLinksFromExistingPages($menuPages);
        $sections['footer'] = $footer;
        $copyright = trim((string) data_get($plan, 'footer.copyright', ''));
        if ($copyright !== '') {
            $sections['copyright'] = $copyright;
        }
        $home->home_sections = $sections;
        $home->save();
    }

    /**
     * @param  list<array{label: string, url: string}>  $menuPages
     * @return list<array{label: string, url: string}>
     */
    private function supportLinksFromExistingPages(array $menuPages): array
    {
        $allowed = ['/help', '/faq', '/help-faq', '/privacy', '/voorwaarden', '/terms', '/cookies', '/cookiebeleid'];
        $out = [];
        foreach ($menuPages as $row) {
            $url = trim((string) ($row['url'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            if ($url === '' || $label === '') {
                continue;
            }
            $path = parse_url($url, PHP_URL_PATH);
            $path = is_string($path) && $path !== '' ? $path : explode('#', $url)[0];
            $path = '/'.strtolower(ltrim((string) $path, '/'));
            if ($path !== '/') {
                $path = rtrim($path, '/');
            }
            if (in_array($path, $allowed, true)) {
                $out[] = ['label' => $label, 'url' => $url];
            }
        }

        return $out;
    }

    private function resolveWebsiteModuleName(Company $company): ?string
    {
        $existingHome = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('page_type', 'home')->orWhere('slug', 'home');
            })
            ->whereNotNull('module_name')
            ->where('module_name', '!=', '')
            ->orderBy('id')
            ->first();
        $fromHome = trim((string) ($existingHome?->module_name ?? ''));
        if ($fromHome !== '') {
            return strtolower($fromHome);
        }
        if ($company->hasTaxiModule()) {
            return 'taxi';
        }
        if ($company->hasSkillmatchingModule()) {
            return 'skillmatching';
        }
        $mod = $company->modules()
            ->where('modules.installed', true)
            ->where('modules.active', true)
            ->orderBy('modules.id')
            ->first();
        $name = trim((string) ($mod?->name ?? ''));

        return $name !== '' ? strtolower($name) : null;
    }

    private function pageModuleName(?WebsitePage $existing, ?string $moduleName): ?string
    {
        $fromExisting = trim((string) ($existing?->module_name ?? ''));
        if ($fromExisting !== '') {
            return $fromExisting;
        }
        $moduleName = trim((string) ($moduleName ?? ''));

        return $moduleName !== '' ? $moduleName : null;
    }

    private function findExistingPage(Company $company, string $slug, ?string $moduleName = null): ?WebsitePage
    {
        $pages = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug);
                if ($slug === 'home') {
                    $q->orWhere('page_type', 'home');
                }
            })
            ->orderBy('id')
            ->get();
        if ($pages->isEmpty()) {
            return null;
        }

        $moduleName = strtolower(trim((string) ($moduleName ?? '')));
        if ($moduleName !== '') {
            $match = $pages->first(
                fn (WebsitePage $page) => strtolower(trim((string) $page->module_name)) === $moduleName
            );
            if ($match) {
                return $match;
            }
        }

        return $pages->first(fn (WebsitePage $page) => filled($page->module_name))
            ?? $pages->first();
    }

    /**
     * Eén Home per tenant: extra home-rijen verwijderen.
     * Inhoud van de meest complete home (vaak de gegenereerde) gaat naar de canonieke rij (module taxi e.d.).
     */
    private function collapseDuplicateHomes(Company $company, ?string $moduleName): void
    {
        $homes = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('page_type', 'home')->orWhere('slug', 'home');
            })
            ->orderBy('id')
            ->get();
        if ($homes->isEmpty()) {
            return;
        }

        $canonical = $this->findExistingPage($company, 'home', $moduleName) ?? $homes->first();
        if ($canonical && $moduleName && trim((string) $canonical->module_name) === '') {
            $canonical->module_name = $moduleName;
            $canonical->save();
        }

        if ($homes->count() === 1) {
            return;
        }

        $contentSource = $homes->sortByDesc(function (WebsitePage $page) {
            $title = mb_strtolower(trim((string) $page->title));
            $score = 0;
            if ($title !== '' && $title !== 'home') {
                $score += 100;
            }
            if (is_array($page->home_sections) && $page->home_sections !== []) {
                $score += 20;
            }

            return ($score * 1_000_000) + (int) $page->id;
        })->first();

        if ($contentSource && (int) $contentSource->id !== (int) $canonical->id) {
            $canonical->fill([
                'title' => $contentSource->title,
                'menu_title' => $contentSource->menu_title ?: $canonical->menu_title,
                'meta_description' => $contentSource->meta_description,
                'home_sections' => $contentSource->home_sections,
                'frontend_theme_id' => $contentSource->frontend_theme_id ?: $canonical->frontend_theme_id,
                'show_in_menu' => true,
            ]);
            $canonical->save();
        }

        foreach ($homes as $home) {
            if ((int) $home->id === (int) $canonical->id) {
                continue;
            }
            $home->delete();
        }
    }

    private function publicPath(string $pageType, string $slug): string
    {
        return match ($pageType) {
            'home' => '/',
            'about' => '/over-ons',
            'contact' => '/contact',
            default => '/'.$slug,
        };
    }

    private function normalizePageType(string $type, string $slug): string
    {
        $type = strtolower(trim($type));
        if (in_array($type, ['home', 'about', 'contact', 'custom'], true)) {
            return $type;
        }
        $slug = strtolower($slug);
        if (in_array($slug, ['home', 'index'], true)) {
            return 'home';
        }
        if (in_array($slug, ['over-ons', 'overons', 'about'], true)) {
            return 'about';
        }
        if (str_contains($slug, 'contact')) {
            return 'contact';
        }

        return 'custom';
    }

    /**
     * @param  list<string>  $used
     */
    private function normalizeSlug(string $slug, string $pageType, array $used): string
    {
        $slug = Str::slug($slug);
        if ($pageType === 'home') {
            $slug = 'home';
        } elseif ($pageType === 'about' && $slug === '') {
            $slug = 'over-ons';
        } elseif ($pageType === 'contact' && $slug === '') {
            $slug = 'contact';
        } elseif ($slug === '') {
            $slug = 'pagina';
        }
        $base = $slug;
        $i = 2;
        while (in_array($slug, $used, true)) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function normalizeIcon(string $icon): string
    {
        $icon = Str::slug($icon);
        if (in_array($icon, self::ALLOWED_ICONS, true)) {
            return $icon;
        }

        return 'bolt';
    }

    private function normalizeHex(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $value)) {
            return strtolower($value);
        }
        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $value, $m)) {
            $h = $m[1];

            return strtolower('#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2]);
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function looksLikeTaxi(Company $company, string $context, array $source): bool
    {
        $hay = strtolower(implode(' ', [
            (string) $company->name,
            (string) ($company->industry ?? ''),
            (string) ($company->description ?? ''),
            $context,
            (string) ($source['summary'] ?? ''),
        ]));

        return (bool) preg_match('/taxi|vervoer|chauffeur|ritten|transfer|coach/i', $hay);
    }
}

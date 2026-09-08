<?php

namespace Tests\Feature;

use App\Models\AiWebsiteGeneration;
use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\User;
use App\Models\WebsiteMedia;
use App\Models\WebsitePage;
use App\Services\WebsiteAiSiteCopy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminWebsiteAiGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function super_admin_can_open_generator_and_sees_menu_item(): void
    {
        $admin = $this->superAdmin();
        Company::query()->create(['name' => 'Taxi AI Demo', 'is_active' => true, 'city' => 'Zwolle']);
        FrontendTheme::query()->create([
            'slug' => 'landwind',
            'name' => 'Landwind',
            'is_active' => true,
            'settings' => ['primary_color' => '#7e3af2'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.website-ai.create'))
            ->assertOk()
            ->assertSee('Genereer website AI', false)
            ->assertSee('id="source_url"', false)
            ->assertSee('id="max_pages"', false)
            ->assertSee('id="style"', false)
            ->assertSee('id="tone"', false)
            ->assertSee('name="goals[]"', false)
            ->assertSee('Landwind (thema)', false)
            ->assertSee('Taxi AI Demo', false)
            ->assertSee(route('admin.website-ai.create', [], false), false);
    }

    #[Test]
    public function company_admin_cannot_open_or_post_generator(): void
    {
        $user = User::factory()->create();
        $user->assignRole('company-admin');
        $company = Company::query()->create(['name' => 'Geen AI', 'is_active' => true]);
        $theme = FrontendTheme::query()->create(['slug' => 'modern', 'name' => 'Modern', 'is_active' => true]);

        $get = $this->actingAs($user)->get(route('admin.website-ai.create'));
        $this->assertTrue(
            in_array($get->status(), [403, 302, 303], true),
            'Company-admin mag de generator niet zien, got: '.$get->status()
        );
        if ($get->status() === 200) {
            $get->assertDontSee('id="website-ai-form"', false);
        }

        $post = $this->actingAs($user)->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme));
        $this->assertTrue(
            in_array($post->status(), [403, 302, 303], true),
            'Company-admin mag niet genereren, got: '.$post->status()
        );
        $this->assertSame(0, WebsitePage::query()->where('company_id', $company->id)->count());
    }

    #[Test]
    public function super_admin_generates_pages_without_openai_key(): void
    {
        config(['services.openai.api_key' => null]);
        $admin = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Taxi Royaal Zwolle',
            'is_active' => true,
            'city' => 'Zwolle',
            'industry' => 'Taxi',
            'phone' => '0381234567',
            'email' => 'info@taxiroyaal.test',
        ]);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
        $company->modules()->attach($taxi->id);
        $theme = FrontendTheme::query()->create([
            'slug' => 'landwind',
            'name' => 'Landwind',
            'is_active' => true,
            'settings' => ['primary_color' => '#7e3af2'],
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'max_pages' => 3,
                'context' => 'Taxibedrijf in Zwolle: luchthavenvervoer en zakelijke ritten.',
                'generate_images' => '0',
            ]));

        $this->assertSame(3, WebsitePage::query()->where('company_id', $company->id)->count());
        $home = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->first();
        $this->assertNotNull($home);
        $response->assertRedirect($this->builderUrl($home, $company));
        $this->assertSame('home', $home->page_type);
        $this->assertFalse((bool) $home->is_active);
        $this->assertSame($theme->id, (int) $home->frontend_theme_id);
        $this->assertSame($theme->id, (int) $company->fresh()->frontend_theme_id);
        $sections = $home->home_sections;
        $this->assertSame('#1e3a8a', $sections['hero']['cta_primary_bg'] ?? null);
        $this->assertSame('#1e3a8a', $sections['featured_services']['items'][0]['icon_color'] ?? null);
        $this->assertSame('#1e3a8a', $sections['component:taxi.boekingsmodule_v2']['style']['primary_color'] ?? null);
        $company->refresh();
        $this->assertSame('#1e3a8a', $company->website_theme_settings['primary_color'] ?? null);
        $this->assertSame('#0f172a', $company->website_theme_settings['secondary_color'] ?? null);
        $this->assertSame('#7e3af2', $theme->fresh()->settings['primary_color'] ?? null);
        $this->assertSame('#1e3a8a', $theme->fresh()->getSettings($company)['primary_color'] ?? null);
        $this->assertContains('hero', $sections['section_order']);
        $this->assertContains('text_block', $sections['section_order']);
        $this->assertNotEmpty($sections['text_block']['content'] ?? '');
        $this->assertSame('left', $sections['text_block']['alignment'] ?? null);
        $extraComponents = array_values(array_filter(
            $sections['section_order'],
            fn ($key) => is_string($key) && str_starts_with($key, 'component:') && ! str_contains($key, 'boekingsmodule')
        ));
        $this->assertGreaterThanOrEqual(2, count($extraComponents));
        $this->assertLessThanOrEqual(4, count($extraComponents));
        foreach ($extraComponents as $key) {
            $id = substr($key, strlen('component:'));
            $this->assertContains($id, WebsiteAiSiteCopy::HOME_COMPONENT_POOL);
        }
        $this->assertSame([], $sections['footer']['support_links'] ?? ['x']);
        $this->assertStringNotContainsString('/help', json_encode($sections['footer']));
        $heroIndex = array_search('hero', $sections['section_order'], true);
        $bookingIndex = array_search('component:taxi.boekingsmodule_v2', $sections['section_order'], true);
        $this->assertNotFalse($heroIndex);
        $this->assertNotFalse($bookingIndex);
        $this->assertSame($heroIndex + 1, $bookingIndex);
        $this->assertNotEmpty($sections['component:taxi.boekingsmodule_v2']['title'] ?? null);
        $this->assertTrue(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'contact')->exists());
        $this->assertTrue(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'over-ons')->exists());
        $contact = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'contact')->first();
        $this->assertFalse((bool) $contact->is_active);
        $this->assertContains('email_template', $contact->home_sections['section_order']);
        $this->assertNotEmpty($contact->home_sections['email_template']['template_id'] ?? null);
        $generation = AiWebsiteGeneration::query()->where('company_id', $company->id)->first();
        $this->assertNotNull($generation);
        $this->assertSame(AiWebsiteGeneration::STATUS_COMPLETED, $generation->status);
        $this->assertSame($home->id, (int) $generation->homepage_page_id);
        $this->assertNotEmpty($generation->sitemap_json);
        $this->assertNotEmpty($generation->website_brief_json);
    }

    #[Test]
    public function generator_respects_max_pages_and_skips_existing_slugs(): void
    {
        config(['services.openai.api_key' => null]);
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Skip Taxi', 'is_active' => true, 'city' => 'Deventer', 'industry' => 'Taxi']);
        $theme = FrontendTheme::query()->create(['slug' => 'modern', 'name' => 'Modern', 'is_active' => true]);
        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Oude home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $home = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->first();

        $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'max_pages' => 2,
                'replace_existing' => '0',
                'generate_images' => '0',
            ]))
            ->assertRedirect($this->builderUrl($home, $company));

        $this->assertSame('Oude home', WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->value('title'));
        $this->assertSame(2, WebsitePage::query()->where('company_id', $company->id)->count());
        $this->assertTrue((bool) $home->fresh()->is_active);
        $this->assertTrue(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'contact')->exists());
        $this->assertFalse(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'over-ons')->exists());
    }

    #[Test]
    public function generator_does_not_create_second_home_when_taxi_home_exists(): void
    {
        config(['services.openai.api_key' => null]);
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Taxi Tosun AI', 'is_active' => true, 'city' => 'Enschede', 'industry' => 'Taxi']);
        $theme = FrontendTheme::query()->create(['slug' => 'vue-material-kit', 'name' => 'Vue Material Kit', 'is_active' => true]);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
        $company->modules()->attach($taxi->id);
        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Taxi home',
            'menu_title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $existing = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->first();

        $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'max_pages' => 3,
                'replace_existing' => '0',
                'generate_images' => '0',
            ]))
            ->assertRedirect($this->builderUrl($existing, $company));

        $homes = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('page_type', 'home')->orWhere('slug', 'home');
            })
            ->get();
        $this->assertCount(1, $homes);
        $this->assertSame('taxi', $homes->first()->module_name);
        $this->assertSame('Taxi home', $homes->first()->title);
        $this->assertTrue(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'contact')->exists());
        $this->assertTrue(WebsitePage::query()->where('company_id', $company->id)->where('slug', 'over-ons')->exists());
        $this->assertFalse(
            WebsitePage::query()
                ->where('company_id', $company->id)
                ->where('slug', 'home')
                ->where(fn ($q) => $q->whereNull('module_name')->orWhere('module_name', ''))
                ->exists()
        );
    }

    #[Test]
    public function generator_collapses_existing_duplicate_homes(): void
    {
        config(['services.openai.api_key' => null]);
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Dubbele Home Taxi', 'is_active' => true, 'city' => 'Hengelo', 'industry' => 'Taxi']);
        $theme = FrontendTheme::query()->create(['slug' => 'landwind', 'name' => 'Landwind', 'is_active' => true]);
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'description' => 'Test',
            'icon' => 'ki-filled ki-car',
            'installed' => true,
            'active' => true,
        ]);
        $company->modules()->attach($taxi->id);
        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'menu_title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Welkom bij Dubbele Home Taxi',
            'menu_title' => 'Home',
            'page_type' => 'home',
            'module_name' => null,
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 1,
            'home_sections' => ['hero' => ['title' => 'Gegenereerde hero']],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'max_pages' => 2,
                'replace_existing' => '0',
                'generate_images' => '0',
            ]))
            ->assertRedirect();

        $homes = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where(function ($q) {
                $q->where('page_type', 'home')->orWhere('slug', 'home');
            })
            ->get();
        $this->assertCount(1, $homes);
        $this->assertSame('taxi', $homes->first()->module_name);
        $this->assertSame('Welkom bij Dubbele Home Taxi', $homes->first()->title);
        $this->assertSame('Gegenereerde hero', $homes->first()->home_sections['hero']['title'] ?? null);
    }

    #[Test]
    public function generator_uses_openai_json_and_source_website(): void
    {
        config(['services.openai.api_key' => 'sk-test-website-ai']);
        Storage::fake('public');
        Storage::fake('local');
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'OpenAI Taxi', 'is_active' => true, 'city' => 'Apeldoorn']);
        $theme = FrontendTheme::query()->create(['slug' => 'play-tailwind', 'name' => 'Play', 'is_active' => true]);

        Http::fake([
            'https://oude-site.example/*' => Http::response(
                '<html><head><title>Oude Site</title></head><body><h1>Taxi Apeldoorn</h1><p>Al 20 jaar luchthavenvervoer.</p><a href="/contact">Contact</a></body></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'brand' => ['name' => 'OpenAI Taxi', 'tagline' => 'Ritten zonder gedoe'],
                            'pages' => [[
                                'slug' => 'home',
                                'title' => 'OpenAI Taxi Apeldoorn',
                                'menu_title' => 'Home',
                                'page_type' => 'home',
                                'meta_description' => 'Taxi in Apeldoorn, luchthavenvervoer.',
                                'show_in_menu' => true,
                                'hero' => [
                                    'title' => 'Ritten zonder gedoe',
                                    'title_highlight' => 'gedoe',
                                    'subtitle' => 'Luchthavenvervoer in Apeldoorn.',
                                    'cta_primary_text' => 'Boeken',
                                    'cta_primary_url' => '/contact',
                                    'image_prompt' => 'Photorealistic taxi',
                                ],
                                'why_nexa' => ['title' => 'Over ons', 'subtitle' => 'Al 20 jaar onderweg.'],
                                'features' => ['section_title' => 'Voordelen', 'items' => [
                                    ['title' => 'Op tijd', 'description' => 'Altijd klaarstaan.', 'icon' => 'clock'],
                                ]],
                                'components' => ['taxi.boekingsmodule_v2'],
                            ]],
                            'footer' => ['tagline' => 'Ritten zonder gedoe', 'copyright' => '© {year} OpenAI Taxi'],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode('fake-png-bytes')]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'source_type' => 'url',
                'source_url' => 'https://oude-site.example',
                'max_pages' => 1,
                'generate_images' => '1',
                'context' => 'Taxi Apeldoorn, vooral Schiphol.',
            ]))
            ->assertRedirect();

        $home = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->first();
        $this->assertNotNull($home);
        $this->assertFalse((bool) $home->is_active);
        $this->assertSame('OpenAI Taxi Apeldoorn', $home->title);
        $this->assertSame('Ritten zonder gedoe', $home->home_sections['hero']['title'] ?? null);
        $this->assertContains('component:taxi.boekingsmodule_v2', $home->home_sections['section_order']);
        $this->assertNotEmpty($home->home_sections['hero']['background_image_url'] ?? '');
        $this->assertStringContainsString('/website-media/', (string) $home->home_sections['hero']['background_image_url']);
        $this->assertNotEmpty($home->home_sections['text_block']['image_url'] ?? '');
        $this->assertStringContainsString('/website-media/', (string) $home->home_sections['text_block']['image_url']);
        $this->assertContains($home->home_sections['text_block']['alignment'] ?? '', ['left', 'right']);
        $this->assertSame(2, WebsiteMedia::query()->count());
        Storage::disk('local')->assertExists(WebsiteMedia::query()->first()->encrypted_path);
    }

    #[Test]
    public function generator_accepts_openai_array_fields_in_brief(): void
    {
        config(['services.openai.api_key' => 'sk-test-website-ai']);
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Array Brief Taxi', 'is_active' => true, 'city' => 'Zwolle']);
        $theme = FrontendTheme::query()->create(['slug' => 'landwind', 'name' => 'Landwind', 'is_active' => true]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'business_type' => ['Taxi'],
                            'business_summary' => ['Luchthavenvervoer in Zwolle'],
                            'primary_cta' => ['Neem contact op'],
                            'services' => [['title' => 'Schiphol'], ['name' => 'Zakelijk']],
                            'pages' => [[
                                'slug' => 'home',
                                'title' => ['Array Brief Taxi'],
                                'page_type' => 'home',
                                'components' => [
                                    ['id' => 'landwind.faq'],
                                    ['taxi.boekingsmodule_v2'],
                                ],
                                'hero' => [
                                    'title' => 'Array Brief Taxi',
                                    'subtitle' => 'Luchthavenvervoer in Zwolle',
                                ],
                            ]],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ]],
            ], 200),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.website-ai.generate'), $this->validPayload($company, $theme, [
                'generate_images' => '0',
            ]))
            ->assertRedirect();

        $home = WebsitePage::query()->where('company_id', $company->id)->where('slug', 'home')->first();
        $this->assertNotNull($home);
        $this->assertFalse((bool) $home->is_active);
        $this->assertSame('Array Brief Taxi', $home->title);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(Company $company, FrontendTheme $theme, array $overrides = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'source_url' => '',
            'context' => 'Professioneel taxibedrijf voor luchthaven- en stadsritten.',
            'max_pages' => 3,
            'frontend_theme_id' => $theme->id,
            'color_preset' => 'navy-gold',
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#0f172a',
            'generate_images' => '0',
            'replace_existing' => '0',
            'source_type' => 'new',
            'style' => 'professional',
            'tone' => 'zakelijk',
            'goals' => ['leads'],
        ], $overrides);
    }

    private function builderUrl(WebsitePage $page, Company $company): string
    {
        $params = [
            'website_page' => $page->id,
            'tenant_company' => $company->id,
            'saved' => 1,
        ];
        if (trim((string) $page->module_name) !== '') {
            $params['module'] = $page->module_name;
        }

        return route('admin.website-pages.builder-v2.edit', $params);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }
}

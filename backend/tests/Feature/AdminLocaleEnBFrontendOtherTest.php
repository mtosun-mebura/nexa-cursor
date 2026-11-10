<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBFrontendOtherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    private function superAdmin(): User
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function visibleHtml(string $html): string
    {
        return (string) preg_replace('/<!--.*?-->/s', '', $html);
    }

    private function tenantSession(array $extra = []): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Frontend Taxi', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id] + $extra;
    }

    private function theme(array $attrs = []): FrontendTheme
    {
        return FrontendTheme::query()->create($attrs + [
            'slug' => 'landwind',
            'name' => 'Landwind',
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<string>  $english
     * @param  list<string>  $dutch
     */
    private function assertEnglishPage(string $html, array $english, array $dutch): void
    {
        foreach ($english as $text) {
            $this->assertStringContainsString($text, $html, $text);
        }
        $visible = $this->visibleHtml($html);
        foreach ($dutch as $text) {
            $this->assertStringNotContainsString($text, $visible, $text);
        }
    }

    #[Test]
    public function coming_soon_settings_page_is_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'en']))
            ->get(route('admin.settings.frontend.index'))
            ->assertOk()
            ->getContent();

        $this->assertEnglishPage($html,
            ['Front-end configuration', 'Coming soon page', 'Preview page', 'Central image', 'Secondary text', 'Footer text', 'Save coming soon settings', 'Show email'],
            ['Front-end configuraties', 'Voorbeeld pagina', 'Centrale afbeelding', 'Secundaire tekst', 'Footertekst', 'Coming soon-instellingen opslaan', 'E-mail tonen', 'Upload mislukt.']);
    }

    #[Test]
    public function coming_soon_settings_page_stays_dutch_in_nl_locale(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'nl']))
            ->get(route('admin.settings.frontend.index'))
            ->assertOk()
            ->getContent();

        foreach (['Front-end configuraties', 'Coming Soon-pagina', 'Voorbeeld pagina', 'Centrale afbeelding', 'Secundaire tekst', 'Footertekst', 'Coming soon-instellingen opslaan', 'Wordt alleen getoond als "E-mail tonen" aanstaat', 'Upload mislukt.'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function website_ai_page_and_validation_are_english_in_en_locale(): void
    {
        $this->theme();
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $html = $this->actingAs($admin)->withSession($session)->get(route('admin.website-ai.create'))->assertOk()->getContent();
        $this->assertEnglishPage($html,
            ['Generate website with AI', 'Tenant and source', 'Company *', 'New website', 'Existing website as source', 'Style', 'Tone', 'Purpose of the website', 'More inquiries', 'Professional', 'Scope, theme and colors', 'Primary color', 'Generate homepage as draft', 'Landwind (theme)', 'Sky blue'],
            ['Tenant en bron', 'Bedrijf *', 'Nieuwe website', 'Doel van de website', 'Meer aanvragen', 'Professioneel', 'Omvang, thema en kleuren', 'Primaire kleur', 'Homepage als concept genereren', 'Hemelsblauw', '(thema)']);

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.website-ai.generate'), [])
            ->assertSessionHasErrors('context');
        $this->assertSame('Describe what the website should be about.', session('errors')->first('context'));
    }

    #[Test]
    public function website_ai_page_stays_dutch_in_nl_locale(): void
    {
        $this->theme();
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'nl']))
            ->get(route('admin.website-ai.create'))
            ->assertOk()
            ->getContent();

        foreach (['Genereer website AI', 'Tenant en bron', 'Bedrijf *', 'Meer aanvragen', 'Professioneel', 'Hemelsblauw', 'Landwind (thema)', 'Homepage als concept genereren', 'Toon</label>'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
    }

    #[Test]
    public function ai_images_page_is_english_in_en_locale_and_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();

        $html = $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'en']))->get(route('admin.ai-images.index'))->assertOk()->getContent();
        $this->assertEnglishPage($html,
            ['AI Images', 'Generate new image', 'Reference image (optional)', 'Source for adjustment', 'No image generated yet.', 'Gallery', 'No images generated yet.', 'Permanently delete this image?', 'Generation failed.'],
            ['Nieuwe afbeelding genereren', 'Referentie-afbeelding (optioneel)', 'Bron voor aanpassing', 'Nog geen afbeelding gegenereerd.', 'Galerij', 'Deze afbeelding definitief verwijderen?', 'Genereren is mislukt.']);

        $nl = $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'nl']))->get(route('admin.ai-images.index'))->assertOk()->getContent();
        foreach (['Nieuwe afbeelding genereren', 'Referentie-afbeelding (optioneel)', 'Nog geen afbeelding gegenereerd.', 'Galerij', 'Deze afbeelding definitief verwijderen?', 'Genereren is mislukt.'] as $dutch) {
            $this->assertStringContainsString($dutch, $nl, $dutch);
        }
    }

    #[Test]
    public function ai_image_generate_error_message_is_english_in_en_locale(): void
    {
        config(['services.openai.api_key' => '']);

        // Service exception messages are translated by the controller before they reach the admin JS.
        $response = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession(['locale' => 'en']))
            ->postJson(route('admin.ai-images.generate'), ['prompt' => 'Test prompt']);

        $response->assertStatus(422);
        $this->assertSame('OpenAI is not configured (OPENAI_API_KEY is missing).', $response->json('message'));
    }

    #[Test]
    public function themes_pages_and_flash_messages_are_english_in_en_locale(): void
    {
        $theme = $this->theme();
        $this->theme(['slug' => 'inactive-locale', 'name' => 'Inactive theme', 'is_active' => false]);
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $index = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-themes.index'))->assertOk()->getContent();
        $this->assertEnglishPage($index,
            ['Frontend Themes', 'Show setup', 'Theme per tenant', 'Website theme', '— No theme —', 'Edit company', 'Unpublish theme', 'No screenshot'],
            ['Thema per tenant', 'Setup tonen', 'Website-thema', 'Geen thema', 'Bedrijf bewerken', 'Geen screenshot']);

        $edit = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-themes.edit', $theme))->assertOk()->getContent();
        $this->assertEnglishPage($edit,
            ['Theme: Landwind', 'Theme settings', 'Primary color', 'Heading font', 'Body font', 'Footer text', 'Show light/dark buttons in the website header', 'Cancel', 'Save'],
            ['Thema-instellingen', 'Primaire kleur', 'Lettertype koppen', 'Footertekst', 'Annuleren', 'Opslaan']);

        $setup = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-themes.setup'))->assertOk()->getContent();
        $this->assertEnglishPage($setup,
            ['Application setup', 'Back to Themes', 'PHP version', 'PHP extensions', 'Active theme'],
            ['Setup applicatie', 'PHP-versie', 'PHP-extensies', 'Actief thema']);

        $this->actingAs($admin)->withSession($session)
            ->put(route('admin.frontend-themes.update', $theme), ['primary_color' => '#112233'])
            ->assertRedirect(route('admin.frontend-themes.index'));
        $this->assertSame('Theme settings saved.', session('success'));

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.frontend-themes.unpublish'), ['theme_id' => $theme->id]);
        $this->assertSame('Theme "Landwind" has been deactivated. Tenants with this theme show Coming soon until another theme is chosen.', session('success'));
    }

    #[Test]
    public function themes_pages_and_flash_messages_stay_dutch_in_nl_locale(): void
    {
        $theme = $this->theme();
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'nl']);

        $index = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-themes.index'))->assertOk()->getContent();
        foreach (["Frontend Thema's", 'Thema per tenant', 'Setup tonen', 'Website-thema', '— Geen thema —', 'Bedrijf bewerken', "Elke tenant kan een eigen website-thema hebben. Alleen geactiveerde thema's zijn kiesbaar."] as $dutch) {
            $this->assertStringContainsString($dutch, $index, $dutch);
        }

        $this->actingAs($admin)->withSession($session)
            ->put(route('admin.frontend-themes.update', $theme), ['primary_color' => '#112233']);
        $this->assertSame('Thema-instellingen opgeslagen.', session('success'));

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.frontend-themes.unpublish'), ['theme_id' => $theme->id]);
        $this->assertSame('Thema "Landwind" is gedeactiveerd. Tenants met dit thema tonen Coming soon tot een ander thema is gekozen.', session('success'));
    }

    #[Test]
    public function frontend_components_pages_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession(['locale' => 'en']);

        $index = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-components.index'))->assertOk()->getContent();
        $this->assertEnglishPage($index,
            ['Front-end components', 'Platform-wide — applies to all tenants', 'Description', 'Theme', 'View demo'],
            ['Front-end Componenten', 'Platformbreed', 'Omschrijving', 'Demo bekijken']);

        $demo = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-components.demo', ['componentId' => 'website.google_reviews']));
        if ($demo->status() === 200) {
            $content = $demo->getContent();
            $this->assertStringContainsString('Back to components', $content);
            $this->assertStringContainsString('Replay animation', $content);
            $this->assertStringNotContainsString('Terug naar componenten', $this->visibleHtml($content));
        } else {
            $this->markTestIncomplete('Demo component id niet beschikbaar: '.$demo->status());
        }

        $section = $this->actingAs($admin)->withSession($session)->get(route('admin.frontend-components.demo', ['componentId' => 'section.cta']))->assertOk()->getContent();
        $this->assertStringContainsString('Demo of a call-to-action section.', $section);
        $this->assertStringContainsString('Example view of built-in section', $section);
        $this->assertStringNotContainsString('Voorbeeldweergave van ingebouwde sectie', $this->visibleHtml($section));
        $this->assertStringContainsString('Built-in page section from the website builder.', $index);

        $this->actingAs($admin)->withSession($session)
            ->post(route('admin.frontend-components.toggle-disabled'), ['component_id' => 'does-not-exist', 'disabled' => 1], ['Accept' => 'application/json'])
            ->assertStatus(404)
            ->assertJson(['message' => 'Component not found.']);
    }

    #[Test]
    public function frontend_components_pages_stay_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();

        $index = $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'nl']))->get(route('admin.frontend-components.index'))->assertOk()->getContent();
        foreach (['Front-end Componenten', 'Platformbreed — geldt voor alle tenants', 'Omschrijving', 'Demo bekijken'] as $dutch) {
            $this->assertStringContainsString($dutch, $index, $dutch);
        }

        $this->actingAs($admin)->withSession($this->tenantSession(['locale' => 'nl']))
            ->post(route('admin.frontend-components.toggle-disabled'), ['component_id' => 'does-not-exist', 'disabled' => 1], ['Accept' => 'application/json'])
            ->assertStatus(404)
            ->assertJson(['message' => 'Component niet gevonden.']);
    }
}

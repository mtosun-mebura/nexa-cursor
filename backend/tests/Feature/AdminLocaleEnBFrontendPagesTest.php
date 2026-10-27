<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnBFrontendPagesTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * @return array{0: WebsitePage, 1: array<string, mixed>}
     */
    private function pageAndSession(string $locale): array
    {
        $tenant = Company::query()->create(['name' => 'Locale Pages Taxi', 'is_active' => true]);
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Metronic', 'is_active' => true]
        );
        $page = WebsitePage::create([
            'slug' => 'over-ons-locale',
            'title' => 'Over ons locale',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'company_id' => $tenant->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$page, ['selected_tenant' => $tenant->id, 'locale' => $locale]];
    }

    #[Test]
    public function website_pages_index_is_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        [, $session] = $this->pageAndSession('en');

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.index'))
            ->assertOk()
            ->getContent();

        foreach (['Website pages', 'Website preview', 'SEO texts', 'New page', 'Menu item', 'Actions', 'Delete page', 'Classic editor', 'Overwrite the SEO title, meta description and hero text of this page?'] as $english) {
            $this->assertStringContainsString($english, $html, $english);
        }
        foreach (["Website Pagina's", 'Website voorbeeld', 'SEO teksten', 'Nieuwe pagina', 'Menuitem', 'Klassieke editor', 'Pagina verwijderen', 'overschrijven?'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($html), $dutch);
        }
    }

    #[Test]
    public function website_pages_index_is_unchanged_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();
        [, $session] = $this->pageAndSession('nl');

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.index'))
            ->assertOk()
            ->getContent();

        foreach (["Website Pagina's", 'Website voorbeeld', 'SEO teksten', 'Nieuwe pagina', 'Menuitem', 'Klassieke editor', 'Pagina verwijderen', 'Weet je zeker dat je de pagina “Over ons locale” wilt verwijderen? Dit kan niet ongedaan worden gemaakt.'] as $dutch) {
            $this->assertStringContainsString($dutch, $html, $dutch);
        }
        $this->assertStringNotContainsString('Website preview', $this->visibleHtml($html));
    }

    #[Test]
    public function website_page_create_and_edit_are_english_in_en_locale(): void
    {
        $admin = $this->superAdmin();
        [$page, $session] = $this->pageAndSession('en');

        $create = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.create'))
            ->assertOk()
            ->getContent();

        foreach (['Create page', 'Page information', 'Which module does this page belong to? *', 'Page type *', 'Menu name', 'Back to overview', 'Cancel'] as $english) {
            $this->assertStringContainsString($english, $create, $english);
        }
        foreach (['Pagina aanmaken', 'Pagina-informatie', 'Bij welke module hoort deze pagina?', 'Paginatype', 'Naam in menu', 'Terug naar overzicht'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($create), $dutch);
        }

        $edit = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.edit', $page))
            ->assertOk()
            ->getContent();

        foreach (['Edit page', 'Page information', 'Visual editor', 'Page title (SEO) *', 'Save', 'Meta description', 'Generate SEO &amp; AI'] as $english) {
            $this->assertStringContainsString($english, $edit, $english);
        }
        foreach (['Pagina bewerken', 'Visuele editor', 'Paginatitel (SEO)', 'Meta-omschrijving', 'SEO &amp; AI genereren'] as $dutch) {
            $this->assertStringNotContainsString($dutch, $this->visibleHtml($edit), $dutch);
        }
    }

    #[Test]
    public function website_page_create_and_edit_keep_dutch_in_nl_locale(): void
    {
        $admin = $this->superAdmin();
        [$page, $session] = $this->pageAndSession('nl');

        $create = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.create'))
            ->assertOk()
            ->getContent();
        foreach (['Pagina aanmaken', 'Pagina-informatie', 'Bij welke module hoort deze pagina? *', 'Paginatype *', 'Naam in menu', 'Terug naar overzicht'] as $dutch) {
            $this->assertStringContainsString($dutch, $create, $dutch);
        }

        $edit = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.website-pages.edit', $page))
            ->assertOk()
            ->getContent();
        foreach (['Pagina bewerken', 'Visuele editor', 'Paginatitel (SEO) *', 'Meta-omschrijving', 'SEO &amp; AI genereren', 'Pagina-secties (Metronic thema)'] as $dutch) {
            $this->assertStringContainsString($dutch, $edit, $dutch);
        }
    }

    #[Test]
    public function website_page_flash_messages_follow_the_locale(): void
    {
        $admin = $this->superAdmin();
        [$page, $session] = $this->pageAndSession('en');

        $this->actingAs($admin)
            ->withSession($session)
            ->post(route('admin.website-pages.toggle-active', $page), ['is_active' => '0'])
            ->assertSessionHas('success', 'Page is now inactive (not visible on the website).');

        [$page2, $sessionNl] = $this->pageAndSession('nl');
        $this->actingAs($admin)
            ->withSession($sessionNl)
            ->post(route('admin.website-pages.toggle-active', $page2), ['is_active' => '1'])
            ->assertSessionHas('success', 'Pagina is nu actief en zichtbaar op de website.');
    }
}

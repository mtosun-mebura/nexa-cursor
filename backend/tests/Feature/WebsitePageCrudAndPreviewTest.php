<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebsitePageCrudAndPreviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function website_pages_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.website-pages.index'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }

    #[Test]
    public function website_pages_index_returns_200_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $response = $this->actingAs($user)->get(route('admin.website-pages.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.website-pages.index');
    }

    #[Test]
    public function website_pages_index_without_tenant_shows_central_pages_only(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id')) {
            $this->markTestSkipped('website_pages.company_id column required');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $tenant = Company::query()->create(['name' => 'No Tenant List', 'slug' => 'no-tenant-list-'.uniqid()]);

        WebsitePage::query()->create([
            'slug' => 'hidden-without-tenant-'.uniqid(),
            'title' => 'Hidden Without Tenant',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $tenant->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        WebsitePage::query()->create([
            'slug' => 'central-saas-home-'.uniqid(),
            'title' => 'Central Saas Home Unique',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.website-pages.index'));

        $response->assertStatus(200);
        $response->assertSee('hoofdwebsite van Nexa SaaS', false);
        $response->assertSee('Central Saas Home Unique');
        $response->assertDontSee('Hidden Without Tenant');
        $response->assertDontSee('voordat u website-pagina');
    }

    #[Test]
    public function website_pages_index_is_scoped_to_selected_tenant(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id')) {
            $this->markTestSkipped('website_pages.company_id column required');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $tenantA = Company::query()->create(['name' => 'Tenant A Pages', 'slug' => 'tenant-a-pages-'.uniqid()]);
        $tenantB = Company::query()->create(['name' => 'Tenant B Pages', 'slug' => 'tenant-b-pages-'.uniqid()]);

        $tenantASlug = 'tenant-a-only-'.uniqid();
        $tenantBSlug = 'tenant-b-only-'.uniqid();

        WebsitePage::query()->create([
            'slug' => $tenantASlug,
            'title' => 'Tenant A Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $tenantA->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        WebsitePage::query()->create([
            'slug' => $tenantBSlug,
            'title' => 'Tenant B Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => $tenantB->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $tenantA->id])
            ->get(route('admin.website-pages.index'));

        $response->assertStatus(200);
        $response->assertSee($tenantASlug);
        $response->assertDontSee($tenantBSlug);
    }

    #[Test]
    public function tenant_switch_updates_tenant_company_in_website_pages_redirect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $tenantA = Company::query()->create(['name' => 'Switch A', 'slug' => 'switch-a-'.uniqid()]);
        $tenantB = Company::query()->create(['name' => 'Switch B', 'slug' => 'switch-b-'.uniqid()]);

        $response = $this->actingAs($user)->postJson(route('admin.tenant.switch'), [
            'tenant_id' => $tenantB->id,
            'redirect' => '/admin/website-pages?tenant_company='.$tenantA->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'redirect' => '/admin/website-pages?tenant_company='.$tenantB->id,
        ]);
    }

    #[Test]
    public function website_pages_create_form_loads_for_super_admin(): void
    {
        $tenant = Company::query()->create(['name' => 'Create Form Tenant', 'slug' => 'create-form-'.uniqid()]);
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $response = $this->actingAs($user)
            ->withSession(['selected_tenant' => $tenant->id])
            ->get(route('admin.website-pages.create'));
        $response->assertStatus(200);
        $response->assertSee('Pagina-informatie');
        $response->assertSee('Menuitem');
        $response->assertSee('Actief');
    }

    #[Test]
    public function website_pages_create_preselects_tenant_linked_module(): void
    {
        $tenant = Company::query()->create(['name' => 'Taxi Wizard Tenant', 'slug' => 'taxi-wizard-'.uniqid()]);
        $module = \App\Models\Module::query()->firstOrCreate(
            ['name' => 'taxi'],
            [
                'display_name' => 'Nexa Taxi',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
            ]
        );
        $module->forceFill(['installed' => true, 'active' => true])->save();
        $tenant->modules()->syncWithoutDetaching([$module->id]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $html = $this->actingAs($user)
            ->get(route('admin.website-pages.create', [
                'from_wizard' => 1,
                'wizard_company' => $tenant->id,
                'wizard_step' => 6,
            ]))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="taxi"[^>]*\bselected\b/i',
            $html
        );
        $this->assertStringContainsString('id="module_name_hidden" value="taxi"', $html);
    }

    #[Test]
    public function website_page_store_allows_same_module_slug_for_another_tenant(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $module = \App\Models\Module::query()->firstOrCreate(
            ['name' => 'taxi'],
            [
                'display_name' => 'Nexa Taxi',
                'version' => '1.0.0',
                'installed' => true,
                'active' => true,
            ]
        );
        $module->forceFill(['installed' => true, 'active' => true])->save();

        $companyA = Company::query()->create(['name' => 'Slug Tenant A', 'slug' => 'slug-a-'.uniqid(), 'is_active' => true]);
        $companyB = Company::query()->create(['name' => 'Slug Tenant B', 'slug' => 'slug-b-'.uniqid(), 'is_active' => true]);
        $companyA->modules()->syncWithoutDetaching([$module->id]);
        $companyB->modules()->syncWithoutDetaching([$module->id]);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home A',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'company_id' => $companyA->id,
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.website-pages.store'), [
                'slug' => 'home',
                'title' => 'Home B',
                'page_type' => 'home',
                'module_name' => 'taxi',
                'company_id' => (string) $companyB->id,
                'from_wizard' => '1',
                'wizard_company' => (string) $companyB->id,
                'wizard_step' => '6',
                'frontend_theme_id' => (string) $theme->id,
                'is_active' => '1',
                'sort_order' => '0',
            ])
            ->assertSessionDoesntHaveErrors('slug')
            ->assertRedirect();

        $this->assertDatabaseHas('website_pages', [
            'slug' => 'home',
            'module_name' => 'taxi',
            'company_id' => $companyB->id,
            'title' => 'Home B',
        ]);
    }

    #[Test]
    public function website_page_preview_returns_200_for_existing_page(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $page = WebsitePage::create([
            'slug' => 'preview-test-page',
            'title' => 'Preview Test',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $response = $this->actingAs($user)->get(route('admin.website-pages.preview', $page));
        $response->assertStatus(200);
    }

    #[Test]
    public function website_page_store_accepts_show_in_menu(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $company = Company::query()->first()
            ?? Company::query()->create(['name' => 'Menu Test Co', 'is_active' => true]);

        $payload = [
            'slug' => 'menu-test-page',
            'title' => 'Menu Test',
            'page_type' => 'custom',
            'module_name' => '',
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            'sort_order' => '0',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id')) {
            $payload['company_id'] = (string) $company->id;
        }

        $response = $this->actingAs($user)->post(route('admin.website-pages.store'), $payload);
        $response->assertRedirect();
        $this->assertDatabaseHas('website_pages', ['slug' => 'menu-test-page']);
        $page = WebsitePage::where('slug', 'menu-test-page')->first();
        if ($page && \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'show_in_menu')) {
            $this->assertTrue((bool) $page->show_in_menu);
        }
        if ($page && \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'sort_order')) {
            $this->assertGreaterThanOrEqual(1, (int) $page->sort_order);
        }
    }

    #[Test]
    public function store_assigns_incrementing_sort_order_per_company(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $company = \App\Models\Company::query()->first()
            ?? \App\Models\Company::query()->create(['name' => 'Sort Co', 'is_active' => true]);

        WebsitePage::create([
            'slug' => 'existing-sort-page',
            'title' => 'Existing',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'company_id' => \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id') ? $company->id : null,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $basePayload = [
            'title' => 'New page',
            'page_type' => 'custom',
            'module_name' => '',
            'meta_description' => '',
            'content' => '',
            'is_active' => '1',
            'show_in_menu' => '1',
            'sort_order' => '0',
        ];
        if (\Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id')) {
            $basePayload['company_id'] = (string) $company->id;
        }

        $response = $this->actingAs($user)->post(route('admin.website-pages.store'), array_merge($basePayload, [
            'slug' => 'auto-sort-page-1',
            'title' => 'Auto sort 1',
        ]));
        $response->assertRedirect();

        $page = WebsitePage::where('slug', 'auto-sort-page-1')->first();
        $this->assertNotNull($page);
        $this->assertSame(6, (int) $page->sort_order);
    }

    #[Test]
    public function central_saas_page_meta_can_be_saved_without_company(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'company_id')) {
            $this->markTestSkipped('website_pages.company_id column required');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $page = WebsitePage::query()->create([
            'slug' => 'central-meta-'.uniqid(),
            'title' => 'Central Meta Page',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->withSession([])
            ->patchJson(route('admin.website-pages.builder-v2.update-meta', $page), [
                'title' => 'Central Meta Page updated',
                'menu_title' => 'Menu naam',
                'slug' => $page->slug,
                'page_type' => 'custom',
                'module_name' => '',
                'frontend_theme_id' => $theme->id,
                'is_active' => true,
                'show_in_menu' => true,
                'sort_order' => 1,
                'meta_description' => 'Centrale Nexa SaaS-pagina',
                'company_id' => null,
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('pageMeta.menuTitle', 'Menu naam');

        $this->assertNull($page->fresh()->company_id);
        $this->assertSame('Central Meta Page updated', $page->fresh()->title);
        $this->assertSame('Menu naam', (string) $page->fresh()->menu_title);
    }

    #[Test]
    public function website_pages_index_shows_sort_order_arrows(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        WebsitePage::query()->create([
            'slug' => 'reorder-arrows-a-'.uniqid(),
            'title' => 'Reorder Arrows A',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 51001,
        ]);
        WebsitePage::query()->create([
            'slug' => 'reorder-arrows-b-'.uniqid(),
            'title' => 'Reorder Arrows B',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 51002,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(route('admin.website-pages.index'));
        $response->assertStatus(200);
        $response->assertSee('Omhoog', false);
        $response->assertSee('Omlaag', false);
        $response->assertSee(route('admin.website-pages.reorder', ['website_page' => WebsitePage::query()->where('title', 'Reorder Arrows A')->value('id')]), false);
    }

    #[Test]
    public function website_page_can_be_reordered_from_index_without_editing(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $pageA = WebsitePage::query()->create([
            'slug' => 'reorder-move-a-'.uniqid(),
            'title' => 'Reorder Move A',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 52001,
        ]);
        $pageB = WebsitePage::query()->create([
            'slug' => 'reorder-move-b-'.uniqid(),
            'title' => 'Reorder Move B',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 52002,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->post(route('admin.website-pages.reorder', $pageA), [
            'direction' => 'down',
        ]);
        $response->assertRedirect(route('admin.website-pages.index'));
        $response->assertSessionHas('success');

        $this->assertGreaterThan((int) $pageB->fresh()->sort_order, (int) $pageA->fresh()->sort_order);
    }

    #[Test]
    public function website_page_reorder_up_on_first_row_is_a_noop(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('website_pages', 'sort_order')) {
            $this->markTestSkipped('sort_order column not migrated');
        }

        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );
        $first = WebsitePage::query()->create([
            'slug' => 'reorder-first-'.uniqid(),
            'title' => 'Reorder First Unique',
            'page_type' => 'custom',
            'frontend_theme_id' => $theme->id,
            'module_name' => null,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $original = (int) $first->sort_order;
        $response = $this->actingAs($user)->post(route('admin.website-pages.reorder', $first), [
            'direction' => 'up',
        ]);
        $response->assertRedirect(route('admin.website-pages.index'));
        $this->assertSame($original, (int) $first->fresh()->sort_order);
    }
}

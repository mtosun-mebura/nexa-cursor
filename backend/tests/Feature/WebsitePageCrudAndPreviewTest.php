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
    public function website_pages_create_form_loads_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $response = $this->actingAs($user)->get(route('admin.website-pages.create'));
        $response->assertStatus(200);
        $response->assertSee('Pagina-informatie');
        $response->assertSee('Menuitem');
        $response->assertSee('Actief');
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
}

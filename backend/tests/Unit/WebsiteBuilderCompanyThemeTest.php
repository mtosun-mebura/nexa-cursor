<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteBuilderCompanyThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_themes_can_be_active_simultaneously(): void
    {
        $a = FrontendTheme::query()->create([
            'slug' => 'theme-a',
            'name' => 'Theme A',
            'is_active' => true,
        ]);
        $b = FrontendTheme::query()->create([
            'slug' => 'theme-b',
            'name' => 'Theme B',
            'is_active' => true,
        ]);

        $this->assertTrue($a->fresh()->is_active);
        $this->assertTrue($b->fresh()->is_active);
        $this->assertCount(2, FrontendTheme::getAllActive());
    }

    public function test_get_theme_for_page_uses_company_assigned_theme(): void
    {
        $modern = FrontendTheme::query()->create([
            'slug' => 'modern-co',
            'name' => 'Modern',
            'is_active' => true,
        ]);
        FrontendTheme::query()->create([
            'slug' => 'atom-co',
            'name' => 'Atom',
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Tenant BV',
            'frontend_theme_id' => $modern->id,
        ]);

        $page = WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'company_id' => $company->id,
            'frontend_theme_id' => $modern->id,
            'is_active' => true,
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        $theme = app(WebsiteBuilderService::class)->getThemeForPage($page);

        $this->assertNotNull($theme);
        $this->assertSame($modern->id, $theme->id);
    }

    public function test_get_home_page_skips_inactive_tenant_home(): void
    {
        $modern = FrontendTheme::query()->create([
            'slug' => 'modern-home-test',
            'name' => 'Modern',
            'is_active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Tenant Home Test',
            'frontend_theme_id' => $modern->id,
        ]);
        $module = Module::query()->create([
            'name' => 'taxi-home-test',
            'display_name' => 'Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'frontend_theme_id' => $modern->id,
        ]);
        $company->modules()->attach($module->id);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi-home-test',
            'company_id' => $company->id,
            'home_sections' => ['hero' => ['title' => 'Test']],
            'is_active' => false,
        ]);

        app()->instance('resolved_tenant_id', $company->id);
        $service = app(WebsiteBuilderService::class);

        $this->assertNull($service->getHomePage());
        $this->assertTrue($service->tenantHasInactiveConfiguredHomePage($company->id));
    }

    public function test_get_home_page_returns_active_tenant_home_first(): void
    {
        $modern = FrontendTheme::query()->create([
            'slug' => 'modern-home-active',
            'name' => 'Modern',
            'is_active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Tenant Active Home',
            'frontend_theme_id' => $modern->id,
        ]);
        $module = Module::query()->create([
            'name' => 'taxi-active-home',
            'display_name' => 'Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'frontend_theme_id' => $modern->id,
        ]);
        $company->modules()->attach($module->id);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Shared',
            'page_type' => 'home',
            'module_name' => 'taxi-active-home',
            'frontend_theme_id' => $modern->id,
            'company_id' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
        $tenantHome = WebsitePage::query()->create([
            'slug' => 'home-tenant',
            'title' => 'Tenant',
            'page_type' => 'home',
            'module_name' => 'taxi-active-home',
            'frontend_theme_id' => $modern->id,
            'company_id' => $company->id,
            'home_sections' => ['hero' => ['title' => 'Tenant']],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        app()->instance('resolved_tenant_id', $company->id);
        $home = app(WebsiteBuilderService::class)->getHomePage();

        $this->assertNotNull($home);
        $this->assertSame($tenantHome->id, $home->id);
    }

    public function test_publish_tenant_website_pages_activates_inactive_pages(): void
    {
        $modern = FrontendTheme::query()->create([
            'slug' => 'modern-publish',
            'name' => 'Modern',
            'is_active' => true,
        ]);
        $company = Company::query()->create([
            'name' => 'Publish Tenant',
            'frontend_theme_id' => $modern->id,
        ]);
        $module = Module::query()->create([
            'name' => 'taxi-publish',
            'display_name' => 'Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'frontend_theme_id' => $modern->id,
        ]);
        $company->modules()->attach($module->id);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi-publish',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        WebsitePage::query()->create([
            'slug' => 'diensten',
            'title' => 'Diensten',
            'page_type' => 'custom',
            'module_name' => 'taxi-publish',
            'company_id' => $company->id,
            'is_active' => false,
            'show_in_menu' => true,
        ]);

        app()->instance('resolved_tenant_id', $company->id);
        $service = app(WebsiteBuilderService::class);
        $this->assertCount(1, $service->getActiveMenuPages());
        $service->publishTenantWebsitePages($company->id);
        $this->assertCount(2, $service->getActiveMenuPages());
    }

    public function test_get_theme_for_company_returns_null_when_theme_unpublished(): void
    {
        $theme = FrontendTheme::query()->create([
            'slug' => 'inactive-co',
            'name' => 'Inactive',
            'is_active' => false,
        ]);
        $company = Company::query()->create([
            'name' => 'No Theme Tenant',
            'frontend_theme_id' => $theme->id,
        ]);

        $this->assertNull(app(WebsiteBuilderService::class)->getThemeForCompany($company->id));
    }
}

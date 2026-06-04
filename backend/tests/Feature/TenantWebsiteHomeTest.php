<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\Module;
use App\Models\WebsitePage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantWebsiteHomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tenant_home_with_taxi_booking_component_returns_200(): void
    {
        $theme = FrontendTheme::query()->create([
            'slug' => 'modern-tenant-home',
            'name' => 'Modern',
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'taxitest',
            'slug' => 'taxitest',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
        ]);

        $module = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'frontend_theme_id' => $theme->id,
        ]);
        $company->modules()->attach($module->id);
        $theme->update(['active_module_id' => $module->id]);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'frontend_theme_id' => $theme->id,
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'section_order' => ['hero', 'component:taxi.boekingsmodule'],
                'visibility' => [
                    'hero' => true,
                    'component:taxi.boekingsmodule' => true,
                ],
                'hero' => ['title' => 'Welkom bij Taxi Test'],
                'component:taxi.boekingsmodule' => ['title' => 'Boek je rit'],
                'footer' => [],
                'copyright' => '',
            ],
        ]);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
        ])->get('/?nexa_admin_preview=1');

        $response->assertOk();
        $response->assertDontSee('Server Error', false);
    }
}

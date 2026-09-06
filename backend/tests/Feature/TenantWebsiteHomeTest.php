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

    #[Test]
    public function booking_module_applies_configured_step_heading_font_size(): void
    {
        $html = view('frontend.website.components.nexataxi-boekingsmodule', [
            'homeSections' => [
                'component:taxi.boekingsmodule' => [
                    'title' => 'Boek je rit',
                    'style' => [
                        'step_heading_font_size_px' => '42',
                        'title_font_size_px' => '48',
                        'field_heading_font_size_px' => '20',
                    ],
                ],
            ],
            'sectionKey' => 'component:taxi.boekingsmodule',
        ])->render();

        $this->assertStringContainsString('--booking-step-heading-size-max: 42px', $html);
        $this->assertStringContainsString('--booking-title-size-max: 48px', $html);
        $this->assertStringContainsString('--booking-field-heading-size: 20px', $html);
        $this->assertStringContainsString('font-size: var(--booking-step-heading-size-max', $html);
        $this->assertStringContainsString('font-size: var(--booking-field-heading-size', $html);
        $this->assertStringNotContainsString('font-size: clamp(0.95rem, 1.8vw + 0.45rem, 1.35rem)', $html);
    }

    #[Test]
    public function booking_module_uses_title_color_and_default_type_sizes(): void
    {
        $html = view('frontend.website.components.nexataxi-boekingsmodule', [
            'homeSections' => [
                'component:taxi.boekingsmodule' => [
                    'title' => 'Boek je rit',
                    'style' => [
                        'title_color' => '#2563eb',
                    ],
                ],
            ],
            'sectionKey' => 'component:taxi.boekingsmodule',
        ])->render();

        $this->assertStringContainsString('--booking-title-color: #2563eb', $html);
        $this->assertStringContainsString('--booking-title-size-max: 24px', $html);
        $this->assertStringContainsString('--booking-step-heading-size-max: 20px', $html);
        $this->assertStringContainsString('color: var(--booking-title-color, var(--booking-cta, #f97316))', $html);
    }
}

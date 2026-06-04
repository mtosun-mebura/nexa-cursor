<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\User;
use App\Models\WebsitePage;
use App\Services\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteBuilderPortalCopyrightTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_copyright_uses_tenant_home_when_host_tenant_is_not_resolved(): void
    {
        $theme = FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $company = Company::query()->create([
            'name' => 'Taxi Tenant BV',
            'is_active' => true,
        ]);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'module_name' => 'taxi',
            'company_id' => $company->id,
            'is_active' => true,
            'sort_order' => 0,
            'home_sections' => [
                'copyright' => '© {year} Taxi Tenant. Alle rechten voorbehouden.',
                'visibility' => ['footer' => true],
            ],
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);

        $this->actingAs($user);

        $copyright = app(WebsiteBuilderService::class)->resolvePortalCopyright('taxi');

        $this->assertSame('© '.date('Y').' Taxi Tenant. Alle rechten voorbehouden.', $copyright);
    }
}

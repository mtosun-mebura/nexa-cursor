<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Services\WebsiteBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteBuilderSiteBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_branding_falls_back_to_company_logo_when_no_tenant_settings(): void
    {
        $company = Company::query()->create([
            'name' => 'Test Tenant BV',
            'logo_blob' => base64_encode('fake-png'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('fake-dark'),
            'logo_dark_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertNotNull($branding['logo_url']);
        $this->assertStringContainsString('/brand/company/'.$company->id.'/logo', $branding['logo_url']);
        $this->assertNotNull($branding['logo_dark_url']);
        $this->assertStringContainsString('/brand/company/'.$company->id.'/logo/dark', $branding['logo_dark_url']);
    }

    public function test_site_branding_uses_tenant_general_settings_logo_over_company(): void
    {
        $company = Company::query()->create([
            'name' => 'Other Tenant BV',
            'logo_blob' => base64_encode('company-logo'),
            'logo_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        GeneralSetting::set('logo', 'settings/test-logo.png', $company->id);
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/test-logo.png', 'tenant-logo');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertStringContainsString('/file/settings--test-logo.png', $branding['logo_url']);
    }

    public function test_site_branding_uses_data_uri_for_company_logo_in_admin_context(): void
    {
        GeneralSetting::clearRequestCache();
        \Illuminate\Support\Facades\Storage::disk('public')->delete('settings/test-logo.png');

        $company = Company::query()->create([
            'name' => 'Taxi Tenant BV',
            'logo_blob' => base64_encode('fake-png'),
            'logo_mime_type' => 'image/png',
        ]);

        $request = \Illuminate\Http\Request::create('/admin/website-pages', 'GET');
        app()->instance('request', $request);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding(null, false, (int) $company->id);

        $this->assertNotNull($branding['logo_url']);
        $this->assertStringStartsWith('data:image/png;base64,', $branding['logo_url']);
    }

    public function test_site_branding_includes_logo_size_px_from_settings(): void
    {
        $company = Company::query()->create(['name' => 'Size Tenant BV']);
        app()->instance('resolved_tenant_id', $company->id);
        GeneralSetting::set('logo_size', '42', $company->id);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertSame(42, $branding['logo_size_px']);
    }
}

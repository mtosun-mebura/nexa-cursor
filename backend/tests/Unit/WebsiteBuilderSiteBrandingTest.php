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

    public function test_site_branding_prefers_company_logo_over_platform_logo_on_tenant(): void
    {
        $company = Company::query()->create([
            'name' => 'Other Tenant BV',
            'logo_blob' => base64_encode('company-logo'),
            'logo_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        GeneralSetting::set('logo', 'settings/test-logo.png');
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/test-logo.png', 'platform-logo');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertStringContainsString('/brand/company/'.$company->id.'/logo', $branding['logo_url']);
    }

    public function test_site_branding_uses_company_logo_for_dark_mode_when_tenant_has_no_dark_logo(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Royaal',
            'logo_blob' => base64_encode('royaal-logo'),
            'logo_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant_id', $company->id);

        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/nexa-dark.png', 'nexa-dark');
        GeneralSetting::set('logo', 'settings/nexa-light.png');
        GeneralSetting::set('logo_dark', 'settings/nexa-dark.png');
        GeneralSetting::set('logo_mode', 'light_dark');
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/nexa-light.png', 'nexa-light');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertStringContainsString('/brand/company/'.$company->id.'/logo', $branding['logo_url']);
        $this->assertSame($branding['logo_url'], $branding['logo_dark_url']);
        $this->assertStringNotContainsString('settings--nexa-dark', (string) $branding['logo_dark_url']);
        $this->assertSame('Taxi Royaal', $branding['logo_alt']);
    }

    public function test_site_branding_keeps_platform_logo_on_central_nexa_website(): void
    {
        GeneralSetting::clearRequestCache();
        \Illuminate\Support\Facades\Storage::disk('public')->put('settings/nexa-platform-logo.png', 'nexa-logo');
        GeneralSetting::set('logo', 'settings/nexa-platform-logo.png');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertStringContainsString('/file/settings--nexa-platform-logo.png', $branding['logo_url']);
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
        $this->assertSame($branding['logo_url'], $branding['logo_dark_url']);
        $this->assertSame('Taxi Tenant BV', $branding['logo_alt']);
    }

    public function test_tenant_website_uses_own_logo_size_not_platform_logo_size(): void
    {
        $company = Company::query()->create(['name' => 'Size Tenant BV']);
        app()->instance('resolved_tenant_id', $company->id);

        GeneralSetting::set('logo_size', '50');
        GeneralSetting::set('website_logo_size', '36', $company->id);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertSame(36, $branding['logo_size_px']);
    }

    public function test_tenant_website_logo_size_defaults_when_unset(): void
    {
        $company = Company::query()->create(['name' => 'Default Size Tenant BV']);
        app()->instance('resolved_tenant_id', $company->id);
        GeneralSetting::set('logo_size', '50');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertSame(26, $branding['logo_size_px']);
    }

    public function test_central_nexa_website_uses_platform_logo_size(): void
    {
        GeneralSetting::clearRequestCache();
        GeneralSetting::set('logo_size', '42');

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertSame(42, $branding['logo_size_px']);
    }

    public function test_site_branding_includes_logo_size_px_from_settings(): void
    {
        $company = Company::query()->create(['name' => 'Size Tenant BV']);
        app()->instance('resolved_tenant_id', $company->id);
        GeneralSetting::set('website_logo_size', '42', $company->id);

        $branding = app(WebsiteBuilderService::class)->getSiteBranding();

        $this->assertSame(42, $branding['logo_size_px']);
    }
}

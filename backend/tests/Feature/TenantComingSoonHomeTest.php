<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantComingSoonHomeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function tenant_without_homepage_shows_neutral_coming_soon_with_company_name_and_logo(): void
    {
        $module = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Taxi Neutraal BV',
            'slug' => 'taxi-neutraal-'.uniqid(),
            'email' => 'info@taxineutraal.test',
            'is_active' => true,
            'logo_blob' => base64_encode('fake-logo-light'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('fake-logo-dark'),
            'logo_dark_mime_type' => 'image/png',
        ]);
        $company->modules()->attach($module->id);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
        ])->get('/');

        $response->assertOk();
        $response->assertSee('Website in voorbereiding', false);
        $response->assertSee('Taxi Neutraal BV', false);
        $response->assertDontSee('droombaan', false);
        $response->assertDontSee('Nexa Skillmatching', false);
        $response->assertSee(route('frontend.company-brand.logo.dark', $company), false);
        $response->assertDontSee('bg-white/95', false);
        $response->assertDontSee('/file/settings--', false);
    }

    #[Test]
    public function tenant_light_logo_is_used_when_no_dark_variant_exists(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Alleen Light',
            'slug' => 'taxi-light-'.uniqid(),
            'is_active' => true,
            'logo_blob' => base64_encode('fake-logo-light-only'),
            'logo_mime_type' => 'image/png',
        ]);

        app()->instance('resolved_tenant', $company);
        app()->instance('resolved_tenant_id', $company->id);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
        ])->get('/');

        $response->assertOk();
        $response->assertSee(route('frontend.company-brand.logo', $company), false);
        $response->assertDontSee('/file/settings--', false);
    }

    #[Test]
    public function admin_selected_tenant_logo_is_used_without_resolved_tenant_binding(): void
    {
        $company = Company::query()->create([
            'name' => 'Wizard Welkomstcode Test BV',
            'slug' => 'wizard-welcome-'.uniqid(),
            'is_active' => true,
            'logo_blob' => base64_encode('nexa-taxi-logo'),
            'logo_mime_type' => 'image/png',
        ]);

        app()->forgetInstance('resolved_tenant');
        app()->forgetInstance('resolved_tenant_id');
        \App\Models\GeneralSetting::clearRequestCache();
        session(['selected_tenant' => $company->id]);

        $logoUrl = (string) (\App\Http\Controllers\Frontend\ComingSoonController::getSettings()['logo_url'] ?? '');

        $this->assertStringContainsString((string) $company->id, $logoUrl);
        $this->assertStringContainsString('/brand/company/', $logoUrl);
        $this->assertStringNotContainsString('nexa-logo-dark', $logoUrl);
    }

    #[Test]
    public function subdomain_host_without_company_domain_still_shows_tenant_logo_on_coming_soon(): void
    {
        config([
            'tenancy.tenant_parent_domains' => ['nexasuite.online'],
        ]);

        $company = Company::query()->create([
            'name' => 'Wizard Logo Preview '.uniqid(),
            'is_active' => true,
            'logo_blob' => base64_encode('tenant-logo-light'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('tenant-logo-dark'),
            'logo_dark_mime_type' => 'image/png',
        ]);
        $company->refresh();
        $this->assertNotEmpty($company->slug);

        $resolved = \App\Support\Tenancy\TenantParentDomains::companyFromSubdomainHost($company->slug.'.nexasuite.online');
        $this->assertNotNull($resolved);
        $this->assertSame($company->id, $resolved->id);
        $this->assertTrue($resolved->hasAdminLogo(), 'Subdomain-resolutie moet logo_blob laden, niet alleen id/name/slug');

        app()->instance('resolved_tenant', $resolved);
        app()->instance('resolved_tenant_id', $resolved->id);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
        ])->get('/');

        $response->assertOk();
        $response->assertSee('Website in voorbereiding', false);
        $response->assertSee($company->name, false);
        $response->assertSee(route('frontend.company-brand.logo.dark', $company), false);
        $response->assertDontSee('/images/nexa-logo-dark.png', false);
    }
}

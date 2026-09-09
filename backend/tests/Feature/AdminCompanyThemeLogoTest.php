<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\AdminLogo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCompanyThemeLogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function dashboard_uses_tenant_light_and_dark_logos_when_both_are_uploaded(): void
    {
        $company = Company::query()->create([
            'name' => 'Logo Tenant',
            'is_active' => true,
            'logo_blob' => base64_encode('light-bytes'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('dark-bytes'),
            'logo_dark_mime_type' => 'image/png',
        ]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.companies.logo', $company), false)
            ->assertSee(route('admin.companies.logo.dark', $company), false);
    }

    #[Test]
    public function super_admin_can_fetch_dark_logo_while_a_tenant_is_selected(): void
    {
        $company = Company::query()->create([
            'name' => 'Dark Logo Tenant',
            'is_active' => true,
            'logo_blob' => base64_encode('light-bytes'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('dark-bytes'),
            'logo_dark_mime_type' => 'image/png',
        ]);
        $super = User::factory()->create(['company_id' => $company->id]);
        $super->assignRole('super-admin');

        $this->actingAs($super)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.companies.logo.dark', $company))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertSee('dark-bytes');
    }

    #[Test]
    public function admin_logo_helper_prefers_selected_tenant_company_logos(): void
    {
        $company = Company::query()->create([
            'name' => 'Gekozen Tenant',
            'is_active' => true,
            'logo_blob' => base64_encode('light-bytes'),
            'logo_mime_type' => 'image/png',
            'logo_dark_blob' => base64_encode('dark-bytes'),
            'logo_dark_mime_type' => 'image/png',
        ]);
        $super = User::factory()->create();
        $super->assignRole('super-admin');

        $this->actingAs($super);
        session(['selected_tenant' => $company->id]);

        $urls = AdminLogo::displayUrls($super);

        $this->assertSame('company', $urls['source']);
        $this->assertSame(route('admin.companies.logo', $company), $urls['light_url']);
        $this->assertSame(route('admin.companies.logo.dark', $company), $urls['dark_url']);
        $this->assertSame('Gekozen Tenant', $urls['alt']);
    }
}

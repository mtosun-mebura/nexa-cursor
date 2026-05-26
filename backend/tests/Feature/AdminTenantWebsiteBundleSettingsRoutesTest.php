<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTenantWebsiteBundleSettingsRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function tenant_website_bundle_export_redirects_guest_to_login(): void
    {
        $company = Company::query()->create(['name' => 'Bundle Test BV']);
        $response = $this->get(route('admin.settings.tenant-website-bundle.export', ['company_id' => $company->id]));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
    }

    #[Test]
    public function tenant_website_bundle_export_streams_zip_for_super_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $company = Company::query()->create(['name' => 'Bundle Test BV 2']);

        $response = $this->actingAs($user)->get(
            route('admin.settings.tenant-website-bundle.export', ['company_id' => $company->id])
        );

        $response->assertOk();
        $this->assertStringContainsString('zip', (string) $response->headers->get('Content-Type'));
    }
}

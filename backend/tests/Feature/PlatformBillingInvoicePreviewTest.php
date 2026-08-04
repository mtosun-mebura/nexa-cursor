<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\GeneralSetting;
use App\Models\PlatformBillingLineItem;
use App\Models\PlatformBillingPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformBillingInvoicePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    public function test_invoice_preview_reflects_custom_amount_discount_and_line_items(): void
    {
        $user = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Preview Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);
        $package = PlatformBillingPackage::query()->create([
            'name' => 'Starter',
            'monthly_amount' => 99.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $extraLine = PlatformBillingLineItem::query()->create([
            'name' => 'Setup',
            'unit_price' => 50.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'platform_billing_package_id' => $package->id,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.platform-billing.tenants.invoice-preview', $company),
            [
                '_token' => csrf_token(),
                'billing_mode' => 'custom',
                'custom_monthly_amount' => '150.50',
                'discount_percent' => '10',
                'extra_lines_one_time' => '1',
                'platform_billing_line_item_ids' => [$extraLine->id],
            ],
            ['Accept' => 'text/html']
        );

        $response->assertOk();
        $response->assertSee('Maandabonnement (maatwerk)', false);
        $response->assertSee('150,50', false);
        $response->assertSee('Korting (10%)', false);
        $response->assertSee('Setup', false);
        $response->assertSee('50,00', false);
        $response->assertSee('− € 15,05', false);
    }

    public function test_invoice_preview_shows_extra_lines_discount_under_extra_lines(): void
    {
        $user = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Preview Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);
        $package = PlatformBillingPackage::query()->create([
            'name' => 'Starter',
            'monthly_amount' => 100.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $extraLine = PlatformBillingLineItem::query()->create([
            'name' => 'Setup',
            'unit_price' => 200.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'platform_billing_package_id' => $package->id,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.platform-billing.tenants.invoice-preview', $company),
            [
                '_token' => csrf_token(),
                'billing_mode' => 'package',
                'platform_billing_package_id' => (string) $package->id,
                'discount_percent' => '0',
                'extra_lines_discount_percent' => '40',
                'extra_lines_one_time' => '1',
                'platform_billing_line_item_ids' => [$extraLine->id],
            ],
            ['Accept' => 'text/html']
        );

        $response->assertOk();
        $response->assertSee('Setup', false);
        $response->assertSee('Korting (40%)', false);
        $response->assertSee('− € 80,00', false);
    }

    public function test_invoice_preview_reflects_selected_package(): void
    {
        $user = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Preview Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);
        $package = PlatformBillingPackage::query()->create([
            'name' => 'Pro',
            'monthly_amount' => 199.00,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_CUSTOM,
            'custom_monthly_amount' => 10,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.platform-billing.tenants.invoice-preview', $company),
            [
                '_token' => csrf_token(),
                'billing_mode' => 'package',
                'platform_billing_package_id' => (string) $package->id,
                'discount_percent' => '0',
            ],
            ['Accept' => 'text/html']
        );

        $response->assertOk();
        $response->assertSee('Pro', false);
        $response->assertSee('199,00', false);
        $response->assertDontSee('Maandabonnement (maatwerk)', false);
    }

    public function test_invoice_preview_reflects_contact_name_and_notes(): void
    {
        $user = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Preview Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_FREE,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.platform-billing.tenants.invoice-preview', $company),
            [
                '_token' => csrf_token(),
                'billing_mode' => 'free',
                'billing_contact_name' => 'Mehmet Tosun',
                'notes' => "Interne opmerking\nTweede regel",
            ],
            ['Accept' => 'text/html']
        );

        $response->assertOk();
        $response->assertSee('Mehmet Tosun', false);
        $response->assertSee('Interne opmerking', false);
        $response->assertSee('Tweede regel', false);
        $response->assertSee('Notities', false);
    }

    public function test_invoice_preview_uses_admin_settings_logo_when_configured(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('settings/test-logo.png', 'fake-logo');

        GeneralSetting::query()->where('key', 'logo')->whereNull('company_id')->delete();
        GeneralSetting::query()->create([
            'key' => 'logo',
            'company_id' => null,
            'value' => 'settings/test-logo.png',
        ]);
        GeneralSetting::clearRequestCache();

        $user = $this->superAdmin();
        $company = Company::query()->create([
            'name' => 'Preview Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);

        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_FREE,
        ]);

        $response = $this->actingAs($user)->post(
            route('admin.platform-billing.tenants.invoice-preview', $company),
            [
                '_token' => csrf_token(),
                'billing_mode' => 'free',
            ],
            ['Accept' => 'text/html']
        );

        $response->assertOk();
        $response->assertSee('/admin/settings/logo', false);
        $response->assertDontSee('nexa-x-logo.png', false);
    }
}

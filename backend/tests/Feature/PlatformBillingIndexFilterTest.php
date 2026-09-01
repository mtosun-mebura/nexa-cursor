<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingPackage;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformBillingIndexFilterTest extends TestCase
{
    private function superAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function companyAdmin(): User
    {
        Role::query()->firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Andere Tenant BV', 'is_active' => true]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        return $user;
    }

    public function test_super_admin_sees_saas_invoices_without_tenant_notice(): void
    {
        $admin = $this->superAdmin();
        [$alpha, $beta] = $this->twoTenantsWithInvoices();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->assertSee('NEXA-facturen', false)
            ->assertSee('Alle tenants', false)
            ->assertSee($alpha->name, false)
            ->assertSee($beta->name, false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);
    }

    public function test_invoices_can_be_filtered_by_tenant_query(): void
    {
        $admin = $this->superAdmin();
        [$alpha, $beta] = $this->twoTenantsWithInvoices();

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.invoices.index', ['company_id' => $alpha->id]))
            ->assertOk()
            ->assertSee('SAAS-ALPHA-1', false)
            ->assertDontSee('SAAS-BETA-1', false);
    }

    public function test_invoices_follow_sidebar_tenant_until_page_filter_clears_it(): void
    {
        $admin = $this->superAdmin();
        [$alpha, $beta] = $this->twoTenantsWithInvoices();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $beta->id])
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->assertSee('SAAS-BETA-1', false)
            ->assertDontSee('SAAS-ALPHA-1', false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $beta->id])
            ->get(route('admin.platform-billing.invoices.index', ['company_id' => '']))
            ->assertOk()
            ->assertSee('SAAS-ALPHA-1', false)
            ->assertSee('SAAS-BETA-1', false);
    }

    public function test_tenant_subscriptions_can_be_filtered_without_tenant_notice(): void
    {
        $admin = $this->superAdmin();
        [$alpha, $beta] = $this->twoTenantsWithInvoices();

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.platform-billing.tenants.index'))
            ->assertOk()
            ->assertSee('Tenant-abonnementen', false)
            ->assertSee('Alle tenants', false)
            ->assertSee($alpha->name, false)
            ->assertSee($beta->name, false)
            ->assertDontSee('Kies links in de zijbalk een tenant', false);

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.tenants.index', ['company_id' => $alpha->id]))
            ->assertOk()
            ->assertSee($alpha->name, false)
            ->assertDontSee(route('admin.platform-billing.tenants.edit', $beta), false);
    }

    public function test_tenant_subscriptions_show_linked_package_name(): void
    {
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Pakket Tenant BV', 'is_active' => true]);
        $package = PlatformBillingPackage::query()->create([
            'name' => 'Pro Taxi Pakket',
            'monthly_amount' => 99,
            'currency' => 'EUR',
            'is_active' => true,
        ]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'platform_billing_package_id' => $package->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.tenants.index', ['company_id' => $company->id]))
            ->assertOk()
            ->assertSee('data-label="Pakket"', false)
            ->assertSee('Pro Taxi Pakket', false)
            ->assertDontSee('>package</td>', false)
            ->assertDontSee('>Modus</th>', false);
    }

    public function test_company_admin_cannot_open_saas_invoices(): void
    {
        $user = $this->companyAdmin();

        $response = $this->actingAs($user)
            ->get(route('admin.platform-billing.invoices.index'));

        $this->assertTrue(
            in_array($response->status(), [403, 302, 303], true),
            'Alleen super-admin mag NEXA-facturen zien, got: '.$response->status()
        );
        if (in_array($response->status(), [302, 303], true)) {
            $this->assertNotSame(route('admin.platform-billing.invoices.index'), $response->headers->get('Location'));
        }
    }

    public function test_invoice_list_shows_dutch_status_and_mollie_status(): void
    {
        $admin = $this->superAdmin();
        $company = Company::query()->create(['name' => 'Status Taxi BV', 'is_active' => true]);
        $invoice = PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'SAAS-STATUS-1',
            'billing_period' => '2026-02',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-02-01',
            'due_date' => '2026-02-15',
            'line_items' => [],
        ]);
        PlatformPayment::query()->create([
            'company_id' => $company->id,
            'platform_invoice_id' => $invoice->id,
            'type' => PlatformPayment::TYPE_INVOICE,
            'mollie_payment_id' => 'tr_status_test_1',
            'amount' => 121,
            'currency' => 'EUR',
            'status' => 'pending',
            'mollie_payload' => ['status' => 'open'],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->assertSee('kt-badge-warning', false)
            ->assertSee('Verzonden', false)
            ->assertDontSee('>sent</td>', false);
    }

    public function test_invoices_page_shows_dunning_workflow_from_settings(): void
    {
        PlatformBillingSetting::current()->update([
            'billing_day' => 5,
            'billing_time' => '07:30',
            'payment_terms_days' => 9,
            'dunning_first_interval_days' => 3,
            'dunning_interval_days' => 11,
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.platform-billing.invoices.index'))
            ->assertOk()
            ->assertSee('Werkwijze NEXA-facturatie', false)
            ->assertSee('dag 5 van de maand om 07:30', false)
            ->assertSee('9 dagen na factuurdatum', false)
            ->assertSee('3 dagen na de vervaldatum', false)
            ->assertSee('11 dagen na de 1e aanmaning', false)
            ->assertSee('11 dagen na de 2e aanmaning', false)
            ->assertSee(route('admin.platform-billing.settings.edit'), false)
            ->assertSee('Voorbeeld Taxi B.V.', false)
            ->assertSee('NEXA-abonnement', false)
            ->assertSee('VOORB', false)
            ->assertSee('Aanmaning NEXA-factuur', false)
            ->assertSee('Tweede aanmaning NEXA-factuur', false)
            ->assertSee('Gelieve binnen 11 dagen te betalen', false)
            ->assertSee('data-nexa-preview-open="invoice"', false)
            ->assertSee('data-nexa-preview-open="first"', false)
            ->assertSee('data-nexa-preview-open="second"', false)
            ->assertSee('nexa-werkwijze-icon-down', false)
            ->assertSee('nexa-werkwijze-icon-up', false)
            ->assertSee('aria-expanded="true"', false);
    }

    /**
     * @return array{0: Company, 1: Company}
     */
    private function twoTenantsWithInvoices(): array
    {
        $alpha = Company::query()->create(['name' => 'Alpha Taxi Filter', 'is_active' => true]);
        $beta = Company::query()->create(['name' => 'Beta Taxi Filter', 'is_active' => true]);

        PlatformInvoice::query()->create([
            'company_id' => $alpha->id,
            'invoice_number' => 'SAAS-ALPHA-1',
            'billing_period' => '2026-01',
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'line_items' => [],
        ]);
        PlatformInvoice::query()->create([
            'company_id' => $beta->id,
            'invoice_number' => 'SAAS-BETA-1',
            'billing_period' => '2026-01',
            'amount' => 80,
            'tax_amount' => 16.80,
            'total_amount' => 96.80,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => '2026-01-01',
            'due_date' => '2026-01-15',
            'line_items' => [],
        ]);

        return [$alpha, $beta];
    }
}

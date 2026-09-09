<?php

namespace Tests\Unit;

use App\Enums\AiChat\AiChatIntent;
use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformInvoice;
use App\Services\PlatformBilling\SuperAdminBillingInsightService;
use Tests\TestCase;

class SuperAdminBillingInsightServiceTest extends TestCase
{
    public function test_unpaid_this_month_lists_open_saas_invoices(): void
    {
        $company = Company::query()->create(['name' => 'Tosun Taxi', 'is_active' => true]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => 'factuur@tosun.test',
        ]);
        PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-OPEN-1',
            'billing_period' => now()->format('Y-m'),
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now()->startOfMonth(),
            'due_date' => now()->addDays(7),
            'line_items' => [],
        ]);

        $result = app(SuperAdminBillingInsightService::class)->execute(
            AiChatIntent::PlatformTenantsOnbetaald,
            ['company_id' => 0],
        );

        $this->assertSame(1, $result['count']);
        $this->assertStringContainsString('Tosun Taxi', $result['summary']['answer']);
        $this->assertStringContainsString('NEXA-OPEN-1', $result['summary']['answer']);
    }

    public function test_named_tenant_subscription_uses_hint(): void
    {
        $company = Company::query()->create(['name' => 'Taxi Royaal', 'is_active' => true, 'package_key' => 'business']);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => 'factuur@royaal.test',
        ]);

        $result = app(SuperAdminBillingInsightService::class)->execute(
            AiChatIntent::PlatformTenantAbonnement,
            ['company_id' => 0, 'query_hint' => 'Taxi Royaal'],
        );

        $this->assertGreaterThanOrEqual(1, $result['count']);
        $this->assertStringContainsString('Taxi Royaal', $result['summary']['answer']);
        $this->assertStringContainsString('pakket', mb_strtolower($result['summary']['answer']));
    }

    public function test_dashboard_actions_include_unpaid_invoice(): void
    {
        $company = Company::query()->create(['name' => 'Actie Taxi', 'is_active' => true]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => 'factuur@actie.test',
        ]);
        PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-ACTIE-1',
            'billing_period' => now()->format('Y-m'),
            'amount' => 80,
            'tax_amount' => 16.8,
            'total_amount' => 96.8,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now()->startOfMonth(),
            'due_date' => now()->subDay(),
            'line_items' => [],
        ]);

        $actions = app(SuperAdminBillingInsightService::class)->dashboardActions();
        $titles = array_column($actions, 'title');

        $this->assertNotEmpty($actions);
        $this->assertTrue(collect($actions)->contains(
            fn (array $row) => str_contains((string) $row['company_name'], 'Actie Taxi')
                && str_contains((string) $row['detail'], 'NEXA-ACTIE-1')
        ));
        $this->assertNotEmpty($titles);
    }
}

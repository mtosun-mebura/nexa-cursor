<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformInvoice;
use App\Services\PlatformBilling\SuperAdminBillingInsightService;
use Tests\TestCase;

class SuperAdminDashboardActionsTest extends TestCase
{
    public function test_saas_actions_card_lists_unpaid_invoice(): void
    {
        $company = Company::query()->create(['name' => 'Dashboard Taxi BV', 'is_active' => true]);
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'billing_email' => 'factuur@dashboard-taxi.test',
        ]);
        PlatformInvoice::query()->create([
            'company_id' => $company->id,
            'invoice_number' => 'NEXA-DASH-1',
            'billing_period' => now()->format('Y-m'),
            'amount' => 100,
            'tax_amount' => 21,
            'total_amount' => 121,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now()->startOfMonth(),
            'due_date' => now()->addDays(3),
            'line_items' => [],
        ]);

        $html = view('admin.dashboard.partials.saas-actions', [
            'saasActionItems' => app(SuperAdminBillingInsightService::class)->dashboardActions(),
        ])->render();

        $this->assertStringContainsString('Acties', $html);
        $this->assertStringContainsString('Dashboard Taxi BV', $html);
        $this->assertStringContainsString('NEXA-factuur deze maand niet betaald', $html);
        $this->assertStringContainsString('NEXA-DASH-1', $html);
    }
}

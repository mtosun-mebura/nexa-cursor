<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PlatformInvoice;
use App\Services\PlatformBilling\TenantSubscriptionService;
use App\Support\TenantPackageAddon;
use Carbon\Carbon;
use Tests\TestCase;

class TenantPackageAddonBillingTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_mid_month_addon_creates_prorated_invoice_line(): void
    {
        Carbon::setTestNow('2026-09-07 10:00:00');
        $company = $this->companyWithPackage();
        $service = app(TenantSubscriptionService::class);
        $this->markTrialEnded($company, $service);

        $company->package_addons = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-09-07',
            ],
        ], []);
        $company->save();

        $invoice = $service->syncPackageAddonsFromCompany($company->fresh(), []);

        $this->assertNotNull($invoice);
        $this->assertSame('2026-09', $invoice->billing_period);
        $gpsLine = collect($invoice->line_items)->first(
            fn (array $line) => ($line['addon_key'] ?? '') === TenantPackageAddon::GPS_TRACKING
        );
        $this->assertNotNull($gpsLine);
        $this->assertSame(round(19 * (24 / 30), 2), (float) $gpsLine['total']);
        $this->assertTrue((bool) ($gpsLine['activation'] ?? false));
        $this->assertStringContainsString('dagen', (string) $gpsLine['description']);

        $stored = $company->fresh()->package_addons[TenantPackageAddon::GPS_TRACKING];
        $this->assertSame('2026-09', $stored['prepaid_through']);
    }

    public function test_next_month_first_charges_full_amount_on_invoice(): void
    {
        Carbon::setTestNow('2026-09-07 10:00:00');
        $company = $this->companyWithPackage();
        $service = app(TenantSubscriptionService::class);
        $this->markTrialEnded($company, $service);

        $company->package_addons = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::FLEET => [
                'quantity' => 1,
                'starts_at' => '2026-10-01',
            ],
        ], []);
        $company->save();

        $invoice = $service->syncPackageAddonsFromCompany($company->fresh(), []);

        $this->assertNotNull($invoice);
        $this->assertSame('2026-10', $invoice->billing_period);
        $line = collect($invoice->line_items)->first(
            fn (array $line) => ($line['addon_key'] ?? '') === TenantPackageAddon::FLEET
        );
        $this->assertNotNull($line);
        $this->assertSame(249.0, (float) $line['total']);
        $this->assertStringContainsString('oktober', mb_strtolower((string) $line['description']));
        $this->assertGreaterThan(0, (float) $invoice->total_amount);

        $this->assertSame(
            0,
            PlatformInvoice::query()
                ->where('company_id', $company->id)
                ->where('billing_period', '2026-09')
                ->count()
        );
    }

    public function test_trial_addon_activation_does_not_create_an_invoice(): void
    {
        Carbon::setTestNow('2026-09-07 10:00:00');
        $company = $this->companyWithPackage();
        $service = app(TenantSubscriptionService::class);
        $this->markInTrial($company, $service);

        $company->package_addons = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-09-07',
            ],
        ], []);
        $company->save();

        $invoice = $service->syncPackageAddonsFromCompany($company->fresh(), []);

        $this->assertNull($invoice);
        $this->assertSame(0, PlatformInvoice::query()->where('company_id', $company->id)->count());
    }

    public function test_paid_cancel_keeps_current_month_entitlement_off_next_invoice_period(): void
    {
        Carbon::setTestNow('2026-09-08 10:00:00');
        $company = $this->companyWithPackage();
        $service = app(TenantSubscriptionService::class);
        $this->markTrialEnded($company, $service);

        $previous = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => [
                'quantity' => 1,
                'starts_at' => '2026-08-01',
            ],
        ], []);
        $company->package_addons = TenantPackageAddon::normalizeRecords([
            TenantPackageAddon::GPS_TRACKING => ['quantity' => 0],
        ], $previous, false);
        $company->save();

        $service->syncPackageAddonsFromCompany($company->fresh(), $previous);

        $stored = $company->fresh()->package_addons[TenantPackageAddon::GPS_TRACKING];
        $this->assertTrue(TenantPackageAddon::isPendingCancel($stored));
        $this->assertSame(1, TenantPackageAddon::effectiveSelections($company->fresh()->package_addons)[TenantPackageAddon::GPS_TRACKING]);

        $profile = $company->fresh()->billingProfile;
        $this->assertSame(19.0, $profile->packageAddonMonthlyAmount(Carbon::parse('2026-09-01'), false));
        $this->assertSame(0.0, $profile->packageAddonMonthlyAmount(Carbon::parse('2026-10-01'), false));
        $packageOnly = round(max(0, $profile->subscriptionBaseAmount() * (1 - ($profile->discountPercent() / 100))), 2);
        $this->assertSame($packageOnly, $profile->mollieRecurringAmount(Carbon::parse('2026-09-08')));
    }

    private function companyWithPackage(): Company
    {
        return Company::query()->create([
            'name' => 'Addon Billing '.uniqid(),
            'email' => 'addon-billing@example.test',
            'is_active' => true,
            'package_key' => 'business',
        ]);
    }

    private function markTrialEnded(Company $company, TenantSubscriptionService $service): void
    {
        $profile = $service->ensureProfile($company);
        $profile->update([
            'trial_started_at' => '2026-07-01',
            'trial_ends_at' => '2026-08-01',
            'subscription_start_date' => '2026-08-01',
        ]);
    }

    private function markInTrial(Company $company, TenantSubscriptionService $service): void
    {
        $profile = $service->ensureProfile($company);
        $profile->update([
            'trial_started_at' => '2026-09-01',
            'trial_ends_at' => '2026-10-01',
            'subscription_start_date' => '2026-10-01',
        ]);
    }
}

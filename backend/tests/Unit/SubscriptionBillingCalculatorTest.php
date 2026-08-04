<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\PlatformBillingPackage;
use App\Services\PlatformBilling\SubscriptionBillingCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionBillingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionBillingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = app(SubscriptionBillingCalculator::class);
    }

    public function test_mid_month_start_covers_partial_and_next_full_month(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $profile = $this->profileWithPackage(100.00, '2026-06-15');
        $segments = $this->calculator->advanceCoverageSegments($profile);

        $this->assertCount(2, $segments);
        $this->assertSame('2026-06', $segments[0]['key']);
        $this->assertSame(16, $segments[0]['days']);
        $this->assertSame('2026-07', $segments[1]['key']);
        $this->assertSame(31, $segments[1]['days']);

        $amount = $this->calculator->proratedSubscriptionAmount($profile);
        $expected = round((100 * (16 / 30)) + 100, 2);
        $this->assertSame($expected, $amount);
    }

    public function test_mollie_subscription_starts_after_advance_period(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $profile = $this->profileWithPackage(100.00, '2026-06-15');

        $this->assertSame('2026-08-01', $this->calculator->mollieSubscriptionStartDate($profile));
    }

    public function test_end_date_limits_subscription_times(): void
    {
        Carbon::setTestNow('2026-06-15 10:00:00');

        $profile = $this->profileWithPackage(100.00, '2026-06-15');
        $profile->subscription_end_date = '2026-09-01';
        $profile->mollie_subscription_id = 'sub_test123';
        $profile->save();

        $this->assertSame(1, $this->calculator->mollieSubscriptionTimes($profile));
        $this->assertTrue($this->calculator->shouldCancelMollieSubscription($profile, Carbon::parse('2026-09-01')));
        $this->assertFalse($this->calculator->shouldCancelMollieSubscription($profile, Carbon::parse('2026-08-31')));
    }

    public function test_on_first_of_month_only_current_month_is_billed(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');

        $profile = $this->profileWithPackage(100.00, '2026-06-01');
        $segments = $this->calculator->advanceCoverageSegments($profile);

        $this->assertCount(1, $segments);
        $this->assertSame('2026-06', $segments[0]['key']);
        $this->assertSame('2026-07-01', $this->calculator->mollieSubscriptionStartDate($profile));
    }

    private function profileWithPackage(float $monthlyAmount, string $startDate): CompanyBillingProfile
    {
        $company = Company::query()->create([
            'name' => 'Test Tenant',
            'email' => 'billing@example.test',
            'is_active' => true,
        ]);
        $package = PlatformBillingPackage::query()->create([
            'name' => 'Basis',
            'monthly_amount' => $monthlyAmount,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'platform_billing_package_id' => $package->id,
            'subscription_start_date' => $startDate,
            'auto_collect_enabled' => true,
        ])->load('package');
    }
}

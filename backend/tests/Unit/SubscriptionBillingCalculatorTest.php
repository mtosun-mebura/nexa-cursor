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

    public function test_september_4_start_prorates_remaining_days_plus_next_month(): void
    {
        app()->setLocale('nl');
        Carbon::setLocale('nl');
        Carbon::setTestNow('2026-09-04 10:00:00');

        $profile = $this->profileWithPackage(99.00, '2026-09-04');
        $segments = $this->calculator->advanceCoverageSegments($profile);

        $this->assertCount(2, $segments);
        $this->assertSame('2026-09', $segments[0]['key']);
        $this->assertSame(27, $segments[0]['days']);
        $this->assertSame('2026-10', $segments[1]['key']);

        $expected = round((99 * (27 / 30)) + 99, 2);
        $this->assertSame($expected, $this->calculator->proratedSubscriptionAmount($profile));

        $presentation = $this->calculator->firstCollectionPresentation($profile);
        $this->assertSame('4 september 2026', $presentation['start_label']);
        $this->assertStringContainsString('4 t/m 30 september 2026', $presentation['coverage_label']);
        $this->assertStringContainsString('oktober 2026', $presentation['coverage_label']);
        $this->assertSame('2026-11-01', $presentation['recurring_from']?->toDateString());
        $this->assertSame($expected, $presentation['first_amount_excl']);
        $this->assertCount(2, $presentation['period_lines']);
        $this->assertSame('4 t/m 30 september 2026', $presentation['period_lines'][0]['label']);
        $this->assertSame(round(99 * (27 / 30), 2), $presentation['period_lines'][0]['amount']);
        $this->assertSame('oktober 2026', $presentation['period_lines'][1]['label']);
        $this->assertSame(99.0, $presentation['period_lines'][1]['amount']);
        $this->assertEqualsWithDelta(
            $presentation['period_lines'][0]['amount'] + $presentation['period_lines'][1]['amount'],
            $presentation['first_amount_excl'],
            0.001
        );
        $this->assertEqualsWithDelta(
            $presentation['first_amount_excl'] + $presentation['tax_amount'],
            $presentation['first_amount_incl'],
            0.001
        );

        $table = app(\App\Services\SaasBillingStartEmailTemplateService::class)->amountTableHtml($presentation);
        $this->assertStringContainsString('4 t/m 30 september 2026', $table);
        $this->assertStringContainsString('oktober 2026', $table);
        $this->assertStringContainsString('btw 21%', $table);
        $this->assertStringContainsString('Totaal incl. btw', $table);
        $this->assertStringContainsString('Subtotaal excl. btw', $table);

        $html = app(\App\Services\SaasBillingStartEmailTemplateService::class)->ensureExists()->html_content;
        $this->assertStringNotContainsString('AMOUNT_TABLE', $html);
        $this->assertStringContainsString('factuur in de bijlage', $html);
        $this->assertStringContainsString('Via de knop hieronder kan de eerste betaling voldaan worden', $html);
        $this->assertStringContainsString('overige maanden via automatische incasso', $html);
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
            'agreed_monthly_amount' => $monthlyAmount,
            'subscription_start_date' => $startDate,
            'auto_collect_enabled' => true,
        ])->load('package');
    }
}

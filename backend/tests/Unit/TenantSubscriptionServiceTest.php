<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Services\NexaPricingService;
use App\Services\PlatformBilling\TenantSubscriptionService;
use Carbon\Carbon;
use Tests\TestCase;

class TenantSubscriptionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_year_change_date_is_the_contract_anniversary(): void
    {
        Carbon::setTestNow('2026-06-01 10:00:00');
        $profile = $this->profileStarting('2026-03-15');
        $service = app(TenantSubscriptionService::class);

        $this->assertFalse($service->isPastFirstYear($profile));
        $this->assertSame('2027-03-15', $service->nextAllowedChangeDate($profile)->toDateString());
        $this->assertSame('2027-03-15', $service->contractAnniversary($profile)->toDateString());
    }

    public function test_after_first_year_change_date_is_end_of_current_month(): void
    {
        Carbon::setTestNow('2027-04-10 10:00:00');
        $profile = $this->profileStarting('2026-03-15');
        $service = app(TenantSubscriptionService::class);

        $this->assertTrue($service->isPastFirstYear($profile));
        $this->assertSame('2027-04-30', $service->nextAllowedChangeDate($profile)->toDateString());
    }

    public function test_upgrade_proration_covers_remaining_days_at_the_price_delta(): void
    {
        $service = app(TenantSubscriptionService::class);
        $asOf = Carbon::parse('2026-03-15');
        $expected = round(50 * (17 / 31), 2);

        $this->assertSame($expected, $service->prorationForRemainingMonth(49, 99, $asOf));
        $this->assertSame(0.0, $service->prorationForRemainingMonth(99, 49, $asOf));
    }

    public function test_nexa_package_amounts_match_start_pro_business(): void
    {
        $pricing = app(NexaPricingService::class);

        $this->assertSame(49.0, $pricing->monthlyAmountForKey('start'));
        $this->assertSame(99.0, $pricing->monthlyAmountForKey('pro'));
        $this->assertSame(179.0, $pricing->monthlyAmountForKey('business'));
        $this->assertSame(0, $pricing->packageRank('start'));
        $this->assertSame(1, $pricing->packageRank('pro'));
        $this->assertSame(2, $pricing->packageRank('business'));
    }

    private function profileStarting(string $startDate): CompanyBillingProfile
    {
        $company = Company::query()->create([
            'name' => 'Sub Test '.uniqid(),
            'is_active' => true,
            'package_key' => 'start',
            'created_at' => $startDate.' 09:00:00',
            'updated_at' => $startDate.' 09:00:00',
        ]);

        return CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => CompanyBillingProfile::MODE_PACKAGE,
            'subscription_start_date' => $startDate,
            'auto_collect_enabled' => true,
        ])->load('company');
    }
}

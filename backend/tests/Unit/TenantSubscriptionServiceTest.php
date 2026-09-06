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

    public function test_new_profile_with_free_months_starts_billing_after_the_trial(): void
    {
        Carbon::setTestNow('2026-03-15 10:00:00');
        $pricing = app(NexaPricingService::class)->get();
        $pricing['packages'][0]['free_months'] = 1;
        app(NexaPricingService::class)->save($pricing);

        $company = Company::query()->create([
            'name' => 'Trial Co '.uniqid(),
            'is_active' => true,
            'package_key' => 'start',
            'email' => 'trial@example.com',
            'created_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-15 09:00:00',
        ]);

        $service = app(TenantSubscriptionService::class);
        $profile = $service->ensureProfile($company);

        $this->assertSame('2026-03-15', $profile->trial_started_at->toDateString());
        $this->assertSame('2026-04-15', $profile->trial_ends_at->toDateString());
        $this->assertSame('2026-04-15', $profile->subscription_start_date->toDateString());
        $this->assertTrue($service->isInTrial($profile));
        $this->assertSame(31, $service->trialDaysRemaining($profile));
        $this->assertSame('2027-03-15', $service->contractAnniversary($profile)->toDateString());

        $calculator = app(\App\Services\PlatformBilling\SubscriptionBillingCalculator::class);
        $this->assertFalse($calculator->isBillable($profile, Carbon::parse('2026-04-14')));
        $this->assertTrue($calculator->isBillable($profile, Carbon::parse('2026-04-15')));
    }

    public function test_agreed_monthly_amount_stays_when_catalog_price_changes(): void
    {
        Carbon::setTestNow('2026-03-15 10:00:00');
        $pricing = app(NexaPricingService::class);
        $data = $pricing->get();
        $data['packages'][0]['free_months'] = 1;
        $data['packages'][0]['price'] = '99';
        $pricing->save($data);

        $company = Company::query()->create([
            'name' => 'Locked Price '.uniqid(),
            'is_active' => true,
            'package_key' => 'start',
            'email' => 'locked@example.com',
        ]);
        $company->forceFill([
            'created_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-15 09:00:00',
        ])->save();

        $service = app(TenantSubscriptionService::class);
        $profile = $service->ensureProfile($company->fresh());
        $locked = $profile->resolveMonthlyAmount();
        $this->assertSame(99.0, $locked);

        $data = $pricing->get();
        $data['packages'][0]['price'] = '999';
        $pricing->save($data);
        $service->syncPlatformPackagesFromPricing();

        $profile->refresh()->load('package');
        $this->assertSame(99.0, $profile->resolveMonthlyAmount());
        $this->assertSame(999.0, $pricing->monthlyAmountForKey('start'));
    }

    public function test_ending_trial_keeps_access_until_trial_end_and_can_be_reactivated(): void
    {
        Carbon::setTestNow('2026-04-10 10:00:00');
        $pricing = app(NexaPricingService::class)->get();
        $pricing['packages'][0]['free_months'] = 1;
        app(NexaPricingService::class)->save($pricing);

        $company = Company::query()->create([
            'name' => 'Stop Trial '.uniqid(),
            'is_active' => true,
            'package_key' => 'start',
            'email' => 'stop@example.com',
        ]);
        $company->forceFill([
            'created_at' => '2026-03-15 09:00:00',
            'updated_at' => '2026-03-15 09:00:00',
        ])->save();
        $company->refresh();

        $service = app(TenantSubscriptionService::class);
        $profile = $service->ensureProfile($company);
        $this->assertSame('2026-03-15', $profile->trial_started_at->toDateString());
        $this->assertSame('2026-04-15', $profile->trial_ends_at->toDateString());
        $this->assertSame('2026-04-15', $profile->subscription_start_date->toDateString());

        $profile = $service->endTrialAndDeactivate($company);

        $company->refresh();
        $this->assertTrue((bool) $company->is_active);
        $this->assertNull($profile->subscription_end_date);
        $this->assertTrue($service->isInTrial($profile));
        $this->assertTrue($service->hasDeclinedTrial($profile));
        $this->assertSame('2026-03-15', $profile->trial_started_at->toDateString());
        $this->assertSame('2026-04-15', $profile->trial_ends_at->toDateString());
        $this->assertSame('2026-04-15', $profile->subscription_start_date->toDateString());
        $this->assertDatabaseHas('company_subscription_changes', [
            'company_id' => $company->id,
            'change_type' => 'trial_end',
            'status' => 'scheduled',
        ]);

        $calculator = app(\App\Services\PlatformBilling\SubscriptionBillingCalculator::class);
        $this->assertFalse($calculator->isBillable($profile, Carbon::parse('2026-04-15')));

        $reactivated = $service->withdrawPending($company->fresh());
        $company->refresh();
        $this->assertTrue((bool) $company->is_active);
        $this->assertFalse($service->hasDeclinedTrial($reactivated));
        $this->assertSame('2026-03-15', $reactivated->trial_started_at->toDateString());
        $this->assertSame('2026-04-15', $reactivated->trial_ends_at->toDateString());
        $this->assertSame('2026-04-15', $reactivated->subscription_start_date->toDateString());
        $this->assertTrue($calculator->isBillable($reactivated, Carbon::parse('2026-04-15')));

        $service->endTrialAndDeactivate($company->fresh());
        $this->assertTrue($service->applyDueChange($company->fresh()->billingProfile, Carbon::parse('2026-04-15')));
        $company->refresh();
        $this->assertFalse((bool) $company->is_active);
        $this->assertSame('2026-04-15', $company->billingProfile->subscription_end_date->toDateString());
        $this->assertDatabaseHas('company_subscription_changes', [
            'company_id' => $company->id,
            'change_type' => 'trial_end',
            'status' => 'applied',
        ]);
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

    public function test_emergency_terminate_always_uses_end_of_current_month_even_in_first_year(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $profile = $this->profileStarting('2026-03-15');
        $service = app(TenantSubscriptionService::class);

        $this->assertFalse($service->isPastFirstYear($profile));
        $this->assertSame('2027-03-15', $service->nextAllowedChangeDate($profile)->toDateString());

        $updated = $service->emergencyTerminateAtMonthEnd($profile->company);
        $this->assertSame('cancel', $updated->pending_change_type);
        $this->assertSame('2026-06-30', $updated->subscription_end_date->toDateString());
        $this->assertSame('2026-06-30', $updated->pending_change_effective_on->toDateString());
    }

    public function test_emergency_terminate_overrides_scheduled_contract_cancel(): void
    {
        Carbon::setTestNow('2026-06-10 10:00:00');
        $profile = $this->profileStarting('2026-03-15');
        $service = app(TenantSubscriptionService::class);

        $service->scheduleCancel($profile->company);
        $profile->refresh();
        $this->assertSame('2027-03-15', $profile->subscription_end_date->toDateString());

        $updated = $service->emergencyTerminateAtMonthEnd($profile->company);
        $this->assertSame('2026-06-30', $updated->subscription_end_date->toDateString());
        $this->assertSame('2026-06-30', $updated->pending_change_effective_on->toDateString());
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

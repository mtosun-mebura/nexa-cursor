<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\PayoutIdentity;
use App\Models\RidePlatformSettlement;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\Payout\PlatformRidePayoutService;
use App\Services\Payout\PlatformRideSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformRideSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'nexa_payout.platform_collect_enabled' => true,
            'nexa_payout.platform_payout_auto_succeed' => true,
            'nexa_payout.network_owner_share_of_net_percent' => 15,
            'nexa_payout.network_fulfiller_share_of_net_percent' => 85,
        ]);
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10]);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_30_100000_create_ride_platform_settlements_table.php']);
    }

    #[Test]
    public function marketplace_breakdown_gives_full_net_to_claimer(): void
    {
        $company = Company::query()->create(['name' => 'Claimer BV', 'is_active' => true]);
        $ride = new RideRequest;
        $ride->forceFill([
            'company_id' => $company->id,
            'fulfilling_company_id' => $company->id,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'final_price' => 100,
            'status' => RideRequest::STATUS_COMPLETED,
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
        ]);
        $ride->id = 9001;

        $service = app(PlatformRideSettlementService::class);
        $this->assertSame(RidePlatformSettlement::MODEL_MARKETPLACE, $service->resolveModel($ride));

        $b = $service->computeBreakdown($ride);
        $this->assertSame(100.0, $b['gross_amount']);
        $this->assertSame(10, $b['nexa_fee_percent']);
        $this->assertSame(10.0, $b['nexa_fee_amount']);
        $this->assertSame(90.0, $b['net_amount']);
        $this->assertSame(90.0, $b['owner_share_amount']);
        $this->assertSame(0.0, $b['fulfiller_share_amount']);
    }

    #[Test]
    public function network_breakdown_splits_net_between_owner_and_fulfiller(): void
    {
        $owner = Company::query()->create(['name' => 'Taxi A', 'is_active' => true]);
        $fulfiller = Company::query()->create(['name' => 'Taxi B', 'is_active' => true]);
        $ride = new RideRequest;
        $ride->forceFill([
            'company_id' => $owner->id,
            'fulfilling_company_id' => $fulfiller->id,
            'source' => RideRequest::SOURCE_BOOKING,
            'final_price' => 100,
            'status' => RideRequest::STATUS_COMPLETED,
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
        ]);
        $ride->id = 9002;

        $service = app(PlatformRideSettlementService::class);
        $this->assertSame(RidePlatformSettlement::MODEL_NETWORK, $service->resolveModel($ride));

        $b = $service->computeBreakdown($ride);
        $this->assertSame(10.0, $b['nexa_fee_amount']);
        $this->assertSame(90.0, $b['net_amount']);
        $this->assertSame(15, $b['owner_share_percent']);
        $this->assertSame(85, $b['fulfiller_share_percent']);
        $this->assertSame(13.5, $b['owner_share_amount']); // 15% of 90
        $this->assertSame(76.5, $b['fulfiller_share_amount']);
    }

    #[Test]
    public function tenant_only_ride_is_not_platform_settled(): void
    {
        $company = Company::query()->create(['name' => 'Tenant', 'is_active' => true]);
        $ride = new RideRequest;
        $ride->forceFill([
            'company_id' => $company->id,
            'fulfilling_company_id' => null,
            'source' => RideRequest::SOURCE_BOOKING,
            'final_price' => 50,
            'status' => RideRequest::STATUS_COMPLETED,
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
        ]);
        $ride->id = 9003;

        $service = app(PlatformRideSettlementService::class);
        $this->assertNull($service->resolveModel($ride));
        $this->assertNull($service->ensureFromEligibleRide($ride));
        $this->assertSame(0, RidePlatformSettlement::query()->count());
    }

    #[Test]
    public function ensure_creates_pending_ledger_once(): void
    {
        $company = Company::query()->create(['name' => 'Claimer', 'is_active' => true]);
        $ride = new RideRequest;
        $ride->forceFill([
            'company_id' => $company->id,
            'fulfilling_company_id' => $company->id,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'final_price' => 80,
            'status' => RideRequest::STATUS_COMPLETED,
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
        ]);
        $ride->id = 9100;

        $service = app(PlatformRideSettlementService::class);
        $a = $service->ensureFromEligibleRide($ride);
        $b = $service->ensureFromEligibleRide($ride);

        $this->assertNotNull($a);
        $this->assertSame($a->id, $b->id);
        $this->assertSame(RidePlatformSettlement::STATUS_PENDING_PAYOUT, $a->status);
        $this->assertSame(1, RidePlatformSettlement::query()->count());
        $this->assertEquals(8.0, (float) $a->nexa_fee_amount);
        $this->assertEquals(72.0, (float) $a->net_amount);
    }

    #[Test]
    public function marketplace_payout_succeeds_when_identity_enabled(): void
    {
        $company = Company::query()->create(['name' => 'Claimer Pay', 'is_active' => true]);
        $this->enablePayout($company);

        $settlement = RidePlatformSettlement::query()->create([
            'ride_request_id' => 9200,
            'model' => RidePlatformSettlement::MODEL_MARKETPLACE,
            'owner_company_id' => $company->id,
            'fulfiller_company_id' => $company->id,
            'gross_amount' => 100,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 10,
            'net_amount' => 90,
            'owner_share_percent' => 100,
            'owner_share_amount' => 90,
            'fulfiller_share_percent' => 0,
            'fulfiller_share_amount' => 0,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
        ]);

        $status = app(PlatformRidePayoutService::class)->processOne($settlement);
        $settlement->refresh();

        $this->assertSame(RidePlatformSettlement::STATUS_PAID_OUT, $status);
        $this->assertSame(RidePlatformSettlement::STATUS_PAID_OUT, $settlement->status);
        $this->assertNotNull($settlement->paid_out_at);
        $this->assertNotEmpty($settlement->payout_lines);
        $this->assertTrue($settlement->payout_lines[0]['ok']);
    }

    #[Test]
    public function network_payout_pays_both_companies(): void
    {
        $owner = Company::query()->create(['name' => 'Owner A', 'is_active' => true]);
        $fulfiller = Company::query()->create(['name' => 'Fulfiller B', 'is_active' => true]);
        $this->enablePayout($owner);
        $this->enablePayout($fulfiller);

        $settlement = RidePlatformSettlement::query()->create([
            'ride_request_id' => 9300,
            'model' => RidePlatformSettlement::MODEL_NETWORK,
            'owner_company_id' => $owner->id,
            'fulfiller_company_id' => $fulfiller->id,
            'gross_amount' => 100,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 10,
            'net_amount' => 90,
            'owner_share_percent' => 15,
            'owner_share_amount' => 13.5,
            'fulfiller_share_percent' => 85,
            'fulfiller_share_amount' => 76.5,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
        ]);

        app(PlatformRidePayoutService::class)->processOne($settlement);
        $settlement->refresh();

        $this->assertSame(RidePlatformSettlement::STATUS_PAID_OUT, $settlement->status);
        $this->assertCount(2, $settlement->payout_lines);
        $roles = collect($settlement->payout_lines)->pluck('role')->all();
        $this->assertSame(['owner', 'fulfiller'], $roles);
        $this->assertEquals(13.5, (float) $settlement->payout_lines[0]['amount']);
        $this->assertEquals(76.5, (float) $settlement->payout_lines[1]['amount']);
    }

    #[Test]
    public function failed_payout_escalates_to_manual_after_three_attempts(): void
    {
        config(['nexa_payout.platform_payout_auto_succeed' => false]);
        $company = Company::query()->create(['name' => 'Fail Co', 'is_active' => true]);
        $this->enablePayout($company);

        $settlement = RidePlatformSettlement::query()->create([
            'ride_request_id' => 9400,
            'model' => RidePlatformSettlement::MODEL_MARKETPLACE,
            'owner_company_id' => $company->id,
            'fulfiller_company_id' => $company->id,
            'gross_amount' => 50,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 5,
            'net_amount' => 45,
            'owner_share_percent' => 100,
            'owner_share_amount' => 45,
            'fulfiller_share_percent' => 0,
            'fulfiller_share_amount' => 0,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_PENDING_PAYOUT,
            'payout_attempts' => 0,
        ]);

        $payouts = app(PlatformRidePayoutService::class);
        $this->assertSame(RidePlatformSettlement::STATUS_FAILED, $payouts->processOne($settlement->fresh()));
        $this->assertSame(RidePlatformSettlement::STATUS_FAILED, $payouts->processOne($settlement->fresh()));
        $this->assertSame(RidePlatformSettlement::STATUS_MANUAL_REQUIRED, $payouts->processOne($settlement->fresh()));

        $settlement->refresh();
        $this->assertSame(3, (int) $settlement->payout_attempts);
        $this->assertSame(RidePlatformSettlement::STATUS_MANUAL_REQUIRED, $settlement->status);
    }

    #[Test]
    public function force_manual_succeed_marks_paid_out(): void
    {
        config(['nexa_payout.platform_payout_auto_succeed' => false]);
        $company = Company::query()->create(['name' => 'Manual Co', 'is_active' => true]);
        // Identity not enabled — force still succeeds
        $settlement = RidePlatformSettlement::query()->create([
            'ride_request_id' => 9500,
            'model' => RidePlatformSettlement::MODEL_MARKETPLACE,
            'owner_company_id' => $company->id,
            'fulfiller_company_id' => $company->id,
            'gross_amount' => 40,
            'nexa_fee_percent' => 10,
            'nexa_fee_amount' => 4,
            'net_amount' => 36,
            'owner_share_percent' => 100,
            'owner_share_amount' => 36,
            'fulfiller_share_percent' => 0,
            'fulfiller_share_amount' => 0,
            'currency' => 'EUR',
            'status' => RidePlatformSettlement::STATUS_MANUAL_REQUIRED,
        ]);

        $status = app(PlatformRidePayoutService::class)->processOne($settlement, forceManualSucceed: true);
        $this->assertSame(RidePlatformSettlement::STATUS_PAID_OUT, $status);
    }

    private function enablePayout(Company $company): void
    {
        $identity = PayoutIdentity::query()->updateOrCreate(
            ['owner_key' => 'company:'.$company->id.':mollie'],
            [
                'company_id' => $company->id,
                'settlement_party' => PayoutIdentity::PARTY_COMPANY,
                'provider' => PayoutIdentity::PROVIDER_MOLLIE,
                'provider_account_id' => 'acc_'.$company->id,
                'capability_status' => PayoutIdentity::STATUS_ENABLED,
                'masked_destination' => 'NL** **** 000'.$company->id,
                'verified_at' => now(),
                'disabled_at' => null,
            ]
        );
        $this->assertTrue($identity->isEnabled());
    }
}

<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\PayoutIdentity;
use App\Models\RidePlatformSettlement;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideSettlementEligibilityService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Services\Payout\DriverRidePayoutService;
use App\Services\Payout\MollieConnectBalanceTransferClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DriverRideCompletionPayoutTest extends TestCase
{
    private FakeDriverTransferClient $transfers;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
            'nexa_payout.platform_collect_enabled' => true,
            'nexa_payout.platform_payout_auto_succeed' => false,
        ]);

        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_09_30_100000_create_ride_platform_settlements_table.php',
        ]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('fulfilling_company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('completed');
            $table->string('source', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->decimal('final_price', 12, 2)->nullable();
            $table->decimal('quoted_price', 12, 2)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('actual_distance_meters')->nullable();
            $table->dateTime('trip_started_at')->nullable();
            $table->dateTime('trip_completed_at')->nullable();
            $table->string('settlement_status', 32)->nullable();
            $table->timestamp('settlement_hold_until')->nullable();
            $table->json('settlement_risk_flags')->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamp('settlement_evaluated_at')->nullable();
            $table->timestamp('settlement_eligible_at')->nullable();
            $table->timestamps();
        });

        TaxiDispatchSchema::resetCache();
        $this->transfers = new FakeDriverTransferClient;
        $this->app->instance(MollieConnectBalanceTransferClient::class, $this->transfers);
    }

    #[Test]
    public function completion_pays_driver_net_once_and_messages_after_transfer(): void
    {
        $driver = User::factory()->create();
        $company = Company::query()->create(['name' => 'Claimer BV', 'is_active' => true]);
        $this->enableDriverPayout($driver, $company, 'org_driver42');

        $ride = $this->completedRide($driver, $company, [
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'final_price' => 100,
        ]);

        $result = app(RideSettlementEligibilityService::class)->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertContains($result->settlement_status, [
            RideRequest::SETTLEMENT_ELIGIBLE,
            RideRequest::SETTLEMENT_SETTLED,
        ]);
        $this->assertNull($result->settlement_hold_until);
        $this->assertCount(1, $this->transfers->calls);
        $this->assertEquals(90.0, $this->transfers->calls[0]['amount']);
        $this->assertSame('org_driver42', $this->transfers->calls[0]['account']);
        $this->assertStringContainsString('ride-'.$ride->id.'-driver', $this->transfers->calls[0]['reference']);

        $settlement = RidePlatformSettlement::query()->where('ride_request_id', $ride->id)->first();
        $this->assertNotNull($settlement);
        $this->assertSame(RidePlatformSettlement::STATUS_PAID_OUT, $settlement->status);
        $this->assertEquals(10.0, (float) $settlement->nexa_fee_amount);
        $this->assertEquals(90.0, (float) $settlement->net_amount);
        $this->assertSame('cbtr_test_1', $settlement->payout_lines[0]['transfer_id'] ?? null);
        $this->assertStringNotContainsString('stub_', (string) ($settlement->payout_lines[0]['transfer_id'] ?? ''));

        $notices = app(DriverRidePayoutService::class)->noticesForDriver($driver->id);
        $this->assertCount(1, $notices);
        $this->assertStringContainsString('90,00', $notices[0]['message']);
        $this->assertStringContainsString('overgemaakt', $notices[0]['message']);
        $this->assertStringContainsString('#'.$ride->id, $notices[0]['message']);

        app(RideSettlementEligibilityService::class)->evaluateAfterCompletion('module_taxi', $ride->fresh());
        app(DriverRidePayoutService::class)->payoutCompletedRide($ride->fresh());

        $this->assertCount(1, $this->transfers->calls);
        $this->assertCount(1, app(DriverRidePayoutService::class)->noticesForDriver($driver->id));
    }

    #[Test]
    public function own_website_ride_pays_the_full_amount(): void
    {
        $driver = User::factory()->create();
        $company = Company::query()->create(['name' => 'Eigen site', 'is_active' => true]);
        $this->enableDriverPayout($driver, $company, 'org_ownsite');

        $ride = $this->completedRide($driver, $company, [
            'source' => RideRequest::SOURCE_BOOKING,
            'final_price' => 100,
        ]);

        app(RideSettlementEligibilityService::class)->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertCount(1, $this->transfers->calls);
        $this->assertEquals(100.0, $this->transfers->calls[0]['amount']);

        $settlement = RidePlatformSettlement::query()->where('ride_request_id', $ride->id)->first();
        $this->assertSame(RidePlatformSettlement::MODEL_OWN_SITE, $settlement->model);
        $this->assertEquals(0.0, (float) $settlement->nexa_fee_amount);
        $this->assertEquals(100.0, (float) $settlement->net_amount);
        $this->assertStringContainsString('100,00', (string) data_get($settlement->meta, 'driver_message'));
    }

    #[Test]
    public function missing_payout_account_does_not_mark_paid_or_message(): void
    {
        $driver = User::factory()->create();
        $company = Company::query()->create(['name' => 'Zonder rekening', 'is_active' => true]);

        $ride = $this->completedRide($driver, $company, [
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'final_price' => 80,
        ]);

        $result = app(RideSettlementEligibilityService::class)->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertNotSame(RideRequest::SETTLEMENT_SETTLED, $result->settlement_status);
        $this->assertSame([], $this->transfers->calls);

        $settlement = RidePlatformSettlement::query()->where('ride_request_id', $ride->id)->first();
        $this->assertNotNull($settlement);
        $this->assertNotSame(RidePlatformSettlement::STATUS_PAID_OUT, $settlement->status);
        $this->assertNull($settlement->paid_out_at);
        $this->assertFalse(filled(data_get($settlement->meta, 'driver_message')));
        $this->assertSame([], app(DriverRidePayoutService::class)->noticesForDriver($driver->id));
    }

    #[Test]
    public function unpaid_ride_is_not_transferred(): void
    {
        $driver = User::factory()->create();
        $company = Company::query()->create(['name' => 'Onbetaald', 'is_active' => true]);
        $this->enableDriverPayout($driver, $company, 'org_unpaid');

        $ride = $this->completedRide($driver, $company, [
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'final_price' => 50,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
        ]);

        $result = app(RideSettlementEligibilityService::class)->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_REJECTED, $result->settlement_status);
        $this->assertSame([], $this->transfers->calls);
        $this->assertSame([], app(DriverRidePayoutService::class)->noticesForDriver($driver->id));
    }

    #[Test]
    public function mollie_client_posts_balance_transfer_and_fails_closed_without_token(): void
    {
        Http::fake([
            'https://api.mollie.com/v2/connect/balance-transfers' => Http::response([
                'id' => 'cbtr_test_accepted',
                'status' => 'created',
            ], 201),
        ]);
        config([
            'nexa_payout.connect_access_token' => 'access_test_token',
            'nexa_payout.connect_source_organization_id' => 'org_platform1',
            'nexa_payout.connect_testmode' => true,
        ]);

        $identity = new PayoutIdentity([
            'provider_account_id' => 'org_driver1',
            'provider_organization_id' => 'org_driver1',
        ]);
        $client = new MollieConnectBalanceTransferClient;
        $result = $client->transfer($identity, 90, 'EUR', 'ride-5-driver');

        $this->assertTrue($result['ok']);
        $this->assertSame('cbtr_test_accepted', $result['transfer_id']);
        $this->assertArrayNotHasKey('stub', $result);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api.mollie.com/v2/connect/balance-transfers'
                && ($data['amount']['value'] ?? null) === '90.00'
                && ($data['destination']['id'] ?? null) === 'org_driver1'
                && ($data['source']['id'] ?? null) === 'org_platform1'
                && ($data['testmode'] ?? null) === true
                && str_starts_with((string) $request->header('Authorization')[0], 'Bearer ');
        });

        Http::fake();
        config([
            'nexa_payout.connect_access_token' => '',
            'nexa_payout.connect_source_organization_id' => '',
        ]);
        $denied = $client->transfer($identity, 90, 'EUR', 'ride-5-driver');
        $this->assertFalse($denied['ok']);
        $this->assertArrayNotHasKey('transfer_id', $denied);
        $this->assertArrayNotHasKey('stub', $denied);
        Http::assertNothingSent();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function completedRide(User $driver, Company $company, array $overrides): RideRequest
    {
        return RideRequest::on('module_taxi')->create(array_merge([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'distance_meters' => 5000,
            'actual_distance_meters' => 5100,
            'trip_started_at' => now()->subMinutes(20),
            'trip_completed_at' => now(),
            'payment_method' => 'ideal',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ], $overrides));
    }

    private function enableDriverPayout(User $driver, Company $company, string $orgId): void
    {
        PayoutIdentity::query()->create([
            'owner_key' => 'user:'.$driver->id.':mollie',
            'company_id' => $company->id,
            'user_id' => $driver->id,
            'settlement_party' => PayoutIdentity::PARTY_INDEPENDENT_DRIVER,
            'provider' => PayoutIdentity::PROVIDER_MOLLIE,
            'provider_account_id' => $orgId,
            'provider_organization_id' => $orgId,
            'capability_status' => PayoutIdentity::STATUS_ENABLED,
            'masked_destination' => '***4242',
            'verified_at' => now(),
            'disabled_at' => null,
        ]);
    }
}

class FakeDriverTransferClient extends MollieConnectBalanceTransferClient
{
    /** @var list<array{amount: float, account: ?string, reference: string}> */
    public array $calls = [];

    public function __construct() {}

    public function transfer(
        \App\Models\PayoutIdentity $identity,
        float $amount,
        string $currency,
        string $reference,
    ): array {
        $this->calls[] = [
            'amount' => $amount,
            'account' => $identity->provider_account_id,
            'reference' => $reference,
        ];

        return ['ok' => true, 'transfer_id' => 'cbtr_test_1'];
    }
}

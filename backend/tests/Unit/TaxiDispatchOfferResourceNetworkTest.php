<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDispatchOfferResourceNetworkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('fulfilling_company_id')->nullable();
            $table->string('status', 32)->default('completed');
            $table->string('source', 32)->nullable();
            $table->string('ride_type', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->string('settlement_status', 32)->nullable();
            $table->timestamp('settlement_hold_until')->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->timestamps();
        });
    }

    #[Test]
    public function ride_summary_exposes_read_only_network_and_settlement_not_risk_flags(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 10,
            'fulfilling_company_id' => 20,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_HOLD,
            'settlement_hold_until' => now()->addDay(),
        ])->save();

        $payload = TaxiDispatchOfferResource::rideSummary($ride->fresh(), false, false);

        $this->assertTrue($payload['is_network_ride']);
        $this->assertSame('NEXA Network', $payload['network_label']);
        $this->assertSame(RideRequest::SETTLEMENT_HOLD, $payload['settlement_status']);
        $this->assertFalse($payload['settlement_payable']);
        $this->assertArrayNotHasKey('settlement_risk_flags', $payload);
        $this->assertArrayNotHasKey('fulfilling_company_id', $payload);
    }

    #[Test]
    public function fee_breakdown_uses_marketplace_percent_for_network_rides(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10]);

        $owner = Company::query()->create(['name' => 'Taxi A', 'is_active' => true]);
        $executor = Company::query()->create(['name' => 'Taxi B', 'is_active' => true]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'fulfilling_company_id' => $executor->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'quoted_price' => 50,
        ]);

        $payload = TaxiDispatchOfferResource::rideSummary($ride->fresh(), false, false);
        $fb = $payload['fee_breakdown'];

        $this->assertNotNull($fb);
        $this->assertTrue($fb['is_network']);
        $this->assertSame(50.0, $fb['customer_pays']);
        $this->assertSame('Taxi A', $fb['owner_name']);
        $this->assertSame('Taxi B', $fb['executor_name']);
        $this->assertSame(5.0, $fb['nexa_fee']);
        $this->assertSame(10, $fb['nexa_fee_percent']);
        $this->assertSame(45.0, $fb['driver_share']);
    }

    #[Test]
    public function fee_breakdown_for_marketplace_uses_nexa_suite_as_owner_when_unclaimed(): void
    {
        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10]);
        $claimer = Company::query()->create(['name' => 'Taxi Claim', 'is_active' => true]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'status' => RideRequest::STATUS_OFFERED,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'quoted_price' => 50,
        ]);

        $fb = TaxiDispatchOfferResource::feeBreakdownForDriver($ride, $claimer->id);

        $this->assertNotNull($fb);
        $this->assertTrue($fb['is_marketplace']);
        $this->assertSame('NEXA Suite', $fb['owner_name']);
        $this->assertSame('Taxi Claim', $fb['executor_name']);
        $this->assertSame(5.0, $fb['nexa_fee']);
    }
}

<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\MarketplacePickupConfirmationService;
use App\Modules\NexaTaxi\Services\RideSettlementEligibilityService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RideSettlementEligibilityServiceTest extends TestCase
{
    private RideSettlementEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        config(['taxi-dispatch.settlement_hold_hours' => 24]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('completed');
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
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
        $this->service = app(RideSettlementEligibilityService::class);
    }

    #[Test]
    public function clean_completion_goes_on_hold_not_eligible(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'distance_meters' => 5000,
            'actual_distance_meters' => 5100,
            'trip_started_at' => now()->subMinutes(20),
            'trip_completed_at' => now(),
            'payment_method' => 'ideal',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_HOLD, $result->settlement_status);
        $this->assertNotNull($result->settlement_hold_until);
        $this->assertNull($result->settlement_eligible_at);
        $this->assertFalse($this->service->isPayable($result));
    }

    #[Test]
    public function cash_and_too_soon_require_manual_review(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'trip_started_at' => now()->subSeconds(10),
            'trip_completed_at' => now(),
            'payment_method' => 'cash',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $result->settlement_status);
        $this->assertContains('cash_requires_verification', $result->settlement_risk_flags);
        $this->assertContains('completed_too_soon', $result->settlement_risk_flags);
        $this->assertFalse($result->isSettlementPayable());
    }

    #[Test]
    public function unpaid_pending_payment_is_rejected(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'trip_started_at' => now()->subMinutes(15),
            'trip_completed_at' => now(),
            'payment_method' => 'ideal',
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_REJECTED, $result->settlement_status);
        $this->assertContains('payment_incomplete', $result->settlement_risk_flags);
    }

    #[Test]
    public function missing_gps_is_hold_flag_not_hard_fail(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'distance_meters' => 8000,
            'actual_distance_meters' => 0,
            'trip_started_at' => now()->subMinutes(25),
            'trip_completed_at' => now(),
            'payment_method' => 'ideal',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_HOLD, $result->settlement_status);
        $this->assertContains('missing_gps_track', $result->settlement_risk_flags);
    }

    #[Test]
    public function release_due_holds_promotes_to_eligible(): void
    {
        $due = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $due->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_HOLD,
            'settlement_hold_until' => now()->subMinute(),
        ])->save();

        $notDue = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $notDue->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_HOLD,
            'settlement_hold_until' => now()->addHour(),
        ])->save();

        $review = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $review->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_REVIEW,
            'settlement_hold_until' => now()->subMinute(),
        ])->save();

        $released = $this->service->releaseDueHolds('module_taxi');

        $this->assertSame(1, $released);
        $this->assertSame(RideRequest::SETTLEMENT_ELIGIBLE, $due->fresh()->settlement_status);
        $this->assertSame(RideRequest::SETTLEMENT_HOLD, $notDue->fresh()->settlement_status);
        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $review->fresh()->settlement_status);
        $this->assertTrue($this->service->isPayable($due->fresh()));
    }

    #[Test]
    public function mark_eligible_clears_hold_for_review_rides(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_REVIEW,
            'settlement_risk_flags' => ['cash_requires_verification'],
            'settlement_hold_until' => now()->addDays(2),
        ])->save();

        $result = $this->service->markEligible('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_ELIGIBLE, $result->settlement_status);
        $this->assertNull($result->settlement_hold_until);
        $this->assertContains('manual_release', $result->settlement_risk_flags);
        $this->assertTrue($result->isSettlementPayable());
    }

    #[Test]
    public function customer_confirmation_is_signal_only_never_eligible(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'trip_completed_at' => now()->subHour(),
        ]);
        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_HOLD,
            'settlement_hold_until' => now()->addDay(),
        ])->save();

        $result = $this->service->recordCustomerConfirmation('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_HOLD, $result->settlement_status);
        $this->assertContains('customer_confirmed_completion', $result->settlement_risk_flags);
        $this->assertFalse($result->isSettlementPayable());
    }

    #[Test]
    public function customer_problem_forces_review_not_eligible(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'trip_completed_at' => now()->subHour(),
        ]);
        $ride->forceFill([
            'settlement_status' => RideRequest::SETTLEMENT_ELIGIBLE,
            'settlement_eligible_at' => now()->subMinute(),
        ])->save();

        $result = $this->service->recordCustomerProblem('module_taxi', $ride, 'Verkeerde drop-off');

        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $result->settlement_status);
        $this->assertNull($result->settlement_eligible_at);
        $this->assertContains('customer_reported_problem', $result->settlement_risk_flags);
        $this->assertFalse($result->isSettlementPayable());
    }

    #[Test]
    public function confirmed_pickup_releases_immediately_even_if_start_was_far(): void
    {
        $ride = $this->cleanCompletedRide([
            'pickup_confirmation' => ['status' => 'confirmed'],
            'pickup_location_check' => ['result' => 'away'],
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_ELIGIBLE, $result->settlement_status);
        $this->assertContains('customer_confirmed_pickup', $result->settlement_risk_flags);
        $this->assertTrue($result->isSettlementPayable());
    }

    #[Test]
    public function denied_pickup_stays_in_review(): void
    {
        $ride = $this->cleanCompletedRide([
            'pickup_confirmation' => ['status' => 'denied'],
            'pickup_location_check' => ['result' => 'near'],
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $result->settlement_status);
        $this->assertContains('customer_denied_pickup', $result->settlement_risk_flags);
        $this->assertFalse($result->isSettlementPayable());
    }

    #[Test]
    public function unknown_or_far_start_without_answer_requires_review(): void
    {
        $unknown = $this->service->evaluateAfterCompletion('module_taxi', $this->cleanCompletedRide([
            'pickup_location_check' => ['result' => 'unknown'],
        ]));
        $away = $this->service->evaluateAfterCompletion('module_taxi', $this->cleanCompletedRide([
            'pickup_location_check' => ['result' => 'away'],
        ]));

        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $unknown->settlement_status);
        $this->assertContains('location_unknown_at_start', $unknown->settlement_risk_flags);
        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $away->settlement_status);
        $this->assertContains('started_away_from_pickup', $away->settlement_risk_flags);
    }

    #[Test]
    public function completing_far_from_dropoff_stays_in_review_after_yes(): void
    {
        $ride = $this->cleanCompletedRide([
            'pickup_confirmation' => ['status' => 'confirmed'],
            'pickup_location_check' => ['result' => 'near'],
            'dropoff_location_check' => ['away' => true],
        ]);

        $result = $this->service->evaluateAfterCompletion('module_taxi', $ride);

        $this->assertSame(RideRequest::SETTLEMENT_REVIEW, $result->settlement_status);
        $this->assertContains('completed_away_from_dropoff', $result->settlement_risk_flags);
        $this->assertFalse($result->isSettlementPayable());
    }

    #[Test]
    public function gps_classifier_uses_radius_and_freshness(): void
    {
        $pickup = app(MarketplacePickupConfirmationService::class);

        $this->assertSame('near', $pickup->classify(true, 52.0, 6.0, 52.0, 6.0, 250)['result']);
        $this->assertSame('unknown', $pickup->classify(false, 52.0, 6.0, 52.0, 6.0, 250)['result']);
        $this->assertSame('away', $pickup->classify(true, 52.0, 6.0, 53.0, 6.0, 250)['result']);
    }

    #[Test]
    public function ensure_pin_creates_four_digit_code_for_marketplace_ride(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'booking_payload' => ['channel' => RideRequest::SOURCE_NEXA_SUITE],
        ]);

        $pickup = app(MarketplacePickupConfirmationService::class);
        $this->assertTrue($pickup->appliesTo($ride));

        $withPin = $pickup->ensurePin('module_taxi', $ride);
        $pin = (string) data_get($withPin->booking_payload, 'pickup_confirmation.pin', '');

        $this->assertMatchesRegularExpression('/^\d{4}$/', $pin);
        $this->assertSame('pending', data_get($withPin->booking_payload, 'pickup_confirmation.status'));
        $this->assertSame($pin, $pickup->customerPayload($withPin)['pin'] ?? null);
        $this->assertTrue($pickup->driverPayload($withPin)['requires_pin'] ?? false);
        $this->assertArrayNotHasKey('pin', $pickup->driverPayload($withPin) ?? []);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function cleanCompletedRide(array $payload): RideRequest
    {
        return RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'distance_meters' => 5000,
            'actual_distance_meters' => 5100,
            'trip_started_at' => now()->subMinutes(20),
            'trip_completed_at' => now(),
            'payment_method' => 'ideal',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'booking_payload' => $payload,
        ]);
    }
}

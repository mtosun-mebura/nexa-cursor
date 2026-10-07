<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Modules\NexaTaxi\Services\TaxiPickupProposalService;
use App\Services\WhatsAppBusinessService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RideClaimServiceTest extends TestCase
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
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->json('booking_payload')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->text('pickup_proposal_customer_remark')->nullable();
            $table->timestamp('pickup_proposal_sent_at')->nullable();
            $table->timestamp('pickup_proposal_responded_at')->nullable();
            $table->string('pickup_proposal_whatsapp_wamid', 191)->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_note')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('driver_id');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('wave')->default(1);
            $table->timestamp('offered_at');
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedSmallInteger('sequence');
            $table->string('stop_type', 24)->index();
            $table->unsignedBigInteger('transport_passenger_id')->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('planned_at')->nullable();
            $table->string('status', 24)->default('planned')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        \App\Modules\NexaTaxi\Support\TaxiRideTrackSchema::ensure('module_taxi');
    }

    public function test_accept_assigns_driver_atomically(): void
    {
        $driver = User::factory()->create();
        $other = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $other->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);

        $otherOffer = RideDispatchOffer::on('module_taxi')
            ->where('driver_id', $other->id)
            ->first();
        $this->assertSame(RideDispatchOffer::STATUS_SUPERSEDED, $otherOffer->status);
    }

    public function test_accept_rejects_overlapping_planned_ride(): void
    {
        $driver = User::factory()->create();
        $pickup = now()->addHours(2);

        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Gepland A',
            'dropoff_address' => 'Gepland B',
            'pickup_at' => $pickup->copy(),
            'duration_seconds' => 30 * 60,
            'customer_name' => 'Bestaand',
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'Nieuw A',
            'dropoff_address' => 'Nieuw B',
            'pickup_at' => $pickup->copy()->addMinutes(10),
            'duration_seconds' => 30 * 60,
            'customer_name' => 'Nieuw',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $this->expectException(ValidationException::class);

        app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);
    }

    public function test_accept_allows_non_overlapping_planned_ride(): void
    {
        $driver = User::factory()->create();
        $pickup = now()->addHours(2);

        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'Gepland A',
            'dropoff_address' => 'Gepland B',
            'pickup_at' => $pickup->copy(),
            'duration_seconds' => 30 * 60,
            'customer_name' => 'Bestaand',
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'Nieuw A',
            'dropoff_address' => 'Nieuw B',
            'pickup_at' => $pickup->copy()->addHours(2),
            'duration_seconds' => 30 * 60,
            'customer_name' => 'Nieuw',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $result = app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);
    }

    public function test_start_moves_accepted_ride_to_assigned(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);
        $started = $claim->startRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $started->status);
        $this->assertNotNull($started->trip_started_at);
    }

    public function test_start_blocks_overdue_own_customer_ride_without_accepted_proposal(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => 'website',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->subHours(2),
            'customer_name' => 'Eigen klant',
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Het ophaalmoment is verlopen');

        app(RideClaimService::class)->startRide('module_taxi', $driver, $ride->id);
    }

    public function test_start_allows_overdue_marketplace_ride_without_new_pickup(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->subHours(2),
            'customer_name' => 'Marketplace',
        ]);

        $started = app(RideClaimService::class)->startRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $started->status);
    }

    public function test_start_allows_overdue_ride_with_marketplace_payload_only(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'source' => 'website',
            'booking_payload' => ['marketplace' => ['candidate_company_ids' => [1]]],
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->subHours(2),
            'customer_name' => 'Marketplace payload',
        ]);

        $started = app(RideClaimService::class)->startRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_ASSIGNED, $started->status);
    }

    public function test_complete_marks_ride_completed_for_assigned_driver(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);
        $completed = $claim->completeRide('module_taxi', $driver, $ride->id);

        $this->assertSame(RideRequest::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->settlement_status);
        $this->assertNotSame(RideRequest::SETTLEMENT_ELIGIBLE, $completed->settlement_status);
        $this->assertFalse($completed->isSettlementPayable());
    }

    public function test_complete_rejects_accepted_ride_without_start(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);

        $this->expectException(ValidationException::class);
        $claim->completeRide('module_taxi', $driver, $ride->id);
    }

    public function test_release_clears_driver_and_redispatches(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $claim = app(RideClaimService::class);
        $released = $claim->releaseAcceptedRide('module_taxi', $driver, $ride->id);

        $this->assertNull($released->driver_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $released->status);

        $driverOffer = RideDispatchOffer::on('module_taxi')
            ->where('ride_request_id', $ride->id)
            ->where('driver_id', $driver->id)
            ->first();
        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $driverOffer->status);
    }

    public function test_release_blocked_for_contract_ride(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'transport_contract_id' => 99,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            'payment_method' => 'contract',
            'source' => 'contract',
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test',
        ]);

        $claim = app(RideClaimService::class);

        $this->expectException(ValidationException::class);
        $claim->releaseAcceptedRide('module_taxi', $driver, $ride->id);
    }

    public function test_decline_pending_offer_marks_declined(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $declined = $claim->declineOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $declined->fresh()->status);
    }

    public function test_decline_expired_offer_marks_declined(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_EXPIRED,
            'offered_at' => now()->subMinutes(10),
            'expires_at' => now()->subMinute(),
            'responded_at' => now()->subMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $declined = $claim->declineOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $declined->fresh()->status);
    }

    public function test_accept_declined_offer_assigns_driver(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            // Naive Amsterdam wall-clock (niet app-TZ converteren).
            'pickup_at' => now('Europe/Amsterdam')->addHours(2)->format('Y-m-d H:i:s'),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subMinutes(5),
            'expires_at' => now()->subMinute(),
            'responded_at' => now()->subMinute(),
        ]);

        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertSame($driver->id, (int) $result['ride']->driver_id);
        $this->assertSame(RideDispatchOffer::STATUS_ACCEPTED, $result['offer']->fresh()->status);
    }

    public function test_accept_declined_overdue_ride_with_new_pickup_at(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('Europe/Amsterdam')->subHours(2)->format('Y-m-d H:i:s'),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $newPickup = now('Europe/Amsterdam')->addDay()->startOfMinute();
        $claim = app(RideClaimService::class);
        $result = $claim->acceptOffer(
            'module_taxi',
            $driver,
            $offer->id,
            $newPickup->toIso8601String()
        );

        $this->assertSame(RideRequest::STATUS_ACCEPTED, $result['ride']->status);
        $this->assertTrue(! empty($result['pickup_proposed']));
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $result['ride']->pickup_proposal_status);
        $this->assertNotNull($result['ride']->pickup_proposal_at);
        $this->assertSame(
            1,
            RideDispatchOffer::on('module_taxi')->awaitingCustomerApprovalForDriver($driver->id)->count()
        );
        $this->assertSame(
            0,
            RideDispatchOffer::on('module_taxi')->customerDeclinedProposalForDriver($driver->id)->count()
        );
        // Oude pickup_at blijft tot de klant via WhatsApp (rit_ophaal_voorstel) accepteert.
        $this->assertTrue($result['ride']->pickup_at->format('Y-m-d H:i:s') < $newPickup->format('Y-m-d H:i:s'));
    }

    public function test_accept_declined_overdue_ride_requires_new_pickup_at(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('Europe/Amsterdam')->subHours(2)->format('Y-m-d H:i:s'),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $claim = app(RideClaimService::class);

        $this->expectException(ValidationException::class);
        $claim->acceptOffer('module_taxi', $driver, $offer->id, null);
    }

    public function test_customer_accepts_pickup_proposal_makes_ride_available_with_new_time(): void
    {
        $driver = User::factory()->create();
        $oldPickup = now('UTC')->subHours(2)->startOfMinute();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => $oldPickup->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
        ]);

        $updated = app(TaxiPickupProposalService::class)->acceptProposal('module_taxi', $ride);

        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $updated->pickup_proposal_status);
        $this->assertSame($proposed->format('Y-m-d H:i:s'), $updated->pickup_at->format('Y-m-d H:i:s'));
        $this->assertSame($driver->id, (int) $updated->driver_id);
        $this->assertSame(RideRequest::STATUS_ACCEPTED, $updated->status);
        $this->assertFalse($updated->hasOpenPickupProposal());
    }

    public function test_customer_declines_pickup_proposal_stays_assigned_until_archived(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $declined = app(TaxiPickupProposalService::class)->declineProposal('module_taxi', $ride);

        $this->assertSame(RideRequest::PICKUP_PROPOSAL_DECLINED, $declined->pickup_proposal_status);
        $this->assertSame($driver->id, (int) $declined->driver_id);
        $this->assertSame(RideRequest::STATUS_ACCEPTED, $declined->status);
        $this->assertTrue($declined->hasDeclinedPickupProposal());

        $awaiting = RideDispatchOffer::on('module_taxi')->awaitingCustomerApprovalForDriver($driver->id)->count();
        $customerDeclined = RideDispatchOffer::on('module_taxi')->customerDeclinedProposalForDriver($driver->id)->count();
        $this->assertSame(0, $awaiting);
        $this->assertSame(1, $customerDeclined);

        $archived = app(RideClaimService::class)->archiveCustomerDeclinedPickupProposal(
            'module_taxi',
            $driver,
            $offer->id
        );

        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $archived->status);
        $this->assertNotNull($archived->archived_at);

        $freshRide = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertNull($freshRide->driver_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $freshRide->status);
        $this->assertNull($freshRide->pickup_proposal_status);
        $this->assertSame(0, RideDispatchOffer::on('module_taxi')->customerDeclinedProposalForDriver($driver->id)->count());
    }

    public function test_whatsapp_text_accepteren_matches_06_and_31_phone(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'text',
            'text' => ['body' => 'Accepteren'],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $fresh->pickup_proposal_status);
        $this->assertSame($proposed->format('Y-m-d H:i:s'), $fresh->pickup_at->format('Y-m-d H:i:s'));
        $this->assertNull($fresh->pickup_proposal_customer_remark);
    }

    public function test_whatsapp_button_payload_accepts_proposal(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '+31612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'button',
            'button' => [
                'payload' => TaxiPickupProposalService::BUTTON_ACCEPT,
                'text' => 'Accepteren',
            ],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $fresh->pickup_proposal_status);
    }

    public function test_whatsapp_button_uses_knoptekst_when_payload_is_index(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'button',
            'button' => [
                'payload' => 0,
                'text' => 'Accepteren',
            ],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $fresh->pickup_proposal_status);
        $this->assertSame($proposed->format('Y-m-d H:i:s'), $fresh->pickup_at->format('Y-m-d H:i:s'));
    }

    public function test_whatsapp_text_weigeren_declines_proposal(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'text',
            'text' => ['body' => 'Weigeren'],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_DECLINED, $fresh->pickup_proposal_status);
    }

    public function test_whatsapp_free_text_remark_does_not_accept(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'text',
            'text' => ['body' => 'Graag 10 minuten later'],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $fresh->pickup_proposal_status);
        $this->assertSame('Graag 10 minuten later', $fresh->pickup_proposal_customer_remark);
    }

    public function test_archive_pending_pickup_proposal_hides_ride_until_customer_replies(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        $archived = app(RideClaimService::class)->archivePendingPickupProposal(
            'module_taxi',
            $driver,
            $offer->id
        );

        $this->assertNotNull($archived->archived_at);
        $this->assertSame(RideDispatchOffer::STATUS_EXPIRED, $archived->status);
        $this->assertSame(0, RideDispatchOffer::on('module_taxi')->awaitingCustomerApprovalForDriver($driver->id)->count());

        $freshRide = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertNull($freshRide->driver_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $freshRide->status);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $freshRide->pickup_proposal_status);
        $this->assertSame($proposed->format('Y-m-d H:i:s'), $freshRide->pickup_proposal_at->format('Y-m-d H:i:s'));
    }

    public function test_whatsapp_accept_after_archive_returns_as_new_offer(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        app(RideClaimService::class)->archivePendingPickupProposal('module_taxi', $driver, $offer->id);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'text',
            'text' => ['body' => 'Accepteren'],
        ]);

        $this->assertTrue($handled);
        $freshRide = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertNull($freshRide->driver_id);
        $this->assertSame($proposed->format('Y-m-d H:i:s'), $freshRide->pickup_at->format('Y-m-d H:i:s'));
        $this->assertNull($freshRide->pickup_proposal_status);
        $this->assertSame(RideRequest::STATUS_OFFERED, $freshRide->status);

        $freshOffer = RideDispatchOffer::on('module_taxi')->find($offer->id);
        $this->assertSame(RideDispatchOffer::STATUS_PENDING, $freshOffer->status);
        $this->assertNull($freshOffer->archived_at);
    }

    public function test_whatsapp_decline_after_archive_returns_to_declined_inbox(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDays(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now()->subHours(3),
            'expires_at' => now()->subHours(2),
            'responded_at' => now()->subHour(),
        ]);

        app(RideClaimService::class)->archivePendingPickupProposal('module_taxi', $driver, $offer->id);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'text',
            'text' => ['body' => 'Weigeren'],
        ]);

        $this->assertTrue($handled);
        $freshRide = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertNull($freshRide->driver_id);
        $this->assertNull($freshRide->pickup_proposal_status);

        $freshOffer = RideDispatchOffer::on('module_taxi')->find($offer->id);
        $this->assertSame(RideDispatchOffer::STATUS_DECLINED, $freshOffer->status);
        $this->assertNull($freshOffer->archived_at);
    }

    public function test_whatsapp_context_id_matches_the_specific_pending_proposal(): void
    {
        $driver = User::factory()->create();
        $proposedOlder = now('UTC')->addDay()->startOfMinute();
        $proposedNewer = now('UTC')->addDays(2)->startOfMinute();

        $older = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposedOlder->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now()->subMinutes(5),
            'pickup_proposal_whatsapp_wamid' => 'wamid.OLDER',
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $newer = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'C',
            'dropoff_address' => 'D',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposedNewer->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'pickup_proposal_whatsapp_wamid' => 'wamid.NEWER',
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'button',
            'button' => [
                'text' => 'Accepteren',
            ],
            'context' => [
                'from' => '15550000000',
                'id' => 'wamid.OLDER',
            ],
        ]);

        $this->assertTrue($handled);
        $freshOlder = RideRequest::on('module_taxi')->find($older->id);
        $freshNewer = RideRequest::on('module_taxi')->find($newer->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $freshOlder->pickup_proposal_status);
        $this->assertSame($proposedOlder->format('Y-m-d H:i:s'), $freshOlder->pickup_at->format('Y-m-d H:i:s'));
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $freshNewer->pickup_proposal_status);
    }

    public function test_whatsapp_unknown_context_id_falls_back_to_phone(): void
    {
        $driver = User::factory()->create();
        $proposed = now('UTC')->addDay()->startOfMinute();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->subHours(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => $proposed->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'pickup_proposal_whatsapp_wamid' => 'wamid.KNOWN',
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'button',
            'button' => [
                'text' => 'Accepteren',
            ],
            'context' => [
                'id' => 'wamid.UNKNOWN',
            ],
        ]);

        $this->assertTrue($handled);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_ACCEPTED, $fresh->pickup_proposal_status);
    }

    public function test_whatsapp_context_id_of_closed_proposal_does_not_hit_another_pending_ride(): void
    {
        $driver = User::factory()->create();

        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_ACCEPTED,
            'pickup_proposal_sent_at' => now()->subHour(),
            'pickup_proposal_responded_at' => now()->subMinutes(10),
            'pickup_proposal_whatsapp_wamid' => 'wamid.CLOSED',
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $otherPending = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'C',
            'dropoff_address' => 'D',
            'pickup_at' => now('UTC')->subHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDays(2)->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'pickup_proposal_whatsapp_wamid' => 'wamid.OTHER',
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $handled = app(TaxiPickupProposalService::class)->handleInboundCustomerMessage('module_taxi', [
            'from' => '31612345678',
            'type' => 'button',
            'button' => [
                'text' => 'Accepteren',
            ],
            'context' => [
                'id' => 'wamid.CLOSED',
            ],
        ]);

        $this->assertFalse($handled);
        $fresh = RideRequest::on('module_taxi')->find($otherPending->id);
        $this->assertSame(RideRequest::PICKUP_PROPOSAL_PENDING, $fresh->pickup_proposal_status);
    }

    public function test_send_proposal_whatsapp_stores_wamid(): void
    {
        $this->mock(WhatsAppBusinessService::class, function ($mock): void {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('sendTemplate')->once()->andReturn([
                'ok' => true,
                'wamid' => 'wamid.HBgNSTORED',
            ]);
        });

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now('UTC')->addHour()->format('Y-m-d H:i:s'),
            'pickup_proposal_at' => now('UTC')->addDay()->format('Y-m-d H:i:s'),
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_PENDING,
            'pickup_proposal_sent_at' => now(),
            'customer_name' => 'Test',
            'customer_phone' => '0612345678',
        ]);

        $ok = app(TaxiPickupProposalService::class)->sendProposalWhatsapp('module_taxi', $ride);

        $this->assertTrue($ok);
        $fresh = RideRequest::on('module_taxi')->find($ride->id);
        $this->assertSame('wamid.HBgNSTORED', $fresh->pickup_proposal_whatsapp_wamid);
    }

    public function test_marketplace_accept_claims_company_id_from_offer(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Market',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 42,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $result = app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(42, (int) $result['ride']->company_id);
        $this->assertNull($result['ride']->fulfilling_company_id);
    }

    public function test_network_accept_preserves_owner_and_sets_fulfiller(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 10,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Network',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 20,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $result = app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(10, (int) $result['ride']->company_id);
        $this->assertSame(20, (int) $result['ride']->fulfilling_company_id);
        $this->assertTrue($result['ride']->isNetworkFulfilled());
    }

    public function test_tenant_accept_does_not_set_fulfiller_when_same_company(): void
    {
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 7,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Tenant',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 7,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $result = app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);

        $this->assertSame(7, (int) $result['ride']->company_id);
        $this->assertNull($result['ride']->fulfilling_company_id);
        $this->assertFalse($result['ride']->isNetworkFulfilled());
    }

    public function test_accept_requires_vehicle_when_availability_column_exists(): void
    {
        $this->ensureDriverAvailabilitySchema();
        $driver = User::factory()->create();

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $this->expectException(ValidationException::class);
        app(RideClaimService::class)->acceptOffer('module_taxi', $driver, $offer->id);
    }

    public function test_accept_stores_selected_vehicle_on_ride(): void
    {
        $this->ensureDriverAvailabilitySchema();
        $this->ensureVehiclesSchema();
        $driver = User::factory()->create();

        Schema::connection('module_taxi')->getConnection()->table('vehicles')->insert([
            'id' => 55,
            'company_id' => 1,
            'name' => 'Touran',
            'license_plate' => 'X-123-YZ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        $offer = RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_PENDING,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
        ]);

        $result = app(RideClaimService::class)->acceptOffer(
            'module_taxi',
            $driver,
            $offer->id,
            null,
            55
        );

        $this->assertSame(55, (int) $result['ride']->vehicle_id);
        $this->assertDatabaseHas('driver_availability', [
            'driver_id' => $driver->id,
            'vehicle_id' => 55,
        ], 'module_taxi');
    }

    public function test_hand_over_to_network_clears_assignment_and_offers_to_partners(): void
    {
        $this->ensureDriverAvailabilitySchema();
        $driver = User::factory()->create();
        $owner = \App\Models\Company::query()->create(['name' => 'Owner Taxi', 'is_active' => true]);
        $partner = \App\Models\Company::query()->create(['name' => 'Partner Taxi', 'is_active' => true]);

        $settings = app(\App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService::class);
        $settings->setNetworkMode(
            \App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService::NETWORK_MODE_MANUAL,
            $owner->id
        );
        $settings->setNetworkPartnerCompanyIds([(int) $partner->id], $owner->id);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $owner->id,
            'driver_id' => $driver->id,
            'vehicle_id' => 9,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Klant',
        ]);

        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'company_id' => $owner->id,
            'driver_id' => $driver->id,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'offered_at' => now(),
            'expires_at' => now()->addMinute(),
            'responded_at' => now(),
        ]);

        $released = app(RideClaimService::class)->handOverToNetwork(
            'module_taxi',
            $driver,
            (int) $ride->id,
            (int) $owner->id
        );

        $this->assertNull($released->driver_id);
        $this->assertNull($released->vehicle_id);
        $this->assertSame(RideRequest::STATUS_PENDING_DISPATCH, $released->status);
        $this->assertSame((int) $owner->id, (int) $released->company_id);
    }

    private function ensureDriverAvailabilitySchema(): void
    {
        if (Schema::connection('module_taxi')->hasTable('driver_availability')) {
            return;
        }

        Schema::connection('module_taxi')->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    private function ensureVehiclesSchema(): void
    {
        if (Schema::connection('module_taxi')->hasTable('vehicles')) {
            return;
        }

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}

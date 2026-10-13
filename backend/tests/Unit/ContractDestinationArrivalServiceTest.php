<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Services\ContractDestinationArrivalService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractDestinationArrivalServiceTest extends TestCase
{
    private string $conn = 'sqlite';

    private const DEST_LAT = 52.2215;

    private const DEST_LNG = 6.8936;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-13 08:00:00', 'Europe/Amsterdam'));
        Cache::flush();

        Schema::connection($this->conn)->dropIfExists('ride_stops');
        Schema::connection($this->conn)->dropIfExists('ride_requests');

        Schema::connection($this->conn)->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('assigned');
            $table->string('ride_type')->default('contract_group');
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('transport_passenger_id')->nullable();
            $table->string('stop_type');
            $table->string('status')->default('planned');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('planned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::connection($this->conn)->dropIfExists('ride_stops');
        Schema::connection($this->conn)->dropIfExists('ride_requests');
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_waits_a_few_minutes_at_the_same_destination_before_updating_everyone(): void
    {
        [$a, $b] = $this->twoPassengersAtSchool();
        $service = app(ContractDestinationArrivalService::class);

        $this->assertSame(0, $service->observe($this->conn, 7, [1, 2], self::DEST_LAT, self::DEST_LNG));
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($a->id)->value('status'));

        Carbon::setTestNow(Carbon::parse('2026-10-13 08:02:00', 'Europe/Amsterdam'));
        $this->assertSame(0, $service->observe($this->conn, 7, [1, 2], self::DEST_LAT, self::DEST_LNG));

        Carbon::setTestNow(Carbon::parse('2026-10-13 08:03:00', 'Europe/Amsterdam'));
        $this->assertSame(2, $service->observe($this->conn, 7, [1, 2], self::DEST_LAT + 0.0004, self::DEST_LNG));
        $this->assertSame('completed', RideStop::on($this->conn)->whereKey($a->id)->value('status'));
        $this->assertSame('completed', RideStop::on($this->conn)->whereKey($b->id)->value('status'));
    }

    #[Test]
    public function it_restarts_the_wait_when_the_destination_changes(): void
    {
        [$a] = $this->twoPassengersAtSchool();
        $other = $this->makeStop($a->ride_request_id, 'destination', 3, 51.4500, 5.8000);
        $service = app(ContractDestinationArrivalService::class);

        $service->observe($this->conn, 7, [1, 2, 3], self::DEST_LAT, self::DEST_LNG);
        Carbon::setTestNow(Carbon::parse('2026-10-13 08:04:00', 'Europe/Amsterdam'));
        $this->assertSame(0, $service->observe($this->conn, 7, [1, 2, 3], 51.4500, 5.8000));
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($other->id)->value('status'));
    }

    #[Test]
    public function manual_reach_at_the_destination_updates_that_group_and_leaves_skipped(): void
    {
        [$a, $b] = $this->twoPassengersAtSchool();
        $skippedPickup = $this->makeStop($a->ride_request_id, 'pickup', 3, 52.2, 6.8, 'skipped');
        $skippedDest = $this->makeStop($a->ride_request_id, 'destination', 3, self::DEST_LAT, self::DEST_LNG);

        $result = app(ContractDestinationArrivalService::class)->markAll(
            $this->conn,
            [1, 2, 3],
            self::DEST_LAT,
            self::DEST_LNG
        );

        $this->assertSame(2, $result['updated']);
        $this->assertNull($result['reason']);
        $this->assertSame('completed', RideStop::on($this->conn)->whereKey($a->id)->value('status'));
        $this->assertSame('completed', RideStop::on($this->conn)->whereKey($b->id)->value('status'));
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($skippedDest->id)->value('status'));
        $this->assertSame('skipped', RideStop::on($this->conn)->whereKey($skippedPickup->id)->value('status'));
    }

    #[Test]
    public function manual_reach_without_a_location_does_not_update(): void
    {
        [$a] = $this->twoPassengersAtSchool();

        $result = app(ContractDestinationArrivalService::class)->markAll($this->conn, [1, 2]);

        $this->assertSame(0, $result['updated']);
        $this->assertSame('no_location', $result['reason']);
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($a->id)->value('status'));
    }

    #[Test]
    public function manual_reach_too_far_from_the_destination_does_not_update(): void
    {
        [$a] = $this->twoPassengersAtSchool();

        $result = app(ContractDestinationArrivalService::class)->markAll($this->conn, [1, 2], 51.0, 5.0);

        $this->assertSame(0, $result['updated']);
        $this->assertSame('too_far', $result['reason']);
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($a->id)->value('status'));
    }

    #[Test]
    public function manual_reach_with_gps_only_updates_the_destination_where_the_driver_stopped(): void
    {
        [$a] = $this->twoPassengersAtSchool();
        $otherRide = RideRequest::on($this->conn)->create([
            'status' => 'assigned',
            'ride_type' => 'contract_group',
        ]);
        $this->makeStop($otherRide->id, 'pickup', 4, 51.45, 5.80);
        $otherDest = $this->makeStop($otherRide->id, 'destination', 4, 51.4500, 5.8000);

        $result = app(ContractDestinationArrivalService::class)->markAll(
            $this->conn,
            [1, 2, 4],
            self::DEST_LAT,
            self::DEST_LNG
        );

        $this->assertSame(2, $result['updated']);
        $this->assertSame('completed', RideStop::on($this->conn)->whereKey($a->id)->value('status'));
        $this->assertSame('planned', RideStop::on($this->conn)->whereKey($otherDest->id)->value('status'));
    }

    #[Test]
    public function parent_only_sees_a_fresh_driver_position(): void
    {
        $service = app(ContractDestinationArrivalService::class);
        $service->rememberDriverPosition($this->conn, 9, 52.22, 6.89);

        $fresh = $service->freshDriverPosition($this->conn, 9);
        $this->assertSame(52.22, $fresh['lat']);
        $this->assertSame(6.89, $fresh['lng']);

        Carbon::setTestNow(Carbon::parse('2026-10-13 08:04:00', 'Europe/Amsterdam'));
        $this->assertNull($service->freshDriverPosition($this->conn, 9));
    }

    /**
     * @return array{0: RideStop, 1: RideStop}
     */
    private function twoPassengersAtSchool(): array
    {
        $ride = RideRequest::on($this->conn)->create([
            'status' => 'assigned',
            'ride_type' => 'contract_group',
        ]);
        $this->makeStop($ride->id, 'pickup', 1, 52.23, 6.90);
        $this->makeStop($ride->id, 'pickup', 2, 52.24, 6.91);
        $a = $this->makeStop($ride->id, 'destination', 1, self::DEST_LAT, self::DEST_LNG);
        $b = $this->makeStop($ride->id, 'destination', 2, self::DEST_LAT, self::DEST_LNG);

        return [$a, $b];
    }

    private function makeStop(
        int $rideId,
        string $type,
        int $passengerId,
        float $lat,
        float $lng,
        string $status = 'planned'
    ): RideStop {
        return RideStop::on($this->conn)->create([
            'ride_request_id' => $rideId,
            'transport_passenger_id' => $passengerId,
            'stop_type' => $type,
            'status' => $status,
            'lat' => $lat,
            'lng' => $lng,
            'planned_at' => '2026-10-13 08:00:00',
        ]);
    }
}

<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideGpsPoint;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideClaimService;
use App\Modules\NexaTaxi\Services\RideTrackService;
use App\Modules\NexaTaxi\Services\TaxiGpsRoadPathService;
use App\Modules\NexaTaxi\Support\TaxiRideTrackSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiRideTrackServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection($this->conn)->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('transport_contract_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('ride_type', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('source', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->string('customer_name');
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('stop_type', 24)->index();
            $table->string('status', 24)->default('planned')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        TaxiRideTrackSchema::ensure($this->conn);

        Cache::flush();
    }

    #[Test]
    public function encode_and_decode_polyline_roundtrip(): void
    {
        $points = [
            [52.370216, 4.895168],
            [52.379189, 4.900272],
            [52.390112, 4.917334],
        ];
        $service = app(TaxiGpsRoadPathService::class);
        $encoded = $service->encodePolyline($points);
        $this->assertNotSame('', $encoded);

        $decoded = $service->decodePolyline($encoded);
        $this->assertCount(3, $decoded);
        $this->assertEqualsWithDelta($points[0][0], $decoded[0][0], 0.0001);
        $this->assertEqualsWithDelta($points[0][1], $decoded[0][1], 0.0001);
        $this->assertEqualsWithDelta($points[2][0], $decoded[2][0], 0.0001);
        $this->assertEqualsWithDelta($points[2][1], $decoded[2][1], 0.0001);
    }

    #[Test]
    public function append_skips_points_closer_than_twelve_meters(): void
    {
        $driver = User::factory()->create();
        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Centraal',
            'pickup_lat' => 52.3731,
            'pickup_lng' => 4.8926,
            'dropoff_lat' => 52.3791,
            'dropoff_lng' => 4.9003,
            'pickup_at' => now()->subMinutes(10),
            'customer_name' => 'Test',
        ]);

        $tracks = app(RideTrackService::class);
        $tracks->appendPoint($this->conn, 1, (int) $driver->id, 52.37310, 4.89260, (int) $ride->id);
        $tracks->appendPoint($this->conn, 1, (int) $driver->id, 52.37311, 4.89261, (int) $ride->id);
        $tracks->appendPoint($this->conn, 1, (int) $driver->id, 52.37910, 4.90030, (int) $ride->id);

        $this->assertSame(2, RideGpsPoint::on($this->conn)->where('ride_request_id', $ride->id)->count());
    }

    #[Test]
    public function complete_ride_stores_polyline_distance_and_duration(): void
    {
        $driver = User::factory()->create();
        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 1,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Centraal',
            'pickup_lat' => 52.3731,
            'pickup_lng' => 4.8926,
            'dropoff_lat' => 52.3791,
            'dropoff_lng' => 4.9003,
            'distance_meters' => 1200,
            'duration_seconds' => 240,
            'pickup_at' => now()->subMinutes(20),
            'trip_started_at' => now()->subMinutes(12),
            'customer_name' => 'Test',
        ]);

        $completed = app(RideClaimService::class)->completeRide(
            $this->conn,
            $driver,
            (int) $ride->id,
            false,
            [
                ['lat' => 52.3731, 'lng' => 4.8926, 't' => (int) (now()->subMinutes(12)->getTimestamp() * 1000)],
                ['lat' => 52.3760, 'lng' => 4.8960, 't' => (int) (now()->subMinutes(6)->getTimestamp() * 1000)],
                ['lat' => 52.3791, 'lng' => 4.9003, 't' => (int) (now()->getTimestamp() * 1000)],
            ],
        );

        $this->assertSame(RideRequest::STATUS_COMPLETED, $completed->status);
        $this->assertNotNull($completed->track_polyline);
        $this->assertGreaterThan(500, (int) $completed->actual_distance_meters);
        $this->assertGreaterThan(60, (int) $completed->actual_duration_seconds);
        $this->assertNotNull($completed->trip_completed_at);
        $this->assertSame(0, RideGpsPoint::on($this->conn)->where('ride_request_id', $completed->id)->count());

        $payload = app(RideTrackService::class)->mapPayload($this->conn, $completed);
        $this->assertTrue($payload['has_recorded_track']);
        $this->assertGreaterThanOrEqual(2, count($payload['path']));
        $this->assertNotNull($payload['distance_label']);
        $this->assertNotNull($payload['duration_label']);
    }

    #[Test]
    public function completed_tracks_appear_on_gps_map_payload(): void
    {
        $driver = User::factory()->create(['first_name' => 'Ahmed', 'last_name' => 'Hassan']);
        $ride = RideRequest::on($this->conn)->create([
            'company_id' => 7,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'Dam',
            'dropoff_address' => 'Centraal',
            'pickup_lat' => 52.3731,
            'pickup_lng' => 4.8926,
            'dropoff_lat' => 52.3791,
            'dropoff_lng' => 4.9003,
            'track_polyline' => app(TaxiGpsRoadPathService::class)->encodePolyline([
                [52.3731, 4.8926],
                [52.3791, 4.9003],
            ]),
            'actual_distance_meters' => 980,
            'actual_duration_seconds' => 420,
            'trip_started_at' => now()->subMinutes(20),
            'trip_completed_at' => now()->subMinutes(5),
            'pickup_at' => now()->subHour(),
            'customer_name' => 'Klant',
        ]);

        $tracks = app(RideTrackService::class)->completedTracksForMap($this->conn, 7);
        $this->assertCount(1, $tracks);
        $this->assertSame((int) $ride->id, $tracks[0]['id']);
        $this->assertSame('Ahmed Hassan', $tracks[0]['driver_name']);
        $this->assertSame('1,0 km', $tracks[0]['distance_label']);
        $this->assertSame('7 min', $tracks[0]['duration_label']);
        $this->assertGreaterThanOrEqual(2, count($tracks[0]['path']));
    }

    #[Test]
    public function snap_driven_path_follows_roads_instead_of_a_straight_line(): void
    {
        $road = [
            [52.37310, 4.89260],
            [52.37385, 4.89380],
            [52.37490, 4.89540],
            [52.37620, 4.89710],
            [52.37760, 4.89860],
            [52.37870, 4.89970],
            [52.37910, 4.90030],
        ];
        $encoded = app(TaxiGpsRoadPathService::class)->encodePolyline($road);
        Cache::flush();
        Http::fake([
            '*maps/api/directions/json*' => Http::response([
                'status' => 'OK',
                'routes' => [['overview_polyline' => ['points' => $encoded]]],
            ], 200),
            '*snapToRoads*' => Http::response([
                'snappedPoints' => array_map(
                    static fn (array $point) => ['location' => ['latitude' => $point[0], 'longitude' => $point[1]]],
                    $road
                ),
            ], 200),
            '*osrm.org/match*' => Http::response([
                'code' => 'Ok',
                'matchings' => [['geometry' => $encoded]],
            ], 200),
            '*computeRoutes*' => Http::response([
                'routes' => [['polyline' => ['encodedPolyline' => $encoded]]],
            ], 200),
            '*osrm.org/route*' => Http::response([
                'code' => 'Ok',
                'routes' => [['geometry' => $encoded]],
            ], 200),
        ]);

        $path = app(TaxiGpsRoadPathService::class)->snapDrivenPath([
            [52.3731, 4.8926],
            [52.3791, 4.9003],
        ], true);

        $this->assertGreaterThanOrEqual(count($road), count($path));

        $mid = $path[(int) floor(count($path) / 2)];
        $straightLng = 4.8926 + (4.9003 - 4.8926) * (($mid[0] - 52.3731) / (52.3791 - 52.3731));
        $this->assertGreaterThan(0.0003, abs($mid[1] - $straightLng));
    }

    #[Test]
    public function snap_driven_path_does_not_invent_a_straight_line_when_routing_fails(): void
    {
        Cache::flush();
        Http::fake([
            '*' => Http::response(['status' => 'REQUEST_DENIED'], 200),
        ]);

        $path = app(TaxiGpsRoadPathService::class)->snapDrivenPath([
            [52.2217, 6.8897],
            [52.2653, 6.7930],
        ], true);

        $this->assertCount(2, $path);
    }

    #[Test]
    public function snap_live_positions_uses_nearby_road_and_keeps_far_points(): void
    {
        Cache::flush();
        $this->mock(\App\Services\EnvService::class, function ($mock) {
            $mock->shouldReceive('getGoogleMapsApiKey')->andReturn('test-key');
        });
        Http::fake([
            '*nearestRoads*' => Http::response([
                'snappedPoints' => [
                    [
                        'location' => ['latitude' => 52.22180, 'longitude' => 6.88990],
                        'originalIndex' => 0,
                    ],
                    [
                        'location' => ['latitude' => 52.30000, 'longitude' => 6.95000],
                        'originalIndex' => 1,
                    ],
                ],
            ], 200),
        ]);

        $path = app(TaxiGpsRoadPathService::class)->snapLivePositions([
            [52.22172, 6.88982],
            [52.25000, 6.80000],
        ], true, 28.0);

        $this->assertEqualsWithDelta(52.22180, $path[0][0], 0.00001);
        $this->assertEqualsWithDelta(6.88990, $path[0][1], 0.00001);
        $this->assertEqualsWithDelta(52.25000, $path[1][0], 0.00001);
        $this->assertEqualsWithDelta(6.80000, $path[1][1], 0.00001);
    }
}

<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Services\ContractDemoDayResetService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractDemoDayResetServiceTest extends TestCase
{
    private string $conn = 'sqlite';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-14 10:00:00', 'Europe/Amsterdam'));
        Cache::flush();

        Schema::connection($this->conn)->dropIfExists('ride_stops');
        Schema::connection($this->conn)->dropIfExists('ride_requests');

        Schema::connection($this->conn)->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('accepted');
            $table->string('ride_type')->default('contract_group');
            $table->dateTime('trip_started_at')->nullable();
            $table->dateTime('trip_completed_at')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('ride_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('transport_passenger_id')->nullable();
            $table->string('stop_type');
            $table->string('status')->default('planned');
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
    public function it_resets_todays_contract_rides_and_leaves_other_days_alone(): void
    {
        $today = RideRequest::on($this->conn)->create([
            'status' => RideRequest::STATUS_COMPLETED,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_GROUP,
        ]);
        RideRequest::on($this->conn)->whereKey($today->id)->update([
            'trip_started_at' => '2026-10-14 08:00:00',
            'trip_completed_at' => '2026-10-14 09:00:00',
        ]);
        $pickup = RideStop::on($this->conn)->create([
            'ride_request_id' => $today->id,
            'transport_passenger_id' => 4,
            'stop_type' => RideStop::STOP_TYPE_PICKUP,
            'status' => RideStop::STATUS_PICKED_UP,
            'planned_at' => '2026-10-14 08:10:00',
            'completed_at' => '2026-10-14 08:20:00',
        ]);
        $destination = RideStop::on($this->conn)->create([
            'ride_request_id' => $today->id,
            'transport_passenger_id' => 4,
            'stop_type' => RideStop::STOP_TYPE_DESTINATION,
            'status' => RideStop::STATUS_COMPLETED,
            'planned_at' => '2026-10-14 08:40:00',
            'completed_at' => '2026-10-14 09:00:00',
        ]);

        $yesterday = RideRequest::on($this->conn)->create([
            'status' => RideRequest::STATUS_COMPLETED,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_GROUP,
        ]);
        $oldPickup = RideStop::on($this->conn)->create([
            'ride_request_id' => $yesterday->id,
            'transport_passenger_id' => 4,
            'stop_type' => RideStop::STOP_TYPE_PICKUP,
            'status' => RideStop::STATUS_PICKED_UP,
            'planned_at' => '2026-10-13 08:10:00',
        ]);

        $count = app(ContractDemoDayResetService::class)->resetToday($this->conn, [4], 9);

        $this->assertSame(1, $count);
        $this->assertSame(RideRequest::STATUS_ACCEPTED, RideRequest::on($this->conn)->whereKey($today->id)->value('status'));
        $this->assertNull(RideRequest::on($this->conn)->whereKey($today->id)->value('trip_started_at'));
        $this->assertNull(RideRequest::on($this->conn)->whereKey($today->id)->value('trip_completed_at'));
        $this->assertSame(RideStop::STATUS_PLANNED, RideStop::on($this->conn)->whereKey($pickup->id)->value('status'));
        $this->assertNull(RideStop::on($this->conn)->whereKey($pickup->id)->value('completed_at'));
        $this->assertSame(RideStop::STATUS_PLANNED, RideStop::on($this->conn)->whereKey($destination->id)->value('status'));
        $this->assertSame(RideRequest::STATUS_COMPLETED, RideRequest::on($this->conn)->whereKey($yesterday->id)->value('status'));
        $this->assertSame(RideStop::STATUS_PICKED_UP, RideStop::on($this->conn)->whereKey($oldPickup->id)->value('status'));
    }
}

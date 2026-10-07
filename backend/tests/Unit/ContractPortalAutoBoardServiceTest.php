<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Services\ContractPortalAutoBoardService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContractPortalAutoBoardServiceTest extends TestCase
{
    private string $conn = 'sqlite';

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-07 15:00:00', 'Europe/Amsterdam'));

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
            $table->string('stop_type');
            $table->string('status')->default('planned');
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
    public function it_does_not_auto_board_before_destination_grace(): void
    {
        $ride = $this->makeRide();
        $this->makeStop($ride->id, 'pickup', 'planned');
        $this->makeStop($ride->id, 'destination', 'completed', now()->subMinutes(2));

        $count = app(ContractPortalAutoBoardService::class)->applyForRide($this->conn, $ride, false);

        $this->assertSame(0, $count);
        $this->assertSame('planned', RideStop::on($this->conn)->where('stop_type', 'pickup')->value('status'));
    }

    #[Test]
    public function it_auto_boards_after_destination_grace_and_keeps_skipped(): void
    {
        $ride = $this->makeRide();
        $open = $this->makeStop($ride->id, 'pickup', 'planned');
        $skipped = $this->makeStop($ride->id, 'pickup', 'skipped');
        $this->makeStop($ride->id, 'destination', 'completed', now()->subMinutes(6));

        $count = app(ContractPortalAutoBoardService::class)->applyForRide($this->conn, $ride, false);

        $this->assertSame(1, $count);
        $this->assertSame('picked_up', RideStop::on($this->conn)->whereKey($open->id)->value('status'));
        $this->assertSame('skipped', RideStop::on($this->conn)->whereKey($skipped->id)->value('status'));
    }

    #[Test]
    public function it_force_boards_open_pickups_when_driver_completes(): void
    {
        $ride = $this->makeRide();
        $this->makeStop($ride->id, 'pickup', 'arrived');
        $this->makeStop($ride->id, 'pickup', 'skipped');

        $count = app(ContractPortalAutoBoardService::class)->applyForRide($this->conn, $ride, true);

        $this->assertSame(1, $count);
        $this->assertSame(
            ['picked_up', 'skipped'],
            RideStop::on($this->conn)->where('stop_type', 'pickup')->orderBy('id')->pluck('status')->all()
        );
    }

    private function makeRide(): RideRequest
    {
        $ride = new RideRequest;
        $ride->setConnection($this->conn);
        $ride->forceFill([
            'status' => RideRequest::STATUS_ASSIGNED,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_GROUP,
        ]);
        $ride->save();

        return $ride;
    }

    private function makeStop(
        int $rideId,
        string $type,
        string $status,
        ?Carbon $completedAt = null
    ): RideStop {
        $stop = new RideStop;
        $stop->setConnection($this->conn);
        $stop->forceFill([
            'ride_request_id' => $rideId,
            'stop_type' => $type,
            'status' => $status,
            'completed_at' => $completedAt,
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);
        $stop->save();

        return $stop;
    }
}

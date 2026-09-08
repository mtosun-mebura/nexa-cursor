<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideRequestMonthlyStatsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RideRequestMonthlyStatsServiceTest extends TestCase
{
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
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at');
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->timestamp('pickup_proposal_sent_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id')->index();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 20);
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->default('open');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('wave')->default(1);
            $table->timestamp('offered_at');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_counts_completed_revenue_cash_mollie_and_operational_stats(): void
    {
        $month = Carbon::parse('2026-09-01');

        $completed = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-09-10 09:00:00',
            'customer_name' => 'Voltooid',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_CANCELLED,
            'pickup_at' => '2026-09-11 09:00:00',
            'customer_name' => 'Geannuleerd',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_at' => '2026-09-12 09:00:00',
            'customer_name' => 'Open',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-08-10 09:00:00',
            'customer_name' => 'Vorige maand',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $reoffered = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_at' => '2026-09-13 09:00:00',
            'pickup_proposal_status' => RideRequest::PICKUP_PROPOSAL_DECLINED,
            'pickup_proposal_sent_at' => '2026-09-13 08:00:00',
            'customer_name' => 'Andere tijd',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);
        $declined = RideRequest::on('module_taxi')->create([
            'company_id' => 1,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_at' => '2026-09-14 09:00:00',
            'customer_name' => 'Geweigerd',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);

        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $completed->id,
            'company_id' => 1,
            'channel' => RidePayment::CHANNEL_CASH,
            'amount' => 40,
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => '2026-09-10 10:00:00',
        ]);
        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $completed->id,
            'company_id' => 1,
            'channel' => RidePayment::CHANNEL_DRIVER,
            'amount' => 60.50,
            'status' => RidePayment::STATUS_PAID,
            'paid_at' => '2026-09-10 10:05:00',
        ]);
        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $declined->id,
            'company_id' => 1,
            'driver_id' => 9,
            'status' => RideDispatchOffer::STATUS_DECLINED,
            'wave' => 1,
            'offered_at' => '2026-09-14 08:00:00',
            'responded_at' => '2026-09-14 08:10:00',
        ]);
        RideDispatchOffer::on('module_taxi')->create([
            'ride_request_id' => $reoffered->id,
            'company_id' => 1,
            'driver_id' => 8,
            'status' => RideDispatchOffer::STATUS_ACCEPTED,
            'wave' => 2,
            'offered_at' => '2026-09-13 07:00:00',
            'responded_at' => '2026-09-13 07:05:00',
        ]);

        $stats = app(RideRequestMonthlyStatsService::class)->forMonth(
            'module_taxi',
            $month,
            fn ($query) => $query->where('company_id', 1)
        );

        $this->assertSame(5, $stats['total']);
        $this->assertSame(1, $stats['completed']);
        $this->assertSame(1, $stats['cancelled']);
        $this->assertSame(2, $stats['open']);
        $this->assertSame(1, $stats['in_progress']);
        $this->assertSame(1, $stats['not_accepted']);
        $this->assertSame(1, $stats['time_reoffered']);
        $this->assertSame(1, $stats['time_reoffered_declined']);
        $this->assertSame(1, $stats['redispatched']);
        $this->assertEquals(40.0, $stats['revenue_cash']);
        $this->assertEquals(60.5, $stats['revenue_mollie']);
        $this->assertEquals(100.5, $stats['revenue_total']);
        $this->assertSame('2026-09', $stats['month']);
        $this->assertNotEmpty($stats['daily']);
        $this->assertSame(1, collect($stats['daily'])->firstWhere('date', '2026-09-10')['completed']);
    }

    public function test_ignores_other_company_when_scoped(): void
    {
        RideRequest::on('module_taxi')->create([
            'company_id' => 2,
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_at' => '2026-09-10 09:00:00',
            'customer_name' => 'Andere tenant',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);

        $stats = app(RideRequestMonthlyStatsService::class)->forMonth(
            'module_taxi',
            Carbon::parse('2026-09-01'),
            fn ($query) => $query->where('company_id', 1)
        );

        $this->assertSame(0, $stats['total']);
        $this->assertSame(0, $stats['completed']);
        $this->assertEquals(0.0, $stats['revenue_total']);
    }
}

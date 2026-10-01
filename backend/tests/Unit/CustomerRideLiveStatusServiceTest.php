<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\CustomerRideLiveStatusService;
use App\Modules\NexaTaxi\Support\TaxiCustomerAppSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerRideLiveStatusServiceTest extends TestCase
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
            $table->string('status', 32)->default('pending_dispatch');
            $table->string('source', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('payment_status', 20)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_track_token', 64)->nullable()->unique();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('channel', 20)->nullable();
            $table->string('mollie_payment_id', 64)->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 24)->nullable();
            $table->string('checkout_url', 500)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('mollie_payload')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function live_payload_reports_searching_before_accept(): void
    {
        $conn = 'module_taxi';
        TaxiCustomerAppSchema::ensureTrackTokenColumn($conn);

        $ride = RideRequest::on($conn)->create([
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'Centraal Station',
            'pickup_lat' => 52.373,
            'pickup_lng' => 4.893,
            'passengers' => 2,
            'customer_name' => 'Test Klant',
            'customer_phone' => '0612345678',
            'source' => RideRequest::SOURCE_NEXA_SUITE,
        ]);

        $live = app(CustomerRideLiveStatusService::class);
        $token = $live->issueTrackToken($ride);
        $found = $live->findByTrackToken($token);
        $this->assertNotNull($found);
        $this->assertSame((int) $ride->id, (int) $found->id);

        $payload = $live->livePayload($ride->fresh());
        $this->assertSame('searching', $payload['phase']);
        $this->assertFalse($payload['accepted']);
        $this->assertTrue($payload['can_cancel']);
        $this->assertFalse($payload['can_download_invoice']);
        $this->assertNull($payload['eta_minutes']);
    }

    #[Test]
    public function live_payload_marks_assigned_as_accepted(): void
    {
        $conn = 'module_taxi';

        $ride = RideRequest::on($conn)->create([
            'status' => RideRequest::STATUS_ASSIGNED,
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'Centraal Station',
            'pickup_lat' => 52.3731,
            'pickup_lng' => 4.8922,
            'passengers' => 1,
            'customer_name' => 'Test Klant',
            'customer_phone' => '0612345678',
            'source' => RideRequest::SOURCE_NEXA_SUITE,
        ]);

        $payload = app(CustomerRideLiveStatusService::class)->livePayload($ride);
        $this->assertSame('accepted', $payload['phase']);
        $this->assertTrue($payload['accepted']);
        $this->assertFalse($payload['can_cancel']);
        $this->assertFalse($payload['can_download_invoice']);
    }

    #[Test]
    public function live_payload_allows_invoice_when_completed(): void
    {
        $conn = 'module_taxi';

        $ride = RideRequest::on($conn)->create([
            'status' => RideRequest::STATUS_COMPLETED,
            'pickup_address' => 'Dam 1, Amsterdam',
            'dropoff_address' => 'Centraal Station',
            'pickup_lat' => 52.3731,
            'pickup_lng' => 4.8922,
            'passengers' => 1,
            'customer_name' => 'Test Klant',
            'customer_phone' => '0612345678',
            'source' => RideRequest::SOURCE_NEXA_SUITE,
        ]);

        $payload = app(CustomerRideLiveStatusService::class)->livePayload($ride);
        $this->assertSame('completed', $payload['phase']);
        $this->assertTrue($payload['can_download_invoice']);
        $this->assertFalse($payload['can_cancel']);
    }
}

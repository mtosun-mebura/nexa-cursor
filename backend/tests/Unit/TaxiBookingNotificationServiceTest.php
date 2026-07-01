<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Services\EnvService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiBookingNotificationServiceTest extends TestCase
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
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_request_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->string('channel');
            $table->string('status');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_address')->nullable();
            $table->text('detail')->nullable();
            $table->text('context')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function customer_booking_email_uses_tenant_mail_settings_when_ride_has_no_company(): void
    {
        Mail::fake();

        $companyId = 42;
        app()->instance('resolved_tenant_id', $companyId);

        $this->mock(EnvService::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('isMailDeliverableToInbox')->once()->with($companyId)->andReturn(true);
            $mock->shouldReceive('applyMailConfigToRuntime')->once()->with($companyId);
            $mock->shouldReceive('resolveMailFromHeaders')->once()->with($companyId)->andReturn([
                'from_address' => 'taxi@example.test',
                'from_name' => 'Taxi BV',
                'smtp_username' => '',
            ]);
        });

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Jan Tester',
            'customer_email' => 'jan@example.test',
            'customer_phone' => '0612345678',
            'quoted_price' => 34.94,
            'booking_payload' => [],
        ]);

        app(TaxiBookingNotificationService::class)->notifyNewRide('module_taxi', $ride, [
            'settings_company_id' => $companyId,
        ]);

        $this->assertTrue(true);
    }
}

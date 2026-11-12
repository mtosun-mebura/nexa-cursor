<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TaxiDriverNotification;
use App\Modules\NexaTaxi\Models\TaxiRideComplaint;
use App\Modules\NexaTaxi\Services\TaxiDriverBlockService;
use App\Modules\NexaTaxi\Services\TaxiDriverNotificationService;
use App\Modules\NexaTaxi\Services\TaxiRideComplaintService;
use App\Modules\NexaTaxi\Support\TaxiDriverComplaintSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxiRideComplaintServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.taxi' => config('database.connections.sqlite')]);
        TaxiDriverComplaintSchema::resetReadyCache();
        TaxiDriverComplaintSchema::ensure('taxi');

        if (! Schema::connection('taxi')->hasTable('ride_requests')) {
            Schema::connection('taxi')->create('ride_requests', function ($table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('fulfilling_company_id')->nullable();
                $table->unsignedBigInteger('driver_id')->nullable();
                $table->unsignedBigInteger('vehicle_id')->nullable();
                $table->string('status')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('pickup_address')->nullable();
                $table->string('dropoff_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_customer_can_file_complaint_on_completed_ride(): void
    {
        $company = Company::query()->create(['name' => 'Klacht Taxi', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);

        $ride = RideRequest::on('taxi')->create([
            'company_id' => $company->id,
            'driver_id' => $driver->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'customer_name' => 'Will Smith',
            'customer_email' => 'will@example.com',
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
        ]);

        $service = app(TaxiRideComplaintService::class);
        $row = $service->file('taxi', $ride, [
            'category' => 'rude',
            'description' => 'Chauffeur was onbeleefd tijdens de rit.',
            'preferred_contact' => 'email',
        ]);

        $this->assertInstanceOf(TaxiRideComplaint::class, $row);
        $this->assertSame(TaxiRideComplaint::STATUS_OPEN, $row->status);
        $this->assertSame('rude', $row->category);
        $this->assertSame((int) $ride->id, (int) $row->ride_request_id);
        $this->assertSame('email', $row->preferred_contact);
        $this->assertStringContainsString('Klacht over rit #'.$ride->id, (string) $row->subject);
        $this->assertStringContainsString('Klacht Taxi', (string) $row->subject);
        $payload = $service->payloadForRide('taxi', $ride);
        $this->assertTrue($payload['filed']);
        $this->assertFalse($payload['can_file']);

        $pulse = $service->adminPulse('taxi', (int) $company->id);
        $this->assertSame(1, $pulse['open_count']);
        $this->assertSame((int) $row->id, $pulse['latest_id']);
        $this->assertSame((int) $row->id, (int) ($pulse['latest']['id'] ?? 0));
    }

    public function test_block_driver_revokes_app_access_flag(): void
    {
        $company = Company::query()->create(['name' => 'Block Taxi', 'is_active' => true]);
        $driver = User::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create(['company_id' => $company->id]);

        $blocks = app(TaxiDriverBlockService::class);
        $blocks->block('taxi', (int) $driver->id, (int) $admin->id, 'Testblokkade', null, (int) $company->id);

        $this->assertTrue($blocks->isBlocked('taxi', (int) $driver->id));
        $this->assertSame(
            TaxiDriverBlockService::LOGIN_DENIED_MESSAGE,
            $blocks->loginDeniedMessage()
        );
        $this->assertTrue(
            TaxiDriverNotification::on('taxi')
                ->where('driver_id', $driver->id)
                ->where('type', TaxiDriverNotification::TYPE_BLOCKED)
                ->exists()
        );

        $blocks->unblock('taxi', (int) $driver->id);
        $this->assertFalse($blocks->isBlocked('taxi', (int) $driver->id));
    }
}

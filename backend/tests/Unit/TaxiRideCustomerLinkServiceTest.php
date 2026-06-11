<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideCustomerLinkService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiRideCustomerLinkServiceTest extends TestCase
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

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
        });

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_user_id')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->timestamps();
        });
    }

    public function test_links_orphan_rides_with_matching_email_and_company(): void
    {
        $user = User::factory()->create([
            'email' => 'Klant@Example.com',
            'company_id' => null,
        ]);

        $linked = RideRequest::on('module_taxi')->create([
            'company_id' => 5,
            'customer_user_id' => 99,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Andere',
            'customer_email' => 'klant@example.com',
        ]);

        $orphan = RideRequest::on('module_taxi')->create([
            'company_id' => 5,
            'customer_user_id' => null,
            'pickup_address' => 'C',
            'dropoff_address' => 'D',
            'pickup_at' => now()->addHours(2),
            'customer_name' => 'Gast',
            'customer_email' => ' klant@example.com ',
        ]);

        $otherCompany = RideRequest::on('module_taxi')->create([
            'company_id' => 9,
            'customer_user_id' => null,
            'pickup_address' => 'E',
            'dropoff_address' => 'F',
            'pickup_at' => now()->addHours(3),
            'customer_name' => 'Gast',
            'customer_email' => 'klant@example.com',
        ]);

        $count = app(TaxiRideCustomerLinkService::class)->linkOrphanRidesForUser($user, 5); // company filter expliciet

        $this->assertSame(1, $count);
        $this->assertSame((int) $user->id, (int) $orphan->fresh()->customer_user_id);
        $this->assertSame(99, (int) $linked->fresh()->customer_user_id);
        $this->assertNull($otherCompany->fresh()->customer_user_id);
    }

    public function test_provisions_customer_from_guest_booking_and_links_ride(): void
    {
        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'customer_user_id' => null,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Jan de Vries',
            'customer_email' => 'jan@example.com',
        ]);

        $service = app(TaxiRideCustomerLinkService::class);
        $user = $service->provisionCustomerFromGuestBookings('jan@example.com', (int) $company->id);

        $this->assertNotNull($user);
        $this->assertSame('jan@example.com', $user->email);
        $this->assertTrue($user->password_must_be_set);
        $this->assertSame('Jan', $user->first_name);
        $this->assertSame('de Vries', $user->last_name);
        $this->assertSame((int) $user->id, (int) $ride->fresh()->customer_user_id);
    }

    public function test_provision_returns_null_without_matching_guest_booking(): void
    {
        $user = app(TaxiRideCustomerLinkService::class)->provisionCustomerFromGuestBookings('onbekend@example.com', 5);

        $this->assertNull($user);
    }
}

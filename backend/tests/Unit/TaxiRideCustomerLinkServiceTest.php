<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideCustomerLinkService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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
}

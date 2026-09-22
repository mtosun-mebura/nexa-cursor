<?php

namespace Tests\Feature;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideCancellationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiBookingCancelTest extends TestCase
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
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('ride_type', 40)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at')->nullable();
            $table->dateTime('pickup_proposal_at')->nullable();
            $table->string('pickup_proposal_status', 32)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->string('status', 24)->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_dispatch_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 24)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function signed_cancel_page_shows_confirm_form(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Station',
            'dropoff_address' => 'Centrum',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
        ]);

        $url = URL::temporarySignedRoute(
            'nexataxi.booking.cancel.show',
            now()->addHour(),
            ['ride' => $ride->id]
        );

        $this->get($url)
            ->assertOk()
            ->assertSee('Rit annuleren')
            ->assertSee('Ja, rit annuleren')
            ->assertSee('teruggestort');
    }

    #[Test]
    public function unsigned_cancel_page_is_forbidden(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Station',
            'dropoff_address' => 'Centrum',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
        ]);

        $this->get(route('nexataxi.booking.cancel.show', ['ride' => $ride->id]))
            ->assertForbidden();
    }

    #[Test]
    public function customer_cancel_url_is_generated_while_unaccepted(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_address' => 'Station',
            'dropoff_address' => 'Centrum',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
        ]);

        $url = app(TaxiRideCancellationService::class)->customerCancelUrl($ride);
        $this->assertNotNull($url);
        $this->assertStringContainsString('/nexa-taxi/booking/annuleren/'.$ride->id, $url);
    }
}

<?php

namespace Tests\Feature;

use App\Modules\NexaTaxi\Controllers\TaxiBookingPaymentController;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiBookingPaymentReturnTest extends TestCase
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
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name');
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->string('status', 32)->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function paid_return_redirects_to_booking_page_with_success_flag(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'pickup_address' => 'Station Enschede',
            'dropoff_address' => 'Molenstraat 22',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
        ]);

        $back = 'http://localhost/?_tenant_host=taxiroyaal.nexasuite.nl#boek-rit';

        $response = $this->withSession([
            'nexataxi.booking_payment.'.$ride->id => [
                'return_url' => $back,
                'message' => 'Bedankt! Je boeking is ontvangen.',
            ],
        ])->get(route('nexataxi.booking.payment.return', ['ride' => $ride->id]));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringContainsString('boeking=betaald', $location);
        $this->assertStringContainsString('_tenant_host=taxiroyaal.nexasuite.nl', $location);
        $this->assertStringContainsString('#boek-rit', $location);
    }

    #[Test]
    public function paid_payment_record_redirects_even_if_ride_status_lags(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => 'Station Enschede',
            'dropoff_address' => 'Molenstraat 22',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
        ]);

        RidePayment::on('module_taxi')->create([
            'ride_request_id' => $ride->id,
            'status' => RidePayment::STATUS_PAID,
        ]);

        $response = $this->withSession([
            'nexataxi.booking_payment.'.$ride->id => [
                'return_url' => 'http://localhost/#boek-rit',
            ],
        ])->get(route('nexataxi.booking.payment.return', ['ride' => $ride->id]));

        $response->assertRedirect();
        $this->assertStringContainsString('boeking=betaald', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function processing_return_stays_on_payment_page(): void
    {
        $ride = RideRequest::on('module_taxi')->create([
            'status' => RideRequest::STATUS_PENDING_PAYMENT,
            'payment_status' => RideRequest::PAYMENT_STATUS_PENDING,
            'pickup_address' => 'Station Enschede',
            'dropoff_address' => 'Molenstraat 22',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Test Klant',
        ]);

        $response = $this->get(route('nexataxi.booking.payment.return', ['ride' => $ride->id]));

        $response->assertOk();
        $response->assertSee('Betaling wordt verwerkt');
        $response->assertDontSee('Betaling ontvangen');
    }

    #[Test]
    public function safe_return_url_rejects_external_hosts(): void
    {
        $request = Request::create('http://localhost/nexa-taxi/booking/betaling/terug');
        $url = TaxiBookingPaymentController::safeReturnUrl('https://evil.example/phish', $request);

        $this->assertStringNotContainsString('evil.example', $url);
        $this->assertStringContainsString('#boek-rit', $url);
    }

    #[Test]
    public function with_booking_result_query_keeps_hash_and_existing_query(): void
    {
        $out = TaxiBookingPaymentController::withBookingResultQuery(
            'http://localhost/?_tenant_host=taxiroyaal.nexasuite.nl#boek-rit',
            'betaald'
        );

        $this->assertSame(
            'http://localhost/?_tenant_host=taxiroyaal.nexasuite.nl&boeking=betaald#boek-rit',
            $out
        );
    }
}

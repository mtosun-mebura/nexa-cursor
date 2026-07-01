<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\WebsiteBuilderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NexaTaxiBookingGuestAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

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

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('person_range')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('customer_user_id')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_status', 32)->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->decimal('pickup_lat', 10, 7)->nullable();
            $table->decimal('pickup_lng', 10, 7)->nullable();
            $table->decimal('dropoff_lat', 10, 7)->nullable();
            $table->decimal('dropoff_lng', 10, 7)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('passengers')->default(1);
            $table->dateTime('pickup_at');
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_note')->nullable();
            $table->dateTime('quote_expires_at')->nullable();
            $table->json('booking_payload')->nullable();
            $table->json('selected_offer_payload')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function admin_logged_in_guest_booking_uses_submitted_email_and_creates_klant_account(): void
    {
        $company = Company::query()->create(['name' => 'Taxi BV', 'is_active' => true]);
        app()->instance('resolved_tenant_id', $company->id);

        $admin = User::query()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'm.tosun@mebura.nl',
            'password' => 'secret',
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $admin->assignRole('super-admin');

        $vehicle = Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Sedan',
            'person_range' => '1-4',
            'active' => true,
        ]);

        $defaultConfig = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();

        $this->mock(WebsiteBuilderService::class, function ($mock) use ($company, $defaultConfig): void {
            $mock->shouldReceive('resolveBookingModuleSection')
                ->andReturn([
                    'config' => $defaultConfig,
                    'tenant_company_id' => $company->id,
                ]);
        });

        $this->mock(NexaTaxiBookingPricingService::class, function ($mock) use ($defaultConfig, $vehicle): void {
            $mock->shouldReceive('getDefaultSectionConfig')->andReturn($defaultConfig);
            $mock->shouldReceive('buildQuotes')->andReturn([
                'offers' => [[
                    'id' => 'offer-1',
                    'vehicle_id' => $vehicle->id,
                    'price' => 42.50,
                ]],
            ]);
        });

        $this->mock(TaxiDispatchSettingsService::class, function ($mock): void {
            $mock->shouldReceive('customerEmailRequiredForBooking')->andReturn(false);
            $mock->shouldReceive('paymentOptionsForTenant')->andReturn([
                'booking' => false,
                'driver' => true,
            ]);
        });

        $this->mock(TaxiRidePaymentService::class, function ($mock): void {
            $mock->shouldReceive('validatePaymentMethodChoice')
                ->andReturn(RideRequest::PAYMENT_METHOD_DRIVER);
        });

        $this->mock(RideDispatchService::class, function ($mock): void {
            $mock->shouldReceive('startDispatch');
        });

        $this->mock(TaxiBookingNotificationService::class, function ($mock): void {
            $mock->shouldReceive('notifyNewRide');
        });

        $this->mock(TaxiCustomerLoginCodeService::class, function ($mock): void {
            $mock->shouldReceive('issueAndSend')
                ->once()
                ->andReturn(true);
        });

        $guestEmail = 'membur+dc@gmail.com';

        $response = $this->actingAs($admin)->postJson(route('nexataxi.booking.submit'), [
            'section_key' => 'component:taxi.boekingsmodule',
            'module' => 'taxi',
            'selected_offer_id' => 'offer-1',
            'distance_meters' => 5000,
            'duration_seconds' => 600,
            'passengers' => 2,
            'pickup_address' => 'Hoofdstraat 1, Amsterdam',
            'dropoff_address' => 'Station 2, Amsterdam',
            'pickup_at' => now()->addDay()->toIso8601String(),
            'first_name' => 'Don',
            'last_name' => 'Corleone',
            'email' => $guestEmail,
            'phone' => '0612345678',
            'create_account' => true,
            'payment_method' => 'driver',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('account_created', true)
            ->assertJsonPath('login_code_email_sent', true);

        $ride = RideRequest::on('module_taxi')->first();
        $this->assertNotNull($ride);
        $this->assertSame($guestEmail, $ride->customer_email);
        $this->assertNotSame($admin->email, $ride->customer_email);

        $customer = User::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($guestEmail)])->first();
        $this->assertNotNull($customer);
        $this->assertTrue($customer->password_must_be_set);
        $this->assertSame((int) $company->id, (int) $customer->company_id);

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($company->id);
        $this->assertTrue($customer->hasRole('klant'));
        $registrar->setPermissionsTeamId($previousTeamId);

        $this->assertSame((int) $customer->id, (int) $ride->customer_user_id);
        $this->assertNotSame((int) $admin->id, (int) $ride->customer_user_id);
    }
}

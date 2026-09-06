<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyBillingProfile;
use App\Models\Module;
use App\Models\NexaSuiteBookingInvoice;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Models\User;
use App\Models\WebsitePage;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use App\Services\NexaSuiteMarketplaceBillingService;
use App\Services\NexaTaxiBookingPricingService;
use App\Services\WebsiteBuilderService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NexaSuiteMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'klant', 'guard_name' => 'web']);

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

        Schema::connection('module_taxi')->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('person_range')->nullable();
            $table->string('type')->nullable();
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
            $table->string('source', 24)->default('booking');
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
            $table->decimal('final_price', 10, 2)->nullable();
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
    public function central_booking_is_assigned_to_nearest_tenant_and_marked_nexa_suite(): void
    {
        [$near, $vehicle] = $this->twoTaxiTenants();
        $page = WebsitePage::query()->create([
            'slug' => 'boek',
            'title' => 'Taxi boeken',
            'page_type' => 'custom',
            'company_id' => null,
            'is_active' => true,
        ]);

        $defaultConfig = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        $this->mock(WebsiteBuilderService::class, function ($mock) use ($defaultConfig): void {
            $mock->shouldReceive('resolveBookingModuleSection')->andReturn([
                'config' => $defaultConfig,
                'tenant_company_id' => null,
            ]);
        });
        $this->mock(NexaTaxiBookingPricingService::class, function ($mock) use ($defaultConfig, $vehicle): void {
            $mock->shouldReceive('getDefaultSectionConfig')->andReturn($defaultConfig);
            $mock->shouldReceive('mergeSectionConfig')->andReturn($defaultConfig);
            $mock->shouldReceive('buildQuotes')->andReturn([
                'offers' => [[
                    'id' => 'offer-1',
                    'vehicle_id' => $vehicle->id,
                    'price' => 42.5,
                ]],
            ]);
        });
        $this->mockDispatchStack();

        $response = $this->postJson(route('nexataxi.booking.submit'), [
            'page_id' => $page->id,
            'section_key' => 'component:taxi.boekingsmodule',
            'selected_offer_id' => 'offer-1',
            'distance_meters' => 5000,
            'duration_seconds' => 600,
            'passengers' => 1,
            'pickup_address' => 'Dam, Amsterdam',
            'dropoff_address' => 'Centraal Station, Amsterdam',
            'pickup_at' => now()->addHour()->toIso8601String(),
            'pickup_lat' => 52.373,
            'pickup_lng' => 4.893,
            'first_name' => 'Anna',
            'last_name' => 'Klant',
            'phone' => '0612345678',
            'payment_method' => 'driver',
        ]);

        $response->assertOk();
        $ride = RideRequest::on('module_taxi')->first();
        $this->assertNotNull($ride);
        $this->assertSame($near->id, (int) $ride->company_id);
        $this->assertSame(RideRequest::SOURCE_NEXA_SUITE, $ride->source);
        $this->assertTrue($ride->isNexaSuiteBooking());
        $this->assertSame('nexa_suite', $ride->booking_payload['channel'] ?? null);
    }

    #[Test]
    public function algemene_booking_module_assigns_nearest_tenant_even_on_a_tenant_page(): void
    {
        [$near, $vehicle] = $this->twoTaxiTenants();
        $far = Company::query()->where('name', 'Taxi Maastricht')->first();
        $page = WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Tenant site',
            'page_type' => 'home',
            'company_id' => $far->id,
            'is_active' => true,
        ]);
        $this->stubBookingQuotes($vehicle);
        $this->mockDispatchStack();

        $response = $this->postJson(route('nexataxi.booking.submit'), $this->bookingPayload($page->id, 'component:taxi.algemene_boekingsmodule'));

        $response->assertOk();
        $ride = RideRequest::on('module_taxi')->first();
        $this->assertNotNull($ride);
        $this->assertSame($near->id, (int) $ride->company_id);
        $this->assertSame(RideRequest::SOURCE_NEXA_SUITE, $ride->source);
    }

    #[Test]
    public function booking_module_v2_stays_on_the_page_tenant(): void
    {
        [$near, $vehicle] = $this->twoTaxiTenants();
        $far = Company::query()->where('name', 'Taxi Maastricht')->first();
        $farVehicle = Vehicle::on('module_taxi')->where('company_id', $far->id)->first();
        $page = WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Tenant site',
            'page_type' => 'home',
            'company_id' => $far->id,
            'is_active' => true,
        ]);
        $this->stubBookingQuotes($farVehicle);
        $this->mockDispatchStack();

        $response = $this->postJson(route('nexataxi.booking.submit'), $this->bookingPayload($page->id, 'component:taxi.boekingsmodule_v2'));

        $response->assertOk();
        $ride = RideRequest::on('module_taxi')->first();
        $this->assertNotNull($ride);
        $this->assertSame($far->id, (int) $ride->company_id);
        $this->assertNotSame($near->id, (int) $ride->company_id);
        $this->assertNotSame(RideRequest::SOURCE_NEXA_SUITE, $ride->source);
    }

    #[Test]
    public function booking_module_v2_on_central_page_is_rejected(): void
    {
        [, $vehicle] = $this->twoTaxiTenants();
        $page = WebsitePage::query()->create([
            'slug' => 'boek',
            'title' => 'Taxi boeken',
            'page_type' => 'custom',
            'company_id' => null,
            'is_active' => true,
        ]);
        $this->stubBookingQuotes($vehicle);

        $response = $this->postJson(route('nexataxi.booking.submit'), $this->bookingPayload($page->id, 'component:taxi.boekingsmodule_v2'));

        $response->assertStatus(422);
        $this->assertNull(RideRequest::on('module_taxi')->first());
    }

    #[Test]
    public function nearby_taxis_endpoint_returns_idle_online_taxis_for_algemene_module(): void
    {
        [$near, $vehicle] = $this->twoTaxiTenants();
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 11,
            'company_id' => $near->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.90,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 12,
            'company_id' => $near->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.371,
            'lng' => 4.901,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => $near->id,
            'driver_id' => 12,
            'status' => RideRequest::STATUS_ACCEPTED,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Busy',
        ]);

        $response = $this->getJson(route('nexataxi.booking.nearby-taxis', [
            'section_key' => 'component:taxi.algemene_boekingsmodule',
            'lat' => 52.37,
            'lng' => 4.90,
        ]));

        $response->assertOk();
        $vehicles = $response->json('vehicles');
        $this->assertCount(1, $vehicles);
        $this->assertEqualsWithDelta(52.37, (float) $vehicles[0]['lat'], 0.001);
    }

    #[Test]
    public function nearby_taxis_endpoint_is_empty_for_tenant_v2_module(): void
    {
        [$near, $vehicle] = $this->twoTaxiTenants();
        DriverAvailability::on('module_taxi')->create([
            'driver_id' => 11,
            'company_id' => $near->id,
            'vehicle_id' => $vehicle->id,
            'is_online' => true,
            'lat' => 52.37,
            'lng' => 4.90,
            'location_updated_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->getJson(route('nexataxi.booking.nearby-taxis', [
            'section_key' => 'component:taxi.boekingsmodule_v2',
            'lat' => 52.37,
            'lng' => 4.90,
        ]))->assertOk()->assertJson(['vehicles' => []]);
    }

    #[Test]
    public function monthly_invoice_uses_fee_percent_and_ride_count_line(): void
    {
        Mail::fake();
        [$company] = $this->twoTaxiTenants();
        CompanyBillingProfile::query()->create([
            'company_id' => $company->id,
            'billing_mode' => 'custom',
            'billing_email' => 'factuur@taxi.test',
        ]);

        RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(3),
            'quoted_price' => 100,
            'final_price' => 100,
            'customer_name' => 'Test',
        ]);
        RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'pickup_address' => 'C',
            'dropoff_address' => 'D',
            'pickup_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(8),
            'quoted_price' => 50,
            'customer_name' => 'Test 2',
        ]);

        NexaSuiteMarketplaceSetting::current()->update(['fee_percent' => 10, 'tax_rate_percent' => 21]);
        $period = now()->subMonthNoOverflow()->format('Y-m');
        $stats = app(NexaSuiteMarketplaceBillingService::class)->generateForPeriod($period, false, $company->id);

        $this->assertSame(1, $stats['generated']);
        $invoice = NexaSuiteBookingInvoice::query()->first();
        $this->assertNotNull($invoice);
        $this->assertSame(2, $invoice->ride_count);
        $this->assertEquals(150.0, (float) $invoice->rides_subtotal);
        $this->assertEquals(15.0, (float) $invoice->amount);
        $this->assertEquals(3.15, (float) $invoice->tax_amount);
        $this->assertEquals(18.15, (float) $invoice->total_amount);
        $this->assertStringContainsString('2 gereden ritten vanuit NEXA Suite', $invoice->line_items[0]['description'] ?? '');
    }

    #[Test]
    public function super_admin_can_open_nexa_suite_bookings_page_and_update_fee(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.index'))
            ->assertOk()
            ->assertSee('NEXA Suite ritten', false);

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.settings'))
            ->assertOk()
            ->assertSee('name="tax_rate_percent"', false)
            ->assertSee('step="1"', false)
            ->assertDontSee('value="21.00"', false);

        $this->actingAs($user)
            ->put(route('admin.nexa-suite-bookings.settings.update'), [
                'fee_percent' => 12,
                'auto_generate' => 1,
                'auto_send' => 1,
                'billing_day' => 2,
                'billing_time' => '07:00',
                'tax_rate_percent' => 21,
                'payment_terms_days' => 14,
                'dunning_first_interval_days' => 1,
                'dunning_interval_days' => 14,
                'invoice_number_prefix' => 'NSB',
                'invoice_title' => 'NEXA Suite boekingsfactuur',
            ])
            ->assertRedirect(route('admin.nexa-suite-bookings.settings'));

        $this->assertSame(12, (int) NexaSuiteMarketplaceSetting::current()->fee_percent);
        $this->assertSame(21, (int) NexaSuiteMarketplaceSetting::current()->tax_rate_percent);
    }

    #[Test]
    public function period_filter_accepts_month_picker_display_format(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.index', ['period' => '08-2026']))
            ->assertOk()
            ->assertSee('data-kt-date-picker-type="month"', false)
            ->assertSee('ki-filled ki-calendar', false);

        $this->actingAs($user)
            ->get(route('admin.nexa-suite-bookings.invoices', ['period' => '08-2026']))
            ->assertOk()
            ->assertSee('data-kt-date-picker-type="month"', false);
    }

    /**
     * @return array{0: Company, 1: Vehicle}
     */
    private function twoTaxiTenants(): array
    {
        $taxi = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
        ]);

        $near = Company::query()->create([
            'name' => 'Taxi Amsterdam',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 52.3676,
            'longitude' => 4.9041,
            'email' => 'amsterdam@taxi.test',
            'accepts_nexa_suite_bookings' => true,
        ]);
        $far = Company::query()->create([
            'name' => 'Taxi Maastricht',
            'is_active' => true,
            'is_main' => false,
            'latitude' => 50.8514,
            'longitude' => 5.6910,
            'accepts_nexa_suite_bookings' => true,
        ]);
        $near->modules()->attach($taxi->id);
        $far->modules()->attach($taxi->id);

        $vehicle = Vehicle::on('module_taxi')->create([
            'company_id' => $near->id,
            'name' => 'Sedan',
            'person_range' => '1-4',
            'active' => true,
        ]);
        Vehicle::on('module_taxi')->create([
            'company_id' => $far->id,
            'name' => 'Sedan Zuid',
            'person_range' => '1-4',
            'active' => true,
        ]);

        return [$near, $vehicle];
    }

    private function stubBookingQuotes(Vehicle $vehicle): void
    {
        $defaultConfig = app(NexaTaxiBookingPricingService::class)->getDefaultSectionConfig();
        $this->mock(WebsiteBuilderService::class, function ($mock) use ($defaultConfig): void {
            $mock->shouldReceive('resolveBookingModuleSection')->andReturn([
                'config' => $defaultConfig,
                'tenant_company_id' => null,
            ]);
        });
        $this->mock(NexaTaxiBookingPricingService::class, function ($mock) use ($defaultConfig, $vehicle): void {
            $mock->shouldReceive('getDefaultSectionConfig')->andReturn($defaultConfig);
            $mock->shouldReceive('mergeSectionConfig')->andReturn($defaultConfig);
            $mock->shouldReceive('buildQuotes')->andReturn([
                'offers' => [[
                    'id' => 'offer-1',
                    'vehicle_id' => $vehicle->id,
                    'price' => 42.5,
                ]],
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(int $pageId, string $sectionKey): array
    {
        return [
            'page_id' => $pageId,
            'section_key' => $sectionKey,
            'selected_offer_id' => 'offer-1',
            'distance_meters' => 5000,
            'duration_seconds' => 600,
            'passengers' => 1,
            'pickup_address' => 'Dam, Amsterdam',
            'dropoff_address' => 'Centraal Station, Amsterdam',
            'pickup_at' => now()->addHour()->toIso8601String(),
            'pickup_lat' => 52.373,
            'pickup_lng' => 4.893,
            'first_name' => 'Anna',
            'last_name' => 'Klant',
            'phone' => '0612345678',
            'payment_method' => 'driver',
        ];
    }

    private function mockDispatchStack(): void
    {
        $this->mock(TaxiDispatchSettingsService::class, function ($mock): void {
            $mock->shouldReceive('customerEmailRequiredForBooking')->andReturn(false);
            $mock->shouldReceive('paymentOptionsForTenant')->andReturn([
                'booking' => false,
                'driver' => true,
            ]);
        });
        $this->mock(TaxiRidePaymentService::class, function ($mock): void {
            $mock->shouldReceive('validatePaymentMethodChoice')->andReturn(RideRequest::PAYMENT_METHOD_DRIVER);
        });
        $this->mock(RideDispatchService::class, function ($mock): void {
            $mock->shouldReceive('startDispatch');
        });
        $this->mock(TaxiBookingNotificationService::class, function ($mock): void {
            $mock->shouldReceive('notifyNewRide');
        });
    }
}

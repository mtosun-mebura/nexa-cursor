<?php

namespace Tests\Unit;

use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\AiChat\AiChatLiveQueryService;
use App\Services\AiChat\AiChatSqlGuardService;
use App\Services\AiChat\AiChatTaxiRoleQueryService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiChatLiveQueryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.module_taxi_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        Schema::connection('module_taxi_test')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->boolean('active')->default(true);
        });

        Schema::connection('module_taxi_test')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('customer_user_id')->nullable();
            $table->string('status', 20);
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
        });

        DB::connection('module_taxi_test')->table('vehicles')->insert([
            'id' => 1,
            'company_id' => 2,
            'name' => 'Taxi 1',
            'active' => true,
        ]);

        $driver = User::factory()->create([
            'first_name' => 'Piet',
            'last_name' => 'Chauffeur',
        ]);

        DB::connection('module_taxi_test')->table('ride_requests')->insert([
            'company_id' => null,
            'vehicle_id' => 1,
            'driver_id' => $driver->id,
            'status' => 'offered',
            'pickup_address' => 'Stationsplein 1',
            'dropoff_address' => 'Schiphol',
            'pickup_at' => now()->addDay(),
            'customer_name' => 'Test Klant',
            'customer_phone' => '06-11112222',
        ]);

        $moduleDb = $this->createMock(ModuleDatabaseService::class);
        $moduleDb->method('ensureModuleStorageReady')->with('taxi');
        $moduleDb->method('getModuleConnectionName')->with('taxi')->willReturn('module_taxi_test');

        $this->app->instance(ModuleDatabaseService::class, $moduleDb);
        $this->app->instance(AiChatTaxiRoleQueryService::class, new AiChatTaxiRoleQueryService());
    }

    public function test_own_rides_vandaag_excludes_yesterday(): void
    {
        $customer = User::factory()->create([
            'email' => 'klant-vandaag@example.com',
        ]);

        DB::connection('module_taxi_test')->table('ride_requests')->insert([
            [
                'company_id' => 2,
                'customer_user_id' => $customer->id,
                'customer_email' => $customer->email,
                'status' => RideRequest::STATUS_ACCEPTED,
                'pickup_address' => 'Gisteren ophalen',
                'dropoff_address' => 'Oud adres',
                'pickup_at' => now()->subDay()->setTime(10, 0),
                'customer_name' => 'Test Klant',
            ],
            [
                'company_id' => 2,
                'customer_user_id' => $customer->id,
                'customer_email' => $customer->email,
                'status' => RideRequest::STATUS_ACCEPTED,
                'pickup_address' => 'Vandaag ophalen',
                'dropoff_address' => 'Nieuw adres',
                'pickup_at' => now()->setTime(15, 0),
                'customer_name' => 'Test Klant',
            ],
        ]);

        $service = new AiChatLiveQueryService(
            app(ModuleDatabaseService::class),
            new AiChatSqlGuardService(),
            app(AiChatTaxiRoleQueryService::class),
        );

        $result = $service->execute(AiChatIntent::MijnRit, [
            'company_id' => 2,
            'user_id' => $customer->id,
            'channel' => AiChatChannel::MijnTaxi->value,
            'intent' => AiChatIntent::MijnRit->value,
            'allow_live_data' => true,
            'allow_public_rates' => false,
            'query_hint' => 'vandaag',
            'exp' => now()->addMinute()->timestamp,
        ]);

        $this->assertSame(1, $result['count']);
        $this->assertSame('Vandaag ophalen', $result['rows'][0]['pickup_address']);
    }

    public function test_own_rides_aankomend_only_returns_future_rides(): void
    {
        $customer = User::factory()->create([
            'email' => 'klant-aankomend@example.com',
        ]);

        DB::connection('module_taxi_test')->table('ride_requests')->insert([
            [
                'company_id' => 2,
                'customer_user_id' => $customer->id,
                'customer_email' => $customer->email,
                'status' => RideRequest::STATUS_COMPLETED,
                'pickup_address' => 'Oude rit',
                'dropoff_address' => 'Afgerond',
                'pickup_at' => now()->subHours(2),
                'customer_name' => 'Test Klant',
            ],
            [
                'company_id' => 2,
                'customer_user_id' => $customer->id,
                'customer_email' => $customer->email,
                'status' => RideRequest::STATUS_ACCEPTED,
                'pickup_address' => 'Toekomst rit',
                'dropoff_address' => 'Later',
                'pickup_at' => now()->addDay()->setTime(12, 0),
                'customer_name' => 'Test Klant',
            ],
        ]);

        $service = new AiChatLiveQueryService(
            app(ModuleDatabaseService::class),
            new AiChatSqlGuardService(),
            app(AiChatTaxiRoleQueryService::class),
        );

        $result = $service->execute(AiChatIntent::MijnRit, [
            'company_id' => 2,
            'user_id' => $customer->id,
            'channel' => AiChatChannel::MijnTaxi->value,
            'intent' => AiChatIntent::MijnRit->value,
            'allow_live_data' => true,
            'allow_public_rates' => false,
            'query_hint' => 'aankomend',
            'exp' => now()->addMinute()->timestamp,
        ]);

        $this->assertSame(1, $result['count']);
        $this->assertSame('Toekomst rit', $result['rows'][0]['pickup_address']);
    }

    public function test_finds_ride_via_vehicle_company_when_ride_company_id_is_null(): void
    {
        $service = new AiChatLiveQueryService(
            app(ModuleDatabaseService::class),
            new AiChatSqlGuardService(),
            app(AiChatTaxiRoleQueryService::class),
        );

        $result = $service->execute(AiChatIntent::RittenKomend, [
            'company_id' => 2,
            'user_id' => 1,
            'channel' => AiChatChannel::Admin->value,
            'intent' => AiChatIntent::RittenKomend->value,
            'allow_live_data' => true,
            'allow_public_rates' => false,
            'exp' => now()->addMinute()->timestamp,
        ]);

        $this->assertSame(1, $result['count']);
        $this->assertSame('Test Klant', $result['rows'][0]['customer_name']);
        $this->assertSame('Piet Chauffeur', $result['rows'][0]['driver_name']);
        $this->assertSame('Taxi 1', $result['rows'][0]['vehicle_name']);
        $this->assertSame('06-11112222', $result['rows'][0]['customer_phone']);
        $this->assertSame('Aangeboden', $result['rows'][0]['status_label']);
    }
}

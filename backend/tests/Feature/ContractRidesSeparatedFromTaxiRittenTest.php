<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractRidesSeparatedFromTaxiRittenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.delete', 'guard_name' => 'web']);

        config([
            'module_database.strategy' => 'single',
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        \Illuminate\Support\Facades\Schema::connection('module_taxi')->create('companies', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::connection('module_taxi')->create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::connection('module_taxi')->create('vehicles', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        \Illuminate\Support\Facades\Schema::connection('module_taxi')->create('ride_requests', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('source', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at');
            $table->string('customer_name')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi');
        app(TaxiContractvervoerSchemaService::class)->ensureRideRequestContractColumns('module_taxi');

        if (! Route::has('admin.taxi.transport_rides.index')) {
            Route::middleware('web')
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            Route::getRoutes()->refreshNameLookups();
        }
    }

    #[Test]
    #[Group('taxi')]
    public function taxi_ritten_index_excludes_contract_rides_and_contract_page_shows_them(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Split Test',
            'is_active' => true,
            'package_key' => 'business',
        ]);

        $taxiRide = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_OFFERED,
            'source' => 'website',
            'pickup_address' => 'Taxi ophaal',
            'dropoff_address' => 'Taxi afzet',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Gewone taxi',
        ]);

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'School Actief',
            'organization_type' => 'school',
            'active' => true,
        ]);

        $contract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'name' => 'Schoolcontract',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $contractRide = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_ASSIGNED,
            'source' => RideRequest::SOURCE_CONTRACT,
            'payment_method' => RideRequest::PAYMENT_METHOD_CONTRACT,
            'transport_contract_id' => $contract->id,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            'pickup_address' => 'Contract ophaal',
            'dropoff_address' => 'Contract afzet',
            'pickup_at' => now()->addHours(2),
            'customer_name' => 'Contractrit actief',
        ]);

        $archivedCustomer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'School Archief',
            'organization_type' => 'school',
            'active' => false,
            'archived_at' => now()->subDay(),
            'archive_keep_past_rides' => false,
        ]);

        $archivedContract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $archivedCustomer->id,
            'name' => 'Gearchiveerd contract',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 50,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $archivedRide = RideRequest::on('module_taxi')->create([
            'company_id' => $company->id,
            'status' => RideRequest::STATUS_COMPLETED,
            'source' => RideRequest::SOURCE_CONTRACT,
            'payment_method' => RideRequest::PAYMENT_METHOD_CONTRACT,
            'transport_contract_id' => $archivedContract->id,
            'ride_type' => RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            'pickup_address' => 'Archief ophaal',
            'dropoff_address' => 'Archief afzet',
            'pickup_at' => now()->subDays(2),
            'customer_name' => 'Gearchiveerde contractrit',
        ]);

        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.taxi.ride_requests.index'))
            ->assertOk()
            ->assertSee('Gewone taxi', false)
            ->assertDontSee('Contractrit actief', false)
            ->assertDontSee('Gearchiveerde contractrit', false);

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.taxi.transport_rides.index'))
            ->assertOk()
            ->assertSee('Contractrit actief', false)
            ->assertDontSee('Gewone taxi', false)
            ->assertDontSee('Gearchiveerde contractrit', false);

        $this->assertTrue($taxiRide->fresh()->exists);
        $this->assertTrue($contractRide->fresh()->exists);
        $this->assertTrue($archivedRide->fresh()->exists);
    }
}

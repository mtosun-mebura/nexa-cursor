<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRideRequestBulkDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.delete', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

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

        Schema::connection('module_taxi')->create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->string('status', 32)->default('offered');
            $table->string('source', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->dateTime('pickup_at');
            $table->string('customer_name')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->timestamps();
        });

        if (! Route::has('admin.taxi.ride_requests.bulk-destroy')) {
            Route::middleware('web')
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            Route::getRoutes()->refreshNameLookups();
        }
    }

    #[Test]
    public function index_shows_select_all_for_super_admin(): void
    {
        $company = $this->company();
        $this->ride($company->id, 'Eerste rit');

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.taxi.ride_requests.index'))
            ->assertOk()
            ->assertSee('rides-select-all', false)
            ->assertSee('ride-row-checkbox', false)
            ->assertSee('rides-bulk-delete', false)
            ->assertSee('Geselecteerde ritten verwijderen', false)
            ->assertSee('min-h-[168px]', false);
    }

    #[Test]
    public function super_admin_can_bulk_delete_rides(): void
    {
        $company = $this->company();
        $first = $this->ride($company->id, 'Eerste');
        $second = $this->ride($company->id, 'Tweede');
        $kept = $this->ride($company->id, 'Blijft');

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->delete(route('admin.taxi.ride_requests.bulk-destroy'), [
                'ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('admin.taxi.ride_requests.index'));

        $this->assertNull(RideRequest::on('module_taxi')->find($first->id));
        $this->assertNull(RideRequest::on('module_taxi')->find($second->id));
        $this->assertNotNull(RideRequest::on('module_taxi')->find($kept->id));
    }

    #[Test]
    public function super_admin_with_tenant_cannot_bulk_delete_other_tenant_rides(): void
    {
        $company = $this->company('Royaal Taxi');
        $other = $this->company('Andere Taxi');
        $foreign = $this->ride($other->id, 'Andere tenant');

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->delete(route('admin.taxi.ride_requests.bulk-destroy'), [
                'ids' => [$foreign->id],
            ])
            ->assertRedirect(route('admin.taxi.ride_requests.index'));

        $this->assertNotNull(RideRequest::on('module_taxi')->find($foreign->id));
    }

    #[Test]
    public function company_admin_without_delete_permission_cannot_bulk_delete(): void
    {
        $company = $this->company();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('company-admin');
        $admin->givePermissionTo('rides.view');
        $ride = $this->ride($company->id, 'Beschermd');

        $this->actingAs($admin)
            ->delete(route('admin.taxi.ride_requests.bulk-destroy'), [
                'ids' => [$ride->id],
            ])
            ->assertForbidden();

        $this->assertNotNull(RideRequest::on('module_taxi')->find($ride->id));
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function company(string $name = 'Ritten Test'): Company
    {
        return Company::query()->create([
            'name' => $name.' '.uniqid(),
            'is_active' => true,
        ]);
    }

    private function ride(int $companyId, string $customerName): RideRequest
    {
        return RideRequest::on('module_taxi')->create([
            'company_id' => $companyId,
            'status' => RideRequest::STATUS_OFFERED,
            'pickup_at' => now(),
            'customer_name' => $customerName,
            'pickup_address' => 'A-straat 1, Enschede',
            'dropoff_address' => 'B-laan 2, Enschede',
        ]);
    }
}

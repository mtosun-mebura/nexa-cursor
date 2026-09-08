<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\Module;
use App\Models\Notification;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DefaultRate;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Services\TaxiTenantSetupService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiTenantSetupServiceTest extends TestCase
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

        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('person_range')->nullable();
            $table->boolean('active')->default(true);
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('default_rates', function (Blueprint $table) {
            $table->id();
            $table->string('person_range')->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->decimal('cleaning_costs', 10, 2)->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function status_requires_vehicles_and_rates_and_warns_about_mail(): void
    {
        [$company, $user] = $this->taxiCompanyWithAdmin();
        $service = app(TaxiTenantSetupService::class);

        $status = $service->status($company, $user);

        $this->assertTrue($status['applies']);
        $this->assertTrue($status['needs_attention']);
        $this->assertTrue($status['needs_login_prompt']);
        $this->assertFalse($status['booking_allowed']);
        $this->assertStringContainsString('voertuig', strtolower((string) $status['booking_block_message']));

        $byKey = collect($status['steps'])->keyBy('key');
        $this->assertFalse($byKey['vehicles']['done']);
        $this->assertFalse($byKey['rates']['done']);
        $this->assertFalse($byKey['mail']['done']);
        $this->assertTrue($byKey['mail']['warning']);
    }

    #[Test]
    public function booking_is_allowed_after_active_vehicle_exists(): void
    {
        [$company, $user] = $this->taxiCompanyWithAdmin();
        $service = app(TaxiTenantSetupService::class);

        Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Comfort 1',
            'type' => 'car',
            'person_range' => '1-4',
            'active' => true,
            'price_per_km' => 2.45,
            'base_fare' => 3.2,
        ]);
        DefaultRate::on('module_taxi')->create([
            'person_range' => '1-4',
            'base_fare' => 3.2,
            'price_per_km' => 2.45,
            'min_fare' => 0,
            'price_per_min' => 0,
        ]);

        $status = $service->status($company->fresh(), $user);

        $this->assertTrue($status['booking_allowed']);
        $this->assertFalse($status['needs_login_prompt']);
        $byKey = collect($status['steps'])->keyBy('key');
        $this->assertTrue($byKey['vehicles']['done']);
        $this->assertTrue($byKey['rates']['done']);
        $this->assertFalse($byKey['mail']['done']);
        $this->assertTrue($status['needs_attention']);
    }

    #[Test]
    public function sync_notification_creates_and_clears_when_complete(): void
    {
        [$company, $user] = $this->taxiCompanyWithAdmin();
        $service = app(TaxiTenantSetupService::class);

        $service->syncNotification($user, $company);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'type' => TaxiTenantSetupService::NOTIFICATION_TYPE,
        ]);

        Vehicle::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'Comfort 1',
            'active' => true,
            'price_per_km' => 2.5,
        ]);
        DefaultRate::on('module_taxi')->create([
            'person_range' => '1-4',
            'price_per_km' => 2.5,
        ]);
        GeneralSetting::set('MAIL_HOST', 'smtp.tenant.test', (int) $company->id);

        $service->syncNotification($user, $company->fresh());

        $this->assertNotNull(
            Notification::query()
                ->where('user_id', $user->id)
                ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
                ->whereNotNull('read_at')
                ->first()
        );
    }

    #[Test]
    public function sync_notification_does_not_recreate_after_read(): void
    {
        [$company, $user] = $this->taxiCompanyWithAdmin();
        $service = app(TaxiTenantSetupService::class);

        $service->syncNotification($user, $company);
        $service->syncNotification($user, $company);

        $this->assertSame(
            1,
            Notification::query()
                ->where('user_id', $user->id)
                ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
                ->count()
        );

        Notification::query()
            ->where('user_id', $user->id)
            ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
            ->update(['read_at' => now()]);

        $service->syncNotification($user, $company);

        $this->assertSame(
            1,
            Notification::query()
                ->where('user_id', $user->id)
                ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
                ->count()
        );
        $this->assertSame(
            0,
            Notification::query()
                ->where('user_id', $user->id)
                ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
                ->whereNull('read_at')
                ->count()
        );
    }

    #[Test]
    public function sync_notification_never_attaches_to_super_admin_and_stays_once_per_tenant(): void
    {
        [$company, $admin] = $this->taxiCompanyWithAdmin();
        $otherAdmin = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin-2-'.uniqid().'@example.com',
        ]);
        $super = User::factory()->create([
            'company_id' => null,
            'email' => 'super-'.uniqid().'@example.com',
        ]);
        $super->assignRole('super-admin');
        $service = app(TaxiTenantSetupService::class);

        $service->syncNotification($super, $company);
        $service->syncNotification($admin, $company);
        $service->syncNotification($otherAdmin, $company);
        $service->syncNotification($super, $company);

        $rows = Notification::query()
            ->where('company_id', $company->id)
            ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertNotSame($super->id, (int) $rows->first()->user_id);
        $this->assertContains((int) $rows->first()->user_id, [(int) $admin->id, (int) $otherAdmin->id]);
        $this->assertSame(
            0,
            Notification::query()
                ->where('user_id', $super->id)
                ->where('type', TaxiTenantSetupService::NOTIFICATION_TYPE)
                ->count()
        );
    }

    #[Test]
    public function booking_component_ids_are_recognized(): void
    {
        $service = app(TaxiTenantSetupService::class);

        $this->assertTrue($service->isBookingComponentId('taxi.boekingsmodule_v2'));
        $this->assertTrue($service->isBookingSectionKey('component:taxi.boekingsmodule_v2'));
        $this->assertFalse($service->isBookingComponentId('taxi.tarieven'));
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function taxiCompanyWithAdmin(): array
    {
        $module = Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'is_active' => true,
        ]);

        $company = Company::query()->create([
            'name' => 'Setup Taxi '.uniqid(),
            'is_active' => true,
            'email' => 'setup-'.uniqid().'@example.com',
        ]);
        $company->modules()->attach($module->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'admin-'.uniqid().'@example.com',
        ]);

        return [$company, $user];
    }
}

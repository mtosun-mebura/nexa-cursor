<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Services\TaxiAppPresenceService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaxiAppPresenceServiceTest extends TestCase
{
    private string $conn = 'module_taxi';

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->with('taxi');
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
        });

        Schema::connection($this->conn)->create('driver_availability', function (Blueprint $table) {
            $table->unsignedBigInteger('driver_id')->primary();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->boolean('is_online')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->conn)->create('transport_customer_portal_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('transport_customer_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('portal_role', 32);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    #[Test]
    public function chauffeur_online_follows_driver_availability(): void
    {
        $company = Company::query()->create(['name' => 'Presence Co', 'is_active' => true]);
        $online = User::factory()->create(['company_id' => $company->id]);
        $offline = User::factory()->create(['company_id' => $company->id]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $online->assignRole('chauffeur');
        $offline->assignRole('chauffeur');
        $admin->assignRole('company-admin');

        DriverAvailability::on($this->conn)->create([
            'driver_id' => $online->id,
            'company_id' => $company->id,
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
        DriverAvailability::on($this->conn)->create([
            'driver_id' => $offline->id,
            'company_id' => $company->id,
            'is_online' => false,
            'last_seen_at' => now()->subHour(),
        ]);

        $presence = $this->service()->forUsers([$online->fresh('roles'), $offline->fresh('roles'), $admin->fresh('roles')]);

        $this->assertTrue($presence[$online->id]['chauffeur']['applicable']);
        $this->assertTrue($presence[$online->id]['chauffeur']['online']);
        $this->assertTrue($presence[$offline->id]['chauffeur']['applicable']);
        $this->assertFalse($presence[$offline->id]['chauffeur']['online']);
        $this->assertFalse($presence[$admin->id]['chauffeur']['applicable']);
        $this->assertFalse($presence[$admin->id]['contract']['applicable']);
    }

    #[Test]
    public function contract_presence_is_applicable_without_online_status(): void
    {
        $company = Company::query()->create(['name' => 'Contract Co', 'is_active' => true]);
        $contractant = User::factory()->create(['company_id' => $company->id]);
        $contractant->assignRole('contractant');

        TransportCustomerPortalUser::on($this->conn)->create([
            'company_id' => $company->id,
            'user_id' => $contractant->id,
            'portal_role' => TransportCustomerPortalUser::ROLE_CONTRACTANT,
            'active' => true,
        ]);

        $presence = $this->service()->forUsers([$contractant->fresh('roles')]);

        $this->assertTrue($presence[$contractant->id]['contract']['applicable']);
        $this->assertFalse($presence[$contractant->id]['contract']['online']);
    }

    private function service(): TaxiAppPresenceService
    {
        return new TaxiAppPresenceService(
            app(ModuleDatabaseService::class),
            app(TaxiDriverEligibilityService::class),
        );
    }
}

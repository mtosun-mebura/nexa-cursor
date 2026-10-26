<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\TransportGroupRouteController;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContractDriverAssignmentRoleTest extends TestCase
{
    #[Test]
    #[Group('taxi')]
    public function assigning_a_driver_without_contractant_role_is_rejected(): void
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'chauffeur', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'contractant', 'guard_name' => 'web']);

        config([
            'module_database.strategy' => 'single',
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi');
        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
            $table->string('license_plate')->nullable();
            $table->timestamps();
        });

        $company = Company::query()->create([
            'name' => 'Taxi Royaal',
            'is_active' => true,
            'package_key' => 'business',
        ]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');
        $chauffeur = User::factory()->create([
            'company_id' => $company->id,
            'first_name' => 'Alleen',
            'last_name' => 'Chauffeur',
        ]);
        $chauffeur->assignRole('chauffeur');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'active' => true,
        ]);
        $contract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'name' => 'Schoolvervoer',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);
        $group = TransportGroup::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'name' => 'Heenweg',
            'destination_address' => 'OBS Roombeek',
            'destination_arrival_time' => '08:15:00',
            'active' => true,
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $request = Request::create('/admin/taxi/contract/toewijzing', 'PUT', [
            'driver_id' => $chauffeur->id,
            'vehicle_id' => null,
        ]);

        try {
            app(TransportGroupRouteController::class)->updateAssignment(
                $request,
                (int) $customer->id,
                (int) $contract->id,
                (int) $group->id
            );
            $this->fail('Toewijzen zonder contractant-rol hoort te falen.');
        } catch (ValidationException $e) {
            $this->assertSame(
                TaxiDriverEligibilityService::CONTRACT_ASSIGNMENT_DENIED,
                $e->errors()['driver_id'][0]
            );
        }
    }
}

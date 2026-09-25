<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\TransportCustomerController;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Services\TransportCustomerCascadeDeleteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransportCustomerDestroyCascadeTest extends TestCase
{
    private function bootTaxiModule(): Company
    {
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        config([
            'module_database.strategy' => 'single',
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi');

        if (! Route::has('admin.taxi.transport_customers.index')) {
            Route::middleware('web')
                ->get('/admin/taxi/contractklanten', static fn () => 'ok')
                ->name('admin.taxi.transport_customers.index');
            app('router')->getRoutes()->refreshNameLookups();
        }

        return Company::query()->create([
            'name' => 'Taxi Cascade Test',
            'is_active' => true,
            'package_key' => 'business',
        ]);
    }

    #[Test]
    #[Group('taxi')]
    public function destroy_archives_customer_and_keeps_contracts_and_passengers(): void
    {
        $company = $this->bootTaxiModule();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'school',
            'contact_name' => 'Directeur Mehmet',
            'active' => true,
        ]);

        $contract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'name' => 'Schoolvervoer Roombeek',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $passenger = TransportPassenger::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'first_name' => 'Lisa',
            'last_name' => 'Jansen',
            'pickup_address' => 'Schoolstraat 1',
            'active' => true,
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $pastOccurrence = TransportOccurrence::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'group',
            'scheduled_date' => now()->subDays(3)->toDateString(),
            'scheduled_at' => now()->subDays(3),
            'status' => 'planned',
        ]);
        $futureOccurrence = TransportOccurrence::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'group',
            'scheduled_date' => now()->addDays(3)->toDateString(),
            'scheduled_at' => now()->addDays(3),
            'status' => 'planned',
        ]);

        $response = app(TransportCustomerController::class)->destroy(
            Request::create('/admin/taxi/contractklanten/'.$customer->id, 'DELETE', [
                'keep_past_rides' => '1',
            ]),
            $customer->id,
            app(TransportCustomerCascadeDeleteService::class)
        );

        $this->assertTrue($response->isRedirect());
        $customer->refresh();
        $this->assertNotNull($customer->archived_at);
        $this->assertFalse($customer->active);
        $this->assertTrue($customer->archive_keep_past_rides);
        $this->assertNotNull(TransportContract::on('module_taxi')->find($contract->id));
        $this->assertNotNull(TransportPassenger::on('module_taxi')->find($passenger->id));
        $this->assertNotNull(TransportOccurrence::on('module_taxi')->find($pastOccurrence->id));
        $this->assertNull(TransportOccurrence::on('module_taxi')->find($futureOccurrence->id));
    }

    #[Test]
    #[Group('taxi')]
    public function destroy_without_keep_past_removes_all_occurrences(): void
    {
        $company = $this->bootTaxiModule();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'school',
            'contact_name' => 'Directeur Mehmet',
            'active' => true,
        ]);

        $contract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'name' => 'Schoolvervoer Roombeek',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $pastOccurrence = TransportOccurrence::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'group',
            'scheduled_date' => now()->subDays(2)->toDateString(),
            'scheduled_at' => now()->subDays(2),
            'status' => 'planned',
        ]);
        $futureOccurrence = TransportOccurrence::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'occurrence_type' => 'group',
            'scheduled_date' => now()->addDays(2)->toDateString(),
            'scheduled_at' => now()->addDays(2),
            'status' => 'planned',
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $response = app(TransportCustomerController::class)->destroy(
            Request::create('/admin/taxi/contractklanten/'.$customer->id, 'DELETE'),
            $customer->id,
            app(TransportCustomerCascadeDeleteService::class)
        );

        $this->assertTrue($response->isRedirect());
        $customer->refresh();
        $this->assertNotNull($customer->archived_at);
        $this->assertFalse($customer->archive_keep_past_rides);
        $this->assertNull(TransportOccurrence::on('module_taxi')->find($pastOccurrence->id));
        $this->assertNull(TransportOccurrence::on('module_taxi')->find($futureOccurrence->id));
    }

    #[Test]
    #[Group('taxi')]
    public function force_destroy_removes_archived_customer_contracts_and_passengers(): void
    {
        $company = $this->bootTaxiModule();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'school',
            'contact_name' => 'Directeur Mehmet',
            'active' => false,
            'archived_at' => now(),
        ]);

        $contract = TransportContract::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_customer_id' => $customer->id,
            'name' => 'Schoolvervoer Roombeek',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $passenger = TransportPassenger::on('module_taxi')->create([
            'company_id' => $company->id,
            'transport_contract_id' => $contract->id,
            'first_name' => 'Lisa',
            'last_name' => 'Jansen',
            'pickup_address' => 'Schoolstraat 1',
            'active' => true,
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $response = app(TransportCustomerController::class)
            ->forceDestroy($customer->id, app(TransportCustomerCascadeDeleteService::class));

        $this->assertTrue($response->isRedirect());
        $this->assertNull(TransportCustomer::on('module_taxi')->find($customer->id));
        $this->assertNull(TransportContract::on('module_taxi')->find($contract->id));
        $this->assertNull(TransportPassenger::on('module_taxi')->find($passenger->id));
    }

    #[Test]
    #[Group('taxi')]
    public function restore_reactivates_archived_customer(): void
    {
        $company = $this->bootTaxiModule();
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'school',
            'active' => false,
            'archived_at' => now(),
            'archive_keep_past_rides' => true,
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $response = app(TransportCustomerController::class)->restore($customer->id);

        $this->assertTrue($response->isRedirect());
        $customer->refresh();
        $this->assertNull($customer->archived_at);
        $this->assertTrue($customer->active);
        $this->assertFalse($customer->archive_keep_past_rides);
    }
}

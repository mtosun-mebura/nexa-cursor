<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\TransportCustomerController;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransportCustomerContractViewTest extends TestCase
{
    #[Test]
    #[Group('taxi')]
    public function contractklanten_index_renders_document_cards_with_organization_type(): void
    {
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
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

        if (! Route::has('admin.taxi.transport_customers.show')) {
            Route::middleware('web')
                ->get('/admin/taxi/contractklanten/{id}', static fn () => 'ok')
                ->name('admin.taxi.transport_customers.show');
            app('router')->getRoutes()->refreshNameLookups();
        }

        $company = Company::query()->create([
            'name' => 'Taxi Royaal Test',
            'is_active' => true,
            'package_key' => 'business',
        ]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $admin->assignRole('super-admin');

        $customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'school',
            'contact_name' => 'Directeur Mehmet',
            'contact_email' => 'membur+directeur@gmail.com',
            'billing_city' => 'Enschede',
            'active' => true,
        ]);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $response = app(TransportCustomerController::class)
            ->index(Request::create('/admin/taxi/contractklanten', 'GET'));

        $this->assertSame('taxi::admin.transport_customers.index', $response->name());
        $this->assertNull($response->getData()['packageDeniedMessage'] ?? null);
        $this->assertTrue($response->getData()['customers']->contains('name', 'O.B.S. Roombeek'));

        $html = view('taxi::admin.transport_customers.partials.contract-document-card', [
            'customer' => $customer,
            'carrierCompany' => $company,
            'latestContract' => null,
        ])->render();

        $this->assertStringContainsString('transport-contract-doc is-active', $html);
        $this->assertStringContainsString('Actief contract', $html);
        $this->assertStringContainsString('O.B.S. Roombeek', $html);
        $this->assertStringContainsString('School', $html);
        $this->assertStringContainsString('Opdrachtgever', $html);
        $this->assertStringContainsString('Taxi Royaal Test', $html);
        $this->assertStringContainsString('Directeur Mehmet', $html);
        $this->assertStringContainsString('membur+directeur@gmail.com', $html);
        $this->assertStringContainsString('Enschede', $html);
        $this->assertStringContainsString('Getekend', $html);
        $this->assertStringContainsString(
            route('admin.taxi.transport_customers.show', $customer->id, false),
            $html
        );
    }
}

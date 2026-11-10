<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnContractATest extends TestCase
{
    private Company $company;

    private TransportCustomer $customer;

    private TransportContract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        config([
            'module_database.strategy' => 'single',
            'database.connections.module_taxi' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);
        app(TaxiContractvervoerSchemaService::class)->ensureTablesExist('module_taxi');
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables('module_taxi');

        // Module-routes zijn in de testomgeving niet geladen (geen actieve modules in de DB).
        if (! Route::has('admin.taxi.transport_customers.index')) {
            Route::middleware(['web', 'admin'])
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            app('router')->getRoutes()->refreshNameLookups();
        }
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        $this->company = Company::query()->create([
            'name' => 'Taxi Locale Contract',
            'is_active' => true,
            'package_key' => 'business',
        ]);

        $this->customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'name' => 'O.B.S. Roombeek',
            'organization_type' => 'zorg',
            'contact_name' => 'Directeur Mehmet',
            'active' => true,
        ]);

        $this->contract = TransportContract::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'transport_customer_id' => $this->customer->id,
            'name' => 'Schoolvervoer Roombeek',
            'status' => 'paused',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 5,
            'payment_terms_days' => 14,
            'tax_rate' => 21,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function page(string $route, array $params = [], string $locale = 'en'): string
    {
        return $this->actingAs($this->admin())
            ->withSession(['selected_tenant' => $this->company->id, 'locale' => $locale])
            ->get(route($route, $params))
            ->assertOk()
            ->getContent();
    }

    #[Test]
    #[Group('taxi')]
    public function customers_index_is_english_in_en_locale(): void
    {
        $html = $this->page('admin.taxi.transport_customers.index');

        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('Clients, subscriptions, passengers and group rides.', $html);
        $this->assertStringContainsString('New customer', $html);
        $this->assertStringContainsString('Showing 1 to 1 of 1 customer', $html);
        $this->assertStringContainsString('Search by name, contact, type...', $html);
        $this->assertStringContainsString('Healthcare institution', $html);
        $this->assertStringContainsString('Official agreement', $html);
        $this->assertStringContainsString('Keep past rides visible in the schedule and calendar', $html);
        $this->assertStringContainsString('Are you sure you want to permanently delete the contract of :label?', $html);

        $this->assertStringNotContainsString('Opdrachtgevers, abonnementen', $html);
        $this->assertStringNotContainsString('Nieuwe klant', $html);
        $this->assertStringNotContainsString('Zoek op naam, contact', $html);
        $this->assertStringNotContainsString('Zorginstelling', $html);
        $this->assertStringNotContainsString('Officiële overeenkomst', $html);
        $this->assertStringNotContainsString('Weet je zeker dat je het contract', $html);
    }

    #[Test]
    #[Group('taxi')]
    public function customer_show_and_contract_show_are_english_in_en_locale(): void
    {
        $show = $this->page('admin.taxi.transport_customers.show', ['id' => $this->customer->id, 'section' => 'portaal']);

        $this->assertStringContainsString('Contract portal', $show);
        $this->assertStringContainsString('Add user', $show);
        $this->assertStringContainsString('Disruptions &amp; notifications', $show);
        $this->assertStringContainsString('Healthcare institution', $show);
        $this->assertStringContainsString('Choose an existing user.', $show);
        $this->assertStringContainsString('"placeholder":"Select a user"', html_entity_decode($show));
        $this->assertStringNotContainsString('Contractportaal', $show);
        $this->assertStringNotContainsString('Gebruiker toevoegen', $show);
        $this->assertStringNotContainsString('Selecteer een gebruiker', $show);
        $this->assertStringNotContainsString('Kies een bestaande gebruiker', $show);

        $contract = $this->page('admin.taxi.transport_customers.contract_show', [
            'customerId' => $this->customer->id,
            'contractId' => $this->contract->id,
        ]);

        $this->assertStringContainsString('Subscription details', $contract);
        $this->assertStringContainsString('Paused', $contract);
        $this->assertStringContainsString('Day 5 of the month', $contract);
        $this->assertStringContainsString('14 days', $contract);
        $this->assertStringContainsString('SEPA mandate (direct debit)', $contract);
        $this->assertStringContainsString('ongoing', $contract);
        $this->assertStringNotContainsString('Abonnementsgegevens', $contract);
        $this->assertStringNotContainsString('Gepauzeerd', $contract);
        $this->assertStringNotContainsString('van de maand', $contract);
        $this->assertStringNotContainsString('doorlopend', $contract);
    }

    #[Test]
    #[Group('taxi')]
    public function invoices_tab_and_flash_messages_are_english_in_en_locale(): void
    {
        $html = $this->actingAs($this->admin())
            ->withSession(['selected_tenant' => $this->company->id, 'locale' => 'en'])
            ->get(route('admin.taxi.transport_customers.contract_show', [
                'customerId' => $this->customer->id,
                'contractId' => $this->contract->id,
                'tab' => 'facturen',
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Generate monthly invoice', $html);
        $this->assertStringContainsString('Send immediately by email', $html);
        $this->assertStringContainsString('No invoices for this subscription yet.', $html);
        $this->assertStringContainsString('October', $html);
        $this->assertStringNotContainsString('Maandfactuur genereren', $html);
        $this->assertStringNotContainsString('Nog geen facturen', $html);
        $this->assertStringNotContainsString('oktober', $html);

        $this->actingAs($this->admin())
            ->withSession(['selected_tenant' => $this->company->id, 'locale' => 'en'])
            ->put(route('admin.taxi.transport_customers.update', $this->customer->id), [
                'name' => 'O.B.S. Roombeek',
                'organization_type' => 'school',
                'active' => 1,
            ])
            ->assertSessionHas('success', 'Contract customer saved.');
    }

    #[Test]
    #[Group('taxi')]
    public function dutch_locale_still_shows_dutch(): void
    {
        $index = $this->page('admin.taxi.transport_customers.index', [], 'nl');

        $this->assertSame('nl', app()->getLocale());
        $this->assertStringContainsString('Opdrachtgevers, abonnementen, passagiers en groepsritten.', $index);
        $this->assertStringContainsString('Nieuwe klant', $index);
        $this->assertStringContainsString('Toon 1 tot 1 van 1 klant', $index);
        $this->assertStringContainsString('Zorginstelling', $index);
        $this->assertStringContainsString('Officiële overeenkomst', $index);
        $this->assertStringNotContainsString('New customer', $index);

        $contract = $this->page('admin.taxi.transport_customers.contract_show', [
            'customerId' => $this->customer->id,
            'contractId' => $this->contract->id,
        ], 'nl');

        $this->assertStringContainsString('Abonnementsgegevens', $contract);
        $this->assertStringContainsString('5e van de maand', $contract);
        $this->assertStringContainsString('14 dagen', $contract);
        $this->assertStringContainsString('Gepauzeerd', $contract);
    }
}

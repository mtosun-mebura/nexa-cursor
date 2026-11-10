<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportScheduleException;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminLocaleEnContractBTest extends TestCase
{
    private Company $company;

    private TransportCustomer $customer;

    private TransportContract $contract;

    private TransportGroup $group;

    private function bootData(): User
    {
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
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

        if (! Schema::connection('module_taxi')->hasTable('transport_schedule_exceptions')) {
            Schema::connection('module_taxi')->create('transport_schedule_exceptions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('transport_contract_id')->nullable()->index();
                $table->date('exception_date');
                $table->string('name', 200);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::connection('module_taxi')->hasTable('vehicles')) {
            Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name')->nullable();
                $table->string('license_plate')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Module routes are not loaded in the test environment (no active modules in the DB).
        if (! Route::has('admin.taxi.transport_groups.index')) {
            Route::middleware(['web', 'admin'])
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            app('router')->getRoutes()->refreshNameLookups();
        }

        $this->company = Company::query()->create([
            'name' => 'Locale Contract Taxi',
            'is_active' => true,
            'package_key' => 'business',
        ]);

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('super-admin');

        $this->customer = TransportCustomer::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'name' => 'O.B.S. Locale',
            'organization_type' => 'school',
            'active' => true,
        ]);

        $this->contract = TransportContract::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'transport_customer_id' => $this->customer->id,
            'name' => 'Schoolvervoer Locale',
            'status' => 'active',
            'billing_model' => 'fixed_monthly',
            'monthly_amount' => 100,
            'invoice_day' => 1,
            'payment_terms_days' => 14,
            'tax_rate' => 0,
        ]);

        $passenger = TransportPassenger::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'transport_contract_id' => $this->contract->id,
            'first_name' => 'Lisa',
            'last_name' => 'Jansen',
            'pickup_address' => 'Schoolstraat 1',
            'active' => true,
        ]);

        $this->group = TransportGroup::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'transport_contract_id' => $this->contract->id,
            'name' => 'Ochtendgroep Locale',
            'destination_address' => 'Schoolplein 2',
            'destination_arrival_time' => '08:00',
            'active' => true,
        ]);

        TransportGroupMember::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'transport_group_id' => $this->group->id,
            'transport_passenger_id' => $passenger->id,
            'valid_from' => now()->subDay()->toDateString(),
        ]);

        TransportScheduleException::on('module_taxi')->create([
            'company_id' => $this->company->id,
            'exception_date' => now()->addDays(5)->toDateString(),
            'name' => 'Hemelvaartsdag',
            'active' => true,
        ]);

        return $user;
    }

    private function page(User $user, string $url, string $locale)
    {
        return $this->actingAs($user)
            ->withSession(['selected_tenant' => $this->company->id, 'locale' => $locale])
            ->get($url);
    }

    #[Test]
    #[Group('taxi')]
    public function passengers_and_exceptions_pages_render_in_english(): void
    {
        $user = $this->bootData();

        $html = $this->page($user, route('admin.taxi.transport_passengers.index', [$this->customer->id, $this->contract->id]), 'en')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('People who travel along on this subscription', $html);
        $this->assertStringContainsString('Showing 1 to 1 of 1 passengers', $html);
        $this->assertStringContainsString('Search name or address...', $html);
        $this->assertStringNotContainsString('Personen die meereizen op dit abonnement', $html);
        $this->assertStringNotContainsString('Toon 1 tot 1 van 1 passagiers', $html);

        $html = $this->page($user, route('admin.taxi.transport_schedule_exceptions.index'), 'en')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Holidays &amp; exceptions', $html);
        $this->assertStringContainsString('New exception day', $html);
        $this->assertStringContainsString('Entire company', $html);
        $this->assertStringContainsString('Are you sure you want to delete :label?', $html);
        $this->assertStringNotContainsString('Nieuwe uitzonderingsdag', $html);
        $this->assertStringNotContainsString('Hele bedrijf', $html);
    }

    #[Test]
    #[Group('taxi')]
    public function group_pages_planning_and_individual_form_render_in_english(): void
    {
        $user = $this->bootData();
        $ids = [$this->customer->id, $this->contract->id];

        $html = $this->page($user, route('admin.taxi.transport_groups.show', [...$ids, $this->group->id, 'tab' => 'leden']), 'en')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Members (1)', $html);
        $this->assertStringContainsString('Add members', $html);
        $this->assertStringContainsString('Passengers in this group', $html);
        $this->assertStringNotContainsString('Leden toevoegen', $html);
        $this->assertStringNotContainsString('Passagiers in deze groep', $html);

        $html = $this->page($user, route('admin.taxi.transport_groups.route.edit', [...$ids, $this->group->id]), 'en')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Route settings', $html);
        $this->assertStringContainsString('Fixed driver &amp; vehicle', $html);
        $this->assertStringContainsString('Select a driver or a vehicle.', $html);
        $this->assertStringContainsString('Press <strong>Calculate route</strong> to generate stops and times.', $html);
        $this->assertStringNotContainsString('Route-instellingen', $html);
        $this->assertStringNotContainsString('Vaste chauffeur', $html);

        $html = $this->page($user, route('admin.taxi.transport_individual_bookings.create', $ids), 'en')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('New individual contract ride', $html);
        $this->assertStringContainsString('Pick-up date and time', $html);
        $this->assertStringNotContainsString('Nieuwe individuele contractrit', $html);

        $html = $this->page($user, route('admin.taxi.transport_planning.index'), 'en')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Schedule overview', $html);
        $this->assertStringContainsString('← Previous week', $html);
        $this->assertStringNotContainsString('Planningsoverzicht', $html);
        $this->assertStringNotContainsString('Vorige week', $html);
    }

    #[Test]
    #[Group('taxi')]
    public function dutch_locale_keeps_dutch_output(): void
    {
        $user = $this->bootData();
        $ids = [$this->customer->id, $this->contract->id];

        $html = $this->page($user, route('admin.taxi.transport_passengers.index', $ids), 'nl')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Personen die meereizen op dit abonnement', $html);
        $this->assertStringContainsString('Toon 1 tot 1 van 1 passagiers', $html);
        $this->assertStringNotContainsString('People who travel along', $html);

        $html = $this->page($user, route('admin.taxi.transport_schedule_exceptions.index'), 'nl')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Feestdagen &amp; uitzonderingen', $html);
        $this->assertStringContainsString('Weet je zeker dat je :label wilt verwijderen?', $html);

        $html = $this->page($user, route('admin.taxi.transport_groups.route.edit', [...$ids, $this->group->id]), 'nl')
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Druk op <strong>Route berekenen</strong> om stops en tijden te genereren.', $html);
        $this->assertStringContainsString('Selecteer een chauffeur of een voertuig.', $html);
    }
}

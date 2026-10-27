<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DefaultRate;
use App\Modules\NexaTaxi\Support\DefaultRateSchema;
use App\Services\ModuleDatabaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Worker taxi_a: voertuigen, tarieven, chauffeurplanning-formulier en chauffeur dispatch in en/nl.
 */
class AdminLocaleEnTaxiATest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        $this->mock(ModuleDatabaseService::class, function ($mock): void {
            $mock->shouldReceive('ensureModuleStorageReady')->andReturnNull();
            $mock->shouldReceive('getModuleConnectionName')->with('taxi')->andReturn('module_taxi');
            $mock->shouldReceive('supportsModuleDatabases')->andReturn(true);
        });

        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        Schema::connection('module_taxi')->create('default_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('person_range')->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('min_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('price_per_min', 10, 2)->nullable();
            $table->decimal('cleaning_costs', 10, 2)->nullable();
            $table->decimal('evening_night_multiplier', 4, 2)->default(1.20);
            $table->unsignedTinyInteger('evening_night_from_hour')->default(22);
            $table->unsignedTinyInteger('evening_night_until_hour')->default(6);
            $table->timestamps();
        });
        Schema::connection('module_taxi')->create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name')->nullable();
            $table->string('license_plate')->nullable();
            $table->string('type')->nullable();
            $table->string('person_range')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DefaultRateSchema::resetCache();
        DefaultRate::resetCompanyIdColumnCache();

        if (! Route::has('admin.taxi.tarieven.edit')) {
            Route::middleware('web')
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            Route::getRoutes()->refreshNameLookups();
        }

        DefaultRate::on('module_taxi')->create([
            'person_range' => '1-4',
            'base_fare' => 3.2,
            'min_fare' => 0,
            'price_per_km' => 2.45,
            'price_per_min' => 0.4,
        ]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    private function tenantSession(string $locale): array
    {
        $tenant = Company::query()->create(['name' => 'Taxi A Co', 'is_active' => true]);

        return ['selected_tenant' => $tenant->id, 'locale' => $locale];
    }

    #[Test]
    public function tarieven_page_is_english_in_en(): void
    {
        $html = $this->actingAs($this->superAdmin())
            ->withSession($this->tenantSession('en'))
            ->get(route('admin.taxi.tarieven.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Evening/night surcharge', $html);
        $this->assertStringContainsString('Surcharge factor', $html);
        $this->assertStringContainsString('Add passenger range', $html);
        $this->assertStringContainsString('Save rates', $html);
        $this->assertStringContainsString('Default rates of', $html);
        $this->assertStringContainsString('At least 1 passenger range is required.', $html);
        $this->assertStringNotContainsString('Toeslagfactor', $html);
        $this->assertStringNotContainsString('Personenbereik toevoegen', $html);
        $this->assertStringNotContainsString('Minimaal 1 personenbereik', $html);
    }

    #[Test]
    public function vehicle_create_page_and_validation_messages_are_english_in_en(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('en');

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.taxi.vehicles.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Vehicle details', $html);
        $this->assertStringContainsString('License plate *', $html);
        $this->assertStringContainsString('Passenger range *', $html);
        $this->assertStringContainsString('Save vehicle', $html);
        $this->assertStringContainsString('up to 4 passengers', $html);
        $this->assertStringContainsString('Optional. JPG, PNG or WebP, max. 5MB.', $html);
        $this->assertStringContainsString('Upload failed', $html);
        $this->assertStringNotContainsString('Kenteken', $html);
        $this->assertStringNotContainsString('Voertuig opslaan', $html);

        $this->actingAs($admin)
            ->withSession($session)
            ->post(route('admin.taxi.vehicles.store'), ['type' => 'car', 'person_range' => '1-4'])
            ->assertSessionHasErrors(['name', 'license_plate']);

        $errors = session('errors');
        $this->assertSame('Enter a name for the vehicle.', $errors->first('name'));
        $this->assertSame('Enter a license plate.', $errors->first('license_plate'));
    }

    #[Test]
    public function nl_remains_dutch(): void
    {
        $admin = $this->superAdmin();
        $session = $this->tenantSession('nl');

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.taxi.tarieven.edit'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Toeslagfactor', $html);
        $this->assertStringContainsString('Personenbereik toevoegen', $html);
        $this->assertStringContainsString('Tarieven opslaan', $html);
        $this->assertStringNotContainsString('Surcharge factor', $html);

        $html = $this->actingAs($admin)
            ->withSession($session)
            ->get(route('admin.taxi.vehicles.create'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Voertuiggegevens', $html);
        $this->assertStringContainsString('Kenteken *', $html);
        $this->assertStringContainsString('t/m 4 personen', $html);
        $this->assertStringContainsString('Voertuig opslaan', $html);
        $this->assertStringNotContainsString('Vehicle details', $html);

        $this->actingAs($admin)
            ->withSession($session)
            ->post(route('admin.taxi.vehicles.store'), ['type' => 'car', 'person_range' => '1-4'])
            ->assertSessionHasErrors(['name', 'license_plate']);
        $this->assertSame('Vul een naam in voor het voertuig.', session('errors')->first('name'));
    }

    #[Test]
    public function dispatch_settings_and_driver_schedule_form_are_english_in_en_and_dutch_in_nl(): void
    {
        $admin = $this->superAdmin();

        $en = $this->actingAs($admin)
            ->withSession($this->tenantSession('en'))
            ->get(route('admin.taxi.dispatch_settings.edit'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Acceptance timer', $en);
        $this->assertStringContainsString('Acceptance time (minutes)', $en);
        $this->assertStringContainsString('Booking notifications', $en);
        $this->assertStringContainsString('Payments (Mollie)', $en);
        $this->assertStringContainsString('Between 1 and', $en);
        $this->assertStringContainsString('{{ CODE_EXPIRES_MINUTES }}', $en);
        $this->assertStringNotContainsString('Acceptatietimer</', $en);
        $this->assertStringNotContainsString('Boekingsmeldingen</', $en);

        $nl = $this->actingAs($admin)
            ->withSession($this->tenantSession('nl'))
            ->get(route('admin.taxi.dispatch_settings.edit'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Acceptatietijd (minuten)', $nl);
        $this->assertStringContainsString('Betalingen (Mollie)', $nl);
        $this->assertStringContainsString('{{ CODE_EXPIRES_MINUTES }}', $nl);
        $this->assertStringNotContainsString('Acceptance time (minutes)', $nl);

        $form = $this->actingAs($admin)
            ->withSession($this->tenantSession('en'))
            ->get(route('admin.taxi.driver_schedules.create'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('Schedule shift', $form);
        $this->assertStringContainsString('Choose driver', $form);
        $this->assertStringContainsString('Limit with an end date', $form);
        $this->assertStringNotContainsString('Dienst inplannen', $form);
        $this->assertStringNotContainsString('Kies chauffeur', $form);
    }
}

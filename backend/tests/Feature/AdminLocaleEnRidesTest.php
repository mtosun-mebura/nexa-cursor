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

class AdminLocaleEnRidesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'rides.delete', 'guard_name' => 'web']);

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
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        if (! Route::has('admin.taxi.ride_requests.index')) {
            Route::middleware('web')
                ->prefix('admin/taxi')
                ->name('admin.taxi.')
                ->group(app_path('Modules/NexaTaxi/Routes/web.php'));
            Route::getRoutes()->refreshNameLookups();
        }
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function company(): Company
    {
        return Company::query()->create(['name' => 'Ritten Locale '.uniqid(), 'is_active' => true]);
    }

    private function ride(int $companyId): RideRequest
    {
        return RideRequest::on('module_taxi')->create([
            'company_id' => $companyId,
            'status' => RideRequest::STATUS_PENDING_DISPATCH,
            'pickup_at' => now(),
            'customer_name' => 'Jan Jansen',
            'pickup_address' => 'A-straat 1, Enschede',
            'dropoff_address' => 'B-laan 2, Enschede',
        ]);
    }

    #[Test]
    public function rides_index_is_english_when_locale_is_en(): void
    {
        $company = $this->company();
        $this->ride($company->id);

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => 'en'])
            ->get(route('admin.taxi.ride_requests.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('Rides overview', $html);
        $this->assertStringContainsString('All statuses', $html);
        $this->assertStringContainsString('Date/time', $html);
        $this->assertStringContainsString('Delete selected rides', $html);
        $this->assertStringContainsString('Are you sure you want to delete the selected rides? This cannot be undone.', $html);
        $this->assertStringContainsString('Completed rides', $html);
        $this->assertStringContainsString('Open / offered', $html);
        $this->assertStringContainsString('Payment mix', $html);
        $this->assertStringContainsString('Awaiting driver', $html); // status label via call-site __()
        $this->assertStringContainsString('1-1 of 1', $html);
        $this->assertStringContainsString('vs ', $html);

        $this->assertStringNotContainsString('Overzicht ritten', $html);
        $this->assertStringNotContainsString('Alle statussen', $html);
        $this->assertStringNotContainsString('Wacht op chauffeur', $html);
        $this->assertStringNotContainsString('Voltooide ritten', $html);
        $this->assertStringNotContainsString('Betaalmix', $html);
        $this->assertStringNotContainsString('Weet je zeker', $html);
    }

    #[Test]
    public function notification_log_page_is_english_when_locale_is_en(): void
    {
        $company = $this->company();
        $ride = $this->ride($company->id);

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => 'en'])
            ->get(route('admin.taxi.ride_requests.notification_log', $ride))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Notification log', $html);
        $this->assertStringContainsString('Back to ride', $html);
        $this->assertStringContainsString('Sent notifications', $html);
        $this->assertStringContainsString('No log data available.', $html);
        $this->assertStringContainsString('Overview of WhatsApp and email notifications for ride #'.$ride->id, $html);

        $this->assertStringNotContainsString('Terug naar rit', $html);
        $this->assertStringNotContainsString('Verzonden meldingen', $html);
        $this->assertStringNotContainsString('Geen loggegevens beschikbaar.', $html);
    }

    #[Test]
    public function rides_index_and_flash_messages_stay_dutch_for_nl(): void
    {
        $company = $this->company();
        $ride = $this->ride($company->id);

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.taxi.ride_requests.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Overzicht ritten', $html);
        $this->assertStringContainsString('Alle statussen', $html);
        $this->assertStringContainsString('Wacht op chauffeur', $html);
        $this->assertStringContainsString('Voltooide ritten', $html);
        $this->assertStringContainsString('Weet je zeker dat je de geselecteerde ritten wilt verwijderen? Dit kan niet ongedaan worden gemaakt.', $html);
        $this->assertStringNotContainsString('Rides overview', $html);

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->delete(route('admin.taxi.ride_requests.bulk-destroy'), ['ids' => [$ride->id]])
            ->assertSessionHas('success', '1 rit succesvol verwijderd.');
    }

    #[Test]
    public function bulk_delete_flash_message_is_english_when_locale_is_en(): void
    {
        $company = $this->company();
        $a = $this->ride($company->id);
        $b = $this->ride($company->id);

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => 'en'])
            ->delete(route('admin.taxi.ride_requests.bulk-destroy'), ['ids' => [$a->id, $b->id]])
            ->assertSessionHas('success', '2 rides deleted successfully.');
    }

    #[Test]
    public function ride_show_page_is_english_when_locale_is_en(): void
    {
        $company = $this->company();
        $ride = $this->ride($company->id);

        $html = $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id, 'locale' => 'en'])
            ->get(route('admin.taxi.ride_requests.show', $ride))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Route &amp; date', $html);
        $this->assertStringContainsString('Customer &amp; assignment', $html);
        $this->assertStringContainsString('Pick-up:', $html);
        $this->assertStringContainsString('Drop-off:', $html);
        $this->assertStringContainsString('Passengers:', $html);
        $this->assertStringContainsString('Awaiting driver', $html);

        $this->assertStringNotContainsString('Klant &amp; toewijzing', $html);
        $this->assertStringNotContainsString('>Ophalen:<', $html);
        $this->assertStringNotContainsString('>Passagiers:<', $html);
        $this->assertStringNotContainsString('>Wacht op chauffeur<', $html);
    }
}

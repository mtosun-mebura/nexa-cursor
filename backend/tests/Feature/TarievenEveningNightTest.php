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
use Tests\TestCase;

class TarievenEveningNightTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
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
            'evening_night_multiplier' => 1.2,
            'evening_night_from_hour' => 22,
            'evening_night_until_hour' => 6,
        ]);
        DefaultRate::on('module_taxi')->create([
            'person_range' => '5-8',
            'base_fare' => 5,
            'min_fare' => 0,
            'price_per_km' => 2.8,
            'price_per_min' => 0.5,
            'evening_night_multiplier' => 1.2,
            'evening_night_from_hour' => 22,
            'evening_night_until_hour' => 6,
        ]);
    }

    #[Test]
    public function tarieven_page_shows_evening_night_fields(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $this->company()->id])
            ->get(route('admin.taxi.tarieven.edit'))
            ->assertOk()
            ->assertSee('Avond/nacht toeslag', false)
            ->assertSee('Toeslagfactor', false)
            ->assertSee('name="evening_night_multiplier"', false)
            ->assertSee('name="evening_night_from_hour"', false)
            ->assertSee('admin-field-fit', false)
            ->assertDontSee('evening_night_from_hour" class="kt-select w-full', false);
    }

    #[Test]
    public function tarieven_page_is_editable_for_nexa_suite_without_tenant(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => null])
            ->get(route('admin.taxi.tarieven.edit'))
            ->assertOk()
            ->assertSee('NEXA Suite-website', false)
            ->assertSee('Alle tenants', false)
            ->assertSee('Avond/nacht toeslag', false)
            ->assertSee('Toeslagfactor', false);
    }

    #[Test]
    public function evening_night_surcharge_is_saved_on_all_rate_rows(): void
    {
        $company = $this->company();

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->put(route('admin.taxi.tarieven.update'), [
                'evening_night_multiplier' => '1.35',
                'evening_night_from_hour' => '21',
                'evening_night_until_hour' => '5',
                'rates' => [
                    [
                        'person_range' => '1-4',
                        'base_fare' => '3.20',
                        'min_fare' => '0',
                        'price_per_km' => '2.45',
                        'price_per_min' => '0.40',
                    ],
                    [
                        'person_range' => '5-8',
                        'base_fare' => '5.00',
                        'min_fare' => '0',
                        'price_per_km' => '2.80',
                        'price_per_min' => '0.50',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.taxi.tarieven.edit'));

        foreach (['1-4', '5-8'] as $range) {
            $rate = DefaultRate::queryForCompany('module_taxi', $company->id)->where('person_range', $range)->first();
            $this->assertNotNull($rate);
            $this->assertEquals(1.35, (float) $rate->evening_night_multiplier);
            $this->assertSame(21, (int) $rate->evening_night_from_hour);
            $this->assertSame(5, (int) $rate->evening_night_until_hour);
        }

        $platform = DefaultRate::queryForCompany('module_taxi', null)->where('person_range', '1-4')->first();
        $this->assertNotNull($platform);
        $this->assertEquals(1.2, (float) $platform->evening_night_multiplier);
    }

    #[Test]
    public function nexa_suite_save_updates_platform_rates(): void
    {
        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => null])
            ->put(route('admin.taxi.tarieven.update'), [
                'evening_night_multiplier' => '1.40',
                'evening_night_from_hour' => '20',
                'evening_night_until_hour' => '7',
                'rates' => [
                    [
                        'person_range' => '1-4',
                        'base_fare' => '4.00',
                        'min_fare' => '0',
                        'price_per_km' => '2.50',
                        'price_per_min' => '0.40',
                    ],
                    [
                        'person_range' => '5-8',
                        'base_fare' => '6.00',
                        'min_fare' => '0',
                        'price_per_km' => '3.00',
                        'price_per_min' => '0.50',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.taxi.tarieven.edit'));

        $platform = DefaultRate::queryForCompany('module_taxi', null)->where('person_range', '1-4')->first();
        $this->assertNotNull($platform);
        $this->assertEquals(4.0, (float) $platform->base_fare);
        $this->assertEquals(1.4, (float) $platform->evening_night_multiplier);
        $this->assertNull($platform->company_id);
    }

    #[Test]
    public function evening_night_surcharge_is_saved_when_columns_were_missing(): void
    {
        DefaultRateSchema::resetCache();
        DefaultRate::resetCompanyIdColumnCache();

        Schema::connection('module_taxi')->table('default_rates', function (Blueprint $table) {
            $table->dropColumn([
                'evening_night_multiplier',
                'evening_night_from_hour',
                'evening_night_until_hour',
            ]);
        });

        $company = $this->company();

        $this->actingAs($this->superAdmin())
            ->withSession(['selected_tenant' => $company->id])
            ->put(route('admin.taxi.tarieven.update'), [
                'evening_night_multiplier' => '1.25',
                'evening_night_from_hour' => '22',
                'evening_night_until_hour' => '6',
                'rates' => [
                    [
                        'person_range' => '1-4',
                        'base_fare' => '3.20',
                        'min_fare' => '0',
                        'price_per_km' => '2.45',
                        'price_per_min' => '0.40',
                    ],
                    [
                        'person_range' => '5-8',
                        'base_fare' => '5.00',
                        'min_fare' => '0',
                        'price_per_km' => '2.80',
                        'price_per_min' => '0.50',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.taxi.tarieven.edit'));

        $this->assertTrue(Schema::connection('module_taxi')->hasColumn('default_rates', 'evening_night_multiplier'));

        $rate = DefaultRate::queryForCompany('module_taxi', $company->id)->where('person_range', '1-4')->first();
        $this->assertNotNull($rate);
        $this->assertEquals(1.25, (float) $rate->evening_night_multiplier);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function company(): Company
    {
        return Company::query()->create([
            'name' => 'Tarieven Test '.uniqid(),
            'is_active' => true,
        ]);
    }
}

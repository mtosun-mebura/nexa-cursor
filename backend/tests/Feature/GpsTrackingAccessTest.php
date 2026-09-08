<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Admin\GpsTrackingController;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use App\Support\TenantPackageAddon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GpsTrackingAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));

        $gpsRoutes = [
            'admin.taxi.gps_tracking.index' => ['get', '/admin/taxi/gps-tracker', 'index'],
            'admin.taxi.gps_tracking.positions' => ['get', '/admin/taxi/gps-tracker/posities', 'positions'],
            'admin.taxi.gps_tracking.unlock' => ['post', '/admin/taxi/gps-tracker/offline-ontgrendelen', 'unlock'],
            'admin.taxi.gps_tracking.lock' => ['post', '/admin/taxi/gps-tracker/offline-verbergen', 'lock'],
            'admin.taxi.gps_tracking.demo' => ['post', '/admin/taxi/gps-tracker/demo', 'demo'],
            'admin.taxi.gps_tracking.code' => ['put', '/admin/taxi/gps-tracker/veiligheidscode', 'updateCode'],
            'admin.taxi.gps_tracking.settings' => ['get', '/admin/taxi/gps-tracker/configuratie', 'settings'],
            'admin.taxi.gps_tracking.settings.update' => ['put', '/admin/taxi/gps-tracker/configuratie', 'updateSettings'],
        ];
        foreach ($gpsRoutes as $name => [$method, $uri, $action]) {
            if (! Route::has($name)) {
                Route::{$method}($uri, [GpsTrackingController::class, $action])
                    ->middleware('web')
                    ->name($name);
            }
        }
        Route::getRoutes()->refreshNameLookups();
    }

    #[Test]
    public function gps_page_is_blocked_without_the_addon(): void
    {
        $company = Company::query()->create([
            'name' => 'Start GPS',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        try {
            app(GpsTrackingController::class)->index();
            $this->fail('GPS zonder module had geblokkeerd moeten worden.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('GPS-trackers', $e->errors()['package'][0] ?? '');
        }
    }

    #[Test]
    public function gps_page_opens_when_the_addon_is_active(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro GPS',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $view = app(GpsTrackingController::class)->index();
        $this->assertSame('taxi::admin.gps-tracking.index', $view->name());
        $this->assertFalse($view->getData()['noTenantSelected']);
        $this->assertTrue($view->getData()['canManageCode']);
        $this->assertTrue($view->getData()['canUseLiveDemo']);
        $this->assertFalse($view->getData()['liveDemoEnabled']);
        $this->assertSame(1, $view->getData()['appearance']['refresh_seconds']);
    }

    #[Test]
    public function gps_page_opens_during_trial_without_the_addon(): void
    {
        $company = Company::query()->create([
            'name' => 'Trial GPS open',
            'is_active' => true,
            'package_key' => 'start',
        ]);
        app(\App\Services\PlatformBilling\TenantSubscriptionService::class)->ensureProfile($company);
        $company->billingProfile->update([
            'trial_started_at' => now()->toDateString(),
            'trial_ends_at' => now()->addMonth()->toDateString(),
            'subscription_start_date' => now()->addMonth()->toDateString(),
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $view = app(GpsTrackingController::class)->index();
        $this->assertSame('taxi::admin.gps-tracking.index', $view->name());
    }

    #[Test]
    public function gps_positions_in_the_browser_without_tenant_goes_to_the_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.taxi.gps_tracking.positions', ['view' => 'online']))
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function gps_positions_json_without_tenant_still_returns_unprocessable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->getJson(route('admin.taxi.gps_tracking.positions', ['view' => 'online']))
            ->assertUnprocessable()
            ->assertJson(['message' => 'Selecteer eerst een bedrijf.']);
    }

    #[Test]
    public function gps_settings_page_opens_when_the_addon_is_active(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro GPS settings',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $view = app(GpsTrackingController::class)->settings();
        $this->assertSame('taxi::admin.gps-tracking.settings', $view->name());
        $this->assertSame('sedan', $view->getData()['appearance']['car_style']);
        $this->assertIsArray($view->getData()['fleet']);
    }

    #[Test]
    public function gps_settings_page_autosaves_and_has_no_save_button(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro GPS autosave',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $html = $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.taxi.gps_tracking.settings'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Wijzigingen worden automatisch opgeslagen.', $html);
        $this->assertStringNotContainsString('>Opslaan</button>', $html);
        $this->assertStringContainsString('id="gps-refresh-seconds"', $html);
        $this->assertStringContainsString('w-20', $html);
        $this->assertStringContainsString('maxlength="4"', $html);
    }

    #[Test]
    public function gps_settings_json_save_stores_color_and_refresh_seconds(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro GPS json save',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->putJson(route('admin.taxi.gps_tracking.settings.update'), [
                'car_color_mode' => 'single',
                'type_colors' => [
                    'sedan' => '#1d4ed8',
                    'van' => '#ea580c',
                    'bus' => '#15803d',
                ],
                'plate_background' => '#f7e125',
                'plate_text_color' => '#111827',
                'plate_border_color' => '#111827',
                'refresh_seconds' => 5,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $appearance = app(TaxiGpsTrackingSettingsService::class)->appearance((int) $company->id);
        $this->assertSame(5, $appearance['refresh_seconds']);
        $this->assertSame('#1d4ed8', $appearance['type_colors']['sedan']);
        $this->assertSame('#15803d', $appearance['type_colors']['bus']);
    }

    #[Test]
    public function gps_code_must_be_four_to_eight_digits(): void
    {
        $this->gpsActingAsSuperAdmin();

        $this->from(route('admin.taxi.gps_tracking.index'))
            ->put(route('admin.taxi.gps_tracking.code'), [
                'code' => '12ab',
                'code_confirmation' => '12ab',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['code', 'code_confirmation']);

        $this->assertSame(
            'De code bestaat uit 4 tot 8 cijfers.',
            session('errors')->first('code')
        );
    }

    #[Test]
    public function gps_code_confirmation_must_match_the_new_code(): void
    {
        $this->gpsActingAsSuperAdmin();

        $this->from(route('admin.taxi.gps_tracking.index'))
            ->put(route('admin.taxi.gps_tracking.code'), [
                'code' => '48291',
                'code_confirmation' => '48292',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['code_confirmation']);

        $this->assertSame(
            'De opgegeven nieuwe codes komen niet overeen.',
            session('errors')->first('code_confirmation')
        );
        $this->assertFalse(session('errors')->has('code'));
    }

    #[Test]
    public function super_admin_can_toggle_live_map_demo(): void
    {
        $this->gpsActingAsSuperAdmin();

        $this->postJson(route('admin.taxi.gps_tracking.demo'), ['enabled' => true])
            ->assertOk()
            ->assertJson(['enabled' => true]);

        $companyId = (int) session('selected_tenant');
        $this->assertTrue((bool) session('gps_live_map_demo.'.$companyId));

        $this->postJson(route('admin.taxi.gps_tracking.demo'), ['enabled' => false])
            ->assertOk()
            ->assertJson(['enabled' => false]);
        $this->assertFalse((bool) session('gps_live_map_demo.'.$companyId));
    }

    #[Test]
    public function tenant_admin_cannot_toggle_live_map_demo(): void
    {
        $company = Company::query()->create([
            'name' => 'Tenant GPS',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create(['company_id' => $company->id]);
        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'vehicles.view',
            'guard_name' => 'web',
        ]);
        $admin->givePermissionTo($permission);

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);

        $this->postJson(route('admin.taxi.gps_tracking.demo'), ['enabled' => true])
            ->assertForbidden();
    }

    private function gpsActingAsSuperAdmin(): void
    {
        $company = Company::query()->create([
            'name' => 'Pro GPS code',
            'is_active' => true,
            'package_key' => 'pro',
            'package_addons' => [TenantPackageAddon::GPS_TRACKING => 1],
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        session(['selected_tenant' => $company->id]);
    }
}

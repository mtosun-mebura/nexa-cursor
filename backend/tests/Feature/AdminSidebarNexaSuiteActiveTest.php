<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Configuraties → Nexa Suite (klant-app) and Betalingen → NEXA Suite ritten share
 * the admin.nexa-suite-bookings.* route prefix; only the matching item may be active.
 */
class AdminSidebarNexaSuiteActiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function setCurrentRouteName(string $name): void
    {
        $request = Request::create('/', 'GET');
        $route = (new Route(['GET'], '/', fn () => null))->name($name);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);
    }

    private function renderSidebarAsSuperAdmin(): string
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $this->actingAs($user);

        return view('admin.layouts.partials.sidebar')->render();
    }

    private function menuItemIsActiveForHref(string $html, string $href): bool
    {
        return (bool) preg_match(
            '/<div class="kt-menu-item\s+active"[^>]*>\s*<a[^>]*href="'.preg_quote($href, '/').'"/',
            $html
        );
    }

    #[Test]
    public function route_is_active_excludes_more_specific_settings_prefix(): void
    {
        $this->setCurrentRouteName('admin.nexa-suite-bookings.settings');

        $this->assertFalse(MenuService::routeIsActive(
            'admin.nexa-suite-bookings.*',
            'admin.nexa-suite-bookings.settings*'
        ));
        $this->assertTrue(MenuService::routeIsActive('admin.nexa-suite-bookings.settings*'));
    }

    #[Test]
    public function route_is_active_keeps_ritten_routes_without_settings(): void
    {
        $this->setCurrentRouteName('admin.nexa-suite-bookings.index');

        $this->assertTrue(MenuService::routeIsActive(
            'admin.nexa-suite-bookings.*',
            'admin.nexa-suite-bookings.settings*'
        ));
        $this->assertFalse(MenuService::routeIsActive('admin.nexa-suite-bookings.settings*'));

        $this->setCurrentRouteName('admin.nexa-suite-bookings.invoices.show');
        $this->assertTrue(MenuService::routeIsActive(
            'admin.nexa-suite-bookings.*',
            'admin.nexa-suite-bookings.settings*'
        ));
    }

    #[Test]
    public function settings_page_activates_only_klant_app_menu_item(): void
    {
        $this->setCurrentRouteName('admin.nexa-suite-bookings.settings');
        $html = $this->renderSidebarAsSuperAdmin();

        $settingsHref = route('admin.nexa-suite-bookings.settings');
        $rittenHref = route('admin.nexa-suite-bookings.index');

        $this->assertTrue($this->menuItemIsActiveForHref($html, $settingsHref));
        $this->assertFalse($this->menuItemIsActiveForHref($html, $rittenHref));
    }

    #[Test]
    public function ritten_page_activates_only_nexa_suite_ritten_menu_item(): void
    {
        $this->setCurrentRouteName('admin.nexa-suite-bookings.index');
        $html = $this->renderSidebarAsSuperAdmin();

        $settingsHref = route('admin.nexa-suite-bookings.settings');
        $rittenHref = route('admin.nexa-suite-bookings.index');

        $this->assertTrue($this->menuItemIsActiveForHref($html, $rittenHref));
        $this->assertFalse($this->menuItemIsActiveForHref($html, $settingsHref));
    }
}

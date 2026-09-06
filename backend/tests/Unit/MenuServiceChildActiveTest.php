<?php

namespace Tests\Unit;

use App\Services\MenuService;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuServiceChildActiveTest extends TestCase
{
    /**
     * @return list<array{title: string, route: string}>
     */
    private function gpsChildren(): array
    {
        return [
            ['title' => 'Voertuigen', 'route' => 'admin.taxi.gps_tracking.index'],
            ['title' => 'Configuratie', 'route' => 'admin.taxi.gps_tracking.settings'],
        ];
    }

    private function setCurrentRouteName(string $name): void
    {
        $request = Request::create('/', 'GET');
        $route = (new Route(['GET'], '/', fn () => null))->name($name);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);
    }

    #[Test]
    public function gps_settings_does_not_also_activate_vehicles(): void
    {
        $children = $this->gpsChildren();
        $this->setCurrentRouteName('admin.taxi.gps_tracking.settings');

        $this->assertFalse(MenuService::childMenuItemIsActive($children[0], $children));
        $this->assertTrue(MenuService::childMenuItemIsActive($children[1], $children));
    }

    #[Test]
    public function gps_map_activates_vehicles_not_settings(): void
    {
        $children = $this->gpsChildren();
        $this->setCurrentRouteName('admin.taxi.gps_tracking.index');

        $this->assertTrue(MenuService::childMenuItemIsActive($children[0], $children));
        $this->assertFalse(MenuService::childMenuItemIsActive($children[1], $children));
    }

    #[Test]
    public function gps_related_routes_stay_on_vehicles(): void
    {
        $children = $this->gpsChildren();
        $this->setCurrentRouteName('admin.taxi.gps_tracking.positions');

        $this->assertTrue(MenuService::childMenuItemIsActive($children[0], $children));
        $this->assertFalse(MenuService::childMenuItemIsActive($children[1], $children));
    }
}

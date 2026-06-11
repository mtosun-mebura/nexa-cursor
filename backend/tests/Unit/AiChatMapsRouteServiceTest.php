<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatMapsRouteService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiChatMapsRouteServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_resolve_route_uses_coordinates_without_geocoding(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [[
                    'distance' => 145000,
                    'duration' => 5400,
                    'geometry' => '_p~iF~ps|U_ulLnnqC_mqNvxq`@',
                ]],
            ], 200),
        ]);

        $service = new AiChatMapsRouteService();
        $route = $service->resolveRoute(
            'Enschede, Nederland',
            'Schiphol, Nederland',
            ['lat' => 52.2215, 'lng' => 6.8937],
            ['lat' => 52.3105, 'lng' => 4.7683],
        );

        $this->assertNotNull($route);
        $this->assertSame(145000, $route['distance_meters']);
        $this->assertSame(5400, $route['duration_seconds']);
        $this->assertSame('_p~iF~ps|U_ulLnnqC_mqNvxq`@', $route['polyline']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '6.893700,52.221500')
                && str_contains($request->url(), '4.768300,52.310500');
        });
    }

    public function test_resolve_route_uses_google_place_id_before_text_geocoding(): void
    {
        config(['maps.api_key' => 'test-google-key']);

        Http::fake([
            'maps.googleapis.com/maps/api/place/details/json*' => Http::response([
                'status' => 'OK',
                'result' => [
                    'formatted_address' => 'Amsterdam Airport Schiphol, Nederland',
                    'geometry' => ['location' => ['lat' => 52.3105, 'lng' => 4.7683]],
                ],
            ], 200),
            'router.project-osrm.org/*' => Http::response([
                'routes' => [[
                    'distance' => 145000,
                    'duration' => 5400,
                ]],
            ], 200),
        ]);

        $service = new AiChatMapsRouteService();
        $route = $service->resolveRoute(
            'Enschede, Enschede, Nederland',
            'Schiphol Amsterdam Airport (AMS), Schiphol, Nederland',
            ['lat' => 52.2215, 'lng' => 6.8937],
            ['place_id' => 'ChIJTestSchiphol'],
        );

        $this->assertNotNull($route);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'place/details/json')
                && ($request->data()['place_id'] ?? '') === 'ChIJTestSchiphol';
        });
    }

    public function test_geocode_skips_nominatim_when_coordinates_are_provided(): void
    {
        Http::fake([
            'router.project-osrm.org/*' => Http::response([
                'routes' => [[
                    'distance' => 1000,
                    'duration' => 120,
                ]],
            ], 200),
        ]);

        $service = new AiChatMapsRouteService();
        $route = $service->resolveRoute(
            'Enschede, Enschede, Nederland',
            'Amsterdam Airport Schiphol, Nederland',
            ['lat' => 52.2215, 'lng' => 6.8937],
            ['lat' => 51.2895, 'lng' => 6.7667],
        );

        $this->assertNotNull($route);

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'nominatim.openstreetmap.org');
        });
    }

    public function test_fetch_route_falls_back_to_google_directions_when_osrm_fails(): void
    {
        config(['maps.api_key' => 'test-google-key']);

        Http::fake([
            'router.project-osrm.org/*' => Http::response([], 500),
            'maps.googleapis.com/maps/api/directions/json*' => Http::response([
                'status' => 'OK',
                'routes' => [[
                    'legs' => [[
                        'distance' => ['value' => 198000],
                        'duration' => ['value' => 7200],
                    ]],
                ]],
            ], 200),
        ]);

        $service = new AiChatMapsRouteService();
        $route = $service->resolveRoute(
            'Stationsplein, Enschede, Nederland',
            'Luchthaven Düsseldorf (DUS), Düsseldorf, Duitsland',
            ['lat' => 52.2222, 'lng' => 6.8914],
            ['lat' => 51.2895, 'lng' => 6.7667],
        );

        $this->assertNotNull($route);
        $this->assertSame(198000, $route['distance_meters']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'directions/json');
        });
    }
}

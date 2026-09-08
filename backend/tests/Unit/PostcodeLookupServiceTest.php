<?php

namespace Tests\Unit;

use App\Models\GeneralSetting;
use App\Services\PostcodeLookupService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostcodeLookupServiceTest extends TestCase
{
    #[Test]
    public function openpostcode_result_with_street_is_used_first(): void
    {
        Http::fake([
            'openpostcode.nl/*' => Http::response([
                'straat' => 'Kalverstraat',
                'huisnummer' => '1',
                'postcode' => '1012NX',
                'woonplaats' => 'Amsterdam',
                'latitude' => 52.37,
                'longitude' => 4.89,
            ], 200),
            'api.pdok.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('1012NX', '1');

        $this->assertTrue($result['success']);
        $this->assertSame('Kalverstraat', $result['street']);
        $this->assertSame('Amsterdam', $result['city']);
        $this->assertSame('openpostcode', $result['source']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'pdok.nl'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'maps.googleapis.com'));
    }

    #[Test]
    public function pdok_fills_street_when_openpostcode_has_no_street(): void
    {
        Http::fake([
            'openpostcode.nl/*' => Http::response([
                'woonplaats' => 'Enschede',
            ], 200),
            'api.pdok.nl/*' => Http::response([
                'response' => [
                    'docs' => [[
                        'straatnaam' => 'Deurningerstraat',
                        'huisnummer' => 240,
                        'postcode' => '7522CA',
                        'woonplaatsnaam' => 'Enschede',
                        'centroide_ll' => 'POINT(6.8958 52.2205)',
                    ]],
                ],
            ], 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('7522CA', '240');

        $this->assertTrue($result['success']);
        $this->assertSame('Deurningerstraat', $result['street']);
        $this->assertSame('Enschede', $result['city']);
        $this->assertSame('pdok', $result['source']);
        $this->assertEqualsWithDelta(52.2205, $result['latitude'], 0.0001);
        $this->assertEqualsWithDelta(6.8958, $result['longitude'], 0.0001);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'maps.googleapis.com'));
    }

    #[Test]
    public function pdok_uses_postcode_street_when_house_number_is_missing(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'openpostcode.nl')) {
                return Http::response(['error' => 'Huisnummer not found'], 404);
            }
            if (str_contains($url, 'pdok.nl') || str_contains($url, 'nationaalgeoregister.nl')) {
                $filter = (string) $request['fq'];
                if (str_contains($filter, 'type:postcode')) {
                    return Http::response([
                        'response' => [
                            'docs' => [[
                                'straatnaam' => 'Stationsplein',
                                'postcode' => '1012AB',
                                'woonplaatsnaam' => 'Amsterdam',
                                'weergavenaam' => 'Stationsplein, 1012AB Amsterdam',
                                'centroide_ll' => 'POINT(4.9003 52.3791)',
                            ]],
                        ],
                    ], 200);
                }

                return Http::response(['response' => ['docs' => []]], 200);
            }

            return Http::response(['status' => 'ZERO_RESULTS'], 200);
        });

        $result = app(PostcodeLookupService::class)->lookup('1012AB', '24');

        $this->assertTrue($result['success']);
        $this->assertSame('Stationsplein', $result['street']);
        $this->assertSame('Amsterdam', $result['city']);
        $this->assertSame('24', $result['house_number']);
        $this->assertSame('pdok', $result['source']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'maps.googleapis.com'));
    }

    #[Test]
    public function google_fills_street_when_openpostcode_and_pdok_miss(): void
    {
        GeneralSetting::set('GOOGLE_MAPS_API_KEY', 'test-maps-key');
        Http::fake([
            'openpostcode.nl/*' => Http::response(['error' => 'Huisnummer not found'], 404),
            'api.pdok.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'geodata.nationaalgeoregister.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'address_components' => [
                        ['long_name' => 'Stationsplein', 'types' => ['route']],
                        ['long_name' => 'Amsterdam', 'types' => ['locality', 'political']],
                        ['long_name' => '1012 AB', 'types' => ['postal_code']],
                        ['long_name' => 'NL', 'types' => ['country', 'political']],
                    ],
                    'geometry' => ['location' => ['lat' => 52.3791, 'lng' => 4.9003]],
                    'formatted_address' => 'Stationsplein 24, 1012 AB Amsterdam, Nederland',
                ]],
            ], 200),
            'maps.googleapis.com/maps/api/place/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('1012AB', '24');

        $this->assertTrue($result['success']);
        $this->assertSame('Stationsplein', $result['street']);
        $this->assertSame('Amsterdam', $result['city']);
        $this->assertSame('google', $result['source']);
    }

    #[Test]
    public function google_places_fills_street_when_geocode_has_no_route(): void
    {
        GeneralSetting::set('GOOGLE_MAPS_API_KEY', 'test-maps-key');
        Http::fake([
            'openpostcode.nl/*' => Http::response(['error' => 'Huisnummer not found'], 404),
            'api.pdok.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'geodata.nationaalgeoregister.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'address_components' => [
                        ['long_name' => 'Amsterdam', 'types' => ['locality', 'political']],
                        ['long_name' => '1012 AB', 'types' => ['postal_code']],
                        ['long_name' => 'NL', 'types' => ['country', 'political']],
                    ],
                    'formatted_address' => '1012 AB Amsterdam, Nederland',
                ]],
            ], 200),
            'maps.googleapis.com/maps/api/place/findplacefromtext/*' => Http::response([
                'status' => 'OK',
                'candidates' => [[
                    'place_id' => 'ChIJtest',
                    'formatted_address' => 'Stationsplein, 1012 AB Amsterdam',
                ]],
            ], 200),
            'maps.googleapis.com/maps/api/place/details/*' => Http::response([
                'status' => 'OK',
                'result' => [
                    'address_components' => [
                        ['long_name' => 'Stationsplein', 'types' => ['route']],
                        ['long_name' => 'Amsterdam', 'types' => ['locality', 'political']],
                        ['long_name' => '1012 AB', 'types' => ['postal_code']],
                        ['long_name' => 'NL', 'types' => ['country', 'political']],
                    ],
                    'formatted_address' => 'Stationsplein, 1012 AB Amsterdam, Nederland',
                    'geometry' => ['location' => ['lat' => 52.3791, 'lng' => 4.9003]],
                ],
            ], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('1012AB', '24');

        $this->assertTrue($result['success']);
        $this->assertSame('Stationsplein', $result['street']);
        $this->assertSame('Amsterdam', $result['city']);
        $this->assertSame('google', $result['source']);
    }

    #[Test]
    public function google_fills_city_when_six_letter_postcode_is_unknown(): void
    {
        GeneralSetting::set('GOOGLE_MAPS_API_KEY', 'test-maps-key');
        Http::fake([
            'openpostcode.nl/*' => Http::response(['error' => 'Huisnummer not found', 'suggestions' => []], 404),
            'api.pdok.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'geodata.nationaalgeoregister.nl/*' => Http::response(['response' => ['docs' => []]], 200),
            'maps.googleapis.com/maps/api/geocode/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'address_components' => [
                        ['long_name' => 'Den Haag', 'types' => ['locality', 'political']],
                        ['long_name' => '2511', 'types' => ['postal_code', 'postal_code_prefix']],
                        ['long_name' => 'NL', 'types' => ['country', 'political']],
                    ],
                    'formatted_address' => '2511 Den Haag, Nederland',
                ]],
            ], 200),
            'maps.googleapis.com/maps/api/place/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('2511EF', '31');

        $this->assertTrue($result['success']);
        $this->assertSame('', $result['street']);
        $this->assertSame('Den Haag', $result['city']);
        $this->assertSame('google', $result['source']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'findplacefromtext'));
    }

    #[Test]
    public function pdok_fallback_can_be_disabled(): void
    {
        GeneralSetting::set(PostcodeLookupService::SETTING_KEY, '0');
        Http::fake([
            'openpostcode.nl/*' => Http::response(['woonplaats' => 'Enschede'], 200),
            'api.pdok.nl/*' => Http::response([
                'response' => [
                    'docs' => [[
                        'straatnaam' => 'Deurningerstraat',
                        'woonplaatsnaam' => 'Enschede',
                    ]],
                ],
            ], 200),
            'maps.googleapis.com/*' => Http::response(['status' => 'ZERO_RESULTS'], 200),
        ]);

        $result = app(PostcodeLookupService::class)->lookup('7522CA', '240');

        $this->assertTrue($result['success']);
        $this->assertSame('', $result['street']);
        $this->assertSame('Enschede', $result['city']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'pdok.nl'));
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaTaxiBookingAddressSearchStationTest extends TestCase
{
    #[Test]
    public function centraal_station_keeps_centraal_in_nominatim_query_and_ranks_cs_first(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
                $q = (string) ($query['q'] ?? '');
                $this->assertMatchesRegularExpression('/centraal/i', $q);
                $this->assertNotSame('station, enschede', mb_strtolower(trim($q)));

                return Http::response([
                    [
                        'lat' => '52.2189172',
                        'lon' => '6.9735096',
                        'category' => 'highway',
                        'type' => 'bus_stop',
                        'name' => 'Station',
                        'display_name' => 'Station, Kerkstraat, Glanerbrug, Enschede, Nederland',
                    ],
                    [
                        'lat' => '52.2217028',
                        'lon' => '6.8893961',
                        'category' => 'highway',
                        'type' => 'platform',
                        'name' => 'Centraal Station',
                        'display_name' => 'Centraal Station, Stationsplein, Enschede, Nederland',
                    ],
                ], 200);
            },
        ]);

        $response = $this->getJson('/nexa-taxi/booking/address-search?'.http_build_query([
            'q' => 'Centraal Station, Enschede',
            'limit' => 8,
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertSame('Centraal Station', $data[0]['name'] ?? null);
        $this->assertSame('52.2217028', (string) ($data[0]['lat'] ?? ''));
    }

    #[Test]
    public function reverse_geocode_puts_house_number_after_street_name(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response([
                'lat' => '52.2215',
                'lon' => '6.8935',
                'name' => '155',
                'display_name' => '155, Deurningerstraat, Walhof, Enschede, Overijssel, Nederland',
                'addresstype' => 'house',
                'category' => 'place',
                'type' => 'house',
                'address' => [
                    'house_number' => '155',
                    'road' => 'Deurningerstraat',
                    'suburb' => 'Walhof',
                    'city' => 'Enschede',
                    'state' => 'Overijssel',
                    'postcode' => '7514AD',
                    'country' => 'Nederland',
                ],
            ], 200),
        ]);

        $response = $this->getJson('/nexa-taxi/booking/address-search?'.http_build_query([
            'lat' => '52.2215',
            'lon' => '6.8935',
        ]));

        $response->assertOk();
        $display = (string) ($response->json('display_name') ?? '');
        $this->assertStringContainsString('Deurningerstraat 155', $display);
        $this->assertDoesNotMatchRegularExpression('/^155,\s*Deurningerstraat/', $display);
        $this->assertStringContainsString('Enschede', $display);
    }

    #[Test]
    public function business_name_is_returned_ahead_of_street_matches(): void
    {
        config(['maps.address_business_search_key' => 'test-places-key']);

        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '52.2300',
                    'lon' => '6.9600',
                    'category' => 'highway',
                    'type' => 'residential',
                    'name' => 'Dentalstraat',
                    'display_name' => 'Dentalstraat, Glanerbrug, Enschede, Nederland',
                    'address' => [
                        'road' => 'Dentalstraat',
                        'city' => 'Glanerbrug',
                    ],
                ],
            ], 200),
            'maps.googleapis.com/maps/api/place/textsearch/json*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'name' => 'Dental Art',
                    'formatted_address' => 'Gronausestraat 710, Glanerbrug, Nederland',
                    'geometry' => ['location' => ['lat' => 52.2211, 'lng' => 6.9712]],
                ]],
            ], 200),
        ]);

        $response = $this->getJson('/nexa-taxi/booking/address-search?'.http_build_query([
            'q' => 'dental art glanerbrug',
            'countrycodes' => 'nl',
            'limit' => 8,
        ]));

        $response->assertOk();
        $data = $response->json();
        $this->assertSame('Dental Art', $data[0]['name'] ?? null);
        $this->assertStringContainsString('Gronausestraat 710', (string) ($data[0]['display_name'] ?? ''));
        $this->assertStringContainsString('Glanerbrug', (string) ($data[0]['display_name'] ?? ''));
        $this->assertSame('52.2211', (string) ($data[0]['lat'] ?? ''));
    }
}

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
}

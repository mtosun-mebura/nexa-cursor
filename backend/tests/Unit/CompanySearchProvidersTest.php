<?php

namespace Tests\Unit;

use App\Services\CompanySearch\CompanySearchData;
use App\Services\CompanySearch\GeoSearchGridService;
use App\Services\CompanySearch\GooglePlacesCompanySearchService;
use App\Services\CompanySearch\HunterService;
use App\Services\CompanySearch\WebsiteCrawlerService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanySearchProvidersTest extends TestCase
{
    #[Test]
    public function google_places_maps_text_search_results(): void
    {
        config([
            'company-enrichment.google.enabled' => true,
            'company-enrichment.google.api_key' => 'test-key',
            'company-enrichment.google.requests_per_second' => 100,
            'company-enrichment.google.max_pages_per_query' => 1,
        ]);
        Http::fake([
            'places.googleapis.com/*' => Http::response([
                'places' => [[
                    'id' => 'ChIJ123',
                    'displayName' => ['text' => 'Janssen Installatie'],
                    'formattedAddress' => 'Kerkstraat 1, Enschede',
                    'nationalPhoneNumber' => '053-1112233',
                    'websiteUri' => 'https://www.janssen-installatie.nl/contact',
                    'addressComponents' => [
                        ['longText' => 'Enschede', 'types' => ['locality']],
                        ['longText' => 'Overijssel', 'types' => ['administrative_area_level_1']],
                    ],
                ]],
            ]),
        ]);

        $results = app(GooglePlacesCompanySearchService::class)->search(new CompanySearchData(
            industry: 'installatiebedrijf',
            provinces: ['Overijssel'],
            query: 'loodgieter',
            city: 'Enschede',
            maxResults: 10,
        ));

        $this->assertCount(1, $results);
        $this->assertSame('ChIJ123', $results[0]->placeId);
        $this->assertSame('Janssen Installatie', $results[0]->name);
        $this->assertSame('https://www.janssen-installatie.nl', $results[0]->website);
        $this->assertSame('0531112233', $results[0]->phone);
        $this->assertSame('Enschede', $results[0]->city);
    }

    #[Test]
    public function geo_grid_uses_city_when_provinces_are_empty(): void
    {
        $points = app(GeoSearchGridService::class)->points(new CompanySearchData(
            industry: 'taxi',
            provinces: [],
            city: 'Enschede',
            radiusKm: 40,
        ));

        $this->assertCount(1, $points);
        $this->assertSame('Enschede', $points[0]['city']);
        $this->assertSame('Overijssel', $points[0]['province']);
        $this->assertGreaterThan(0, $points[0]['lat']);
        $this->assertSame(40, $points[0]['radius_km']);
    }

    #[Test]
    public function website_crawler_collects_mailto_and_tel(): void
    {
        config(['company-enrichment.crawler.delay_ms' => 0, 'company-enrichment.crawler.max_pages' => 2]);
        Http::fake([
            'janssen-installatie.nl/*' => Http::response(
                '<html><body><a href="mailto:info@janssen-installatie.nl">Email ons</a><a href="tel:+31531234567">Bel ons</a></body></html>'
            ),
        ]);

        $contacts = app(WebsiteCrawlerService::class)->collectContacts('https://janssen-installatie.nl');

        $this->assertSame('info@janssen-installatie.nl', $contacts['email']);
        $this->assertSame('0531234567', $contacts['phone']);
    }

    #[Test]
    public function hunter_is_skipped_when_disabled(): void
    {
        config(['company-enrichment.hunter.enabled' => false, 'company-enrichment.hunter.api_key' => 'secret']);
        Http::fake();

        $this->assertNull(app(HunterService::class)->findEmail('https://example.nl'));
        Http::assertNothingSent();
    }

    #[Test]
    public function hunter_returns_generic_email(): void
    {
        config([
            'company-enrichment.hunter.enabled' => true,
            'company-enrichment.hunter.api_key' => 'hunter-test',
        ]);
        Http::fake([
            'api.hunter.io/*' => Http::response([
                'data' => [
                    'emails' => [
                        ['value' => 'jan@example.nl', 'type' => 'personal'],
                        ['value' => 'info@example.nl', 'type' => 'generic'],
                    ],
                ],
            ]),
        ]);

        $email = app(HunterService::class)->findEmail('https://www.example.nl');

        $this->assertSame('info@example.nl', $email);
    }

    #[Test]
    public function hunter_returns_personal_name_from_domain_search(): void
    {
        config([
            'company-enrichment.hunter.enabled' => true,
            'company-enrichment.hunter.api_key' => 'hunter-test',
        ]);
        Http::fake([
            'api.hunter.io/*' => Http::response([
                'data' => [
                    'emails' => [
                        [
                            'value' => 'kees@example.nl',
                            'type' => 'personal',
                            'first_name' => 'Kees',
                            'last_name' => 'van Dijk',
                            'position' => 'Eigenaar',
                        ],
                        ['value' => 'info@example.nl', 'type' => 'generic'],
                    ],
                ],
            ]),
        ]);

        $lookup = app(HunterService::class)->lookup('https://www.example.nl');

        $this->assertSame('info@example.nl', $lookup['email']);
        $this->assertSame('Kees', $lookup['first_name']);
        $this->assertSame('van', $lookup['middle_name']);
        $this->assertSame('Dijk', $lookup['last_name']);
    }
}

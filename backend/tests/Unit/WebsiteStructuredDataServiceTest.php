<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Services\WebsiteStructuredDataService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsiteStructuredDataServiceTest extends TestCase
{
    #[Test]
    public function it_builds_organization_website_and_webpage_graph(): void
    {
        $page = new WebsitePage([
            'title' => 'Over ons',
            'slug' => 'over-ons',
            'meta_description' => 'Leer ons team kennen.',
            'page_type' => 'about',
        ]);
        $page->updated_at = now();

        $company = new Company([
            'name' => 'Demo Taxi BV',
            'email' => 'info@demo.nl',
            'phone' => '+31 20 123 4567',
            'street' => 'Hoofdstraat',
            'house_number' => '1',
            'postal_code' => '1234 AB',
            'city' => 'Amsterdam',
            'country' => 'NL',
        ]);

        $service = new WebsiteStructuredDataService;
        $graph = $service->buildGraph(
            $page,
            [
                'site_name' => 'Nexa Taxi',
                'site_description' => 'Taxivervoer in de regio.',
                'logo_url' => 'https://example.test/logo.png',
            ],
            [
                'footer' => [
                    'tagline' => 'Betrouwbaar vervoer.',
                ],
            ],
            'https://demo.test/over-ons',
            'Over ons',
            'Leer ons team kennen.',
            $company,
        );

        $this->assertSame('https://schema.org', $graph['@context']);
        $this->assertCount(3, $graph['@graph']);

        $types = array_column($graph['@graph'], '@type');
        $this->assertContains('LocalBusiness', $types);
        $this->assertContains('WebSite', $types);
        $this->assertContains('WebPage', $types);

        $org = collect($graph['@graph'])->firstWhere('@type', 'LocalBusiness');
        $this->assertSame('Demo Taxi BV', $org['name']);
        $this->assertSame('info@demo.nl', $org['email']);
        $this->assertArrayHasKey('address', $org);

        $webPage = collect($graph['@graph'])->firstWhere('@type', 'WebPage');
        $this->assertSame('Over ons', $webPage['name']);
        $this->assertSame('Leer ons team kennen.', $webPage['description']);
    }

    #[Test]
    public function it_uses_organization_without_address_when_no_location_data(): void
    {
        $page = new WebsitePage([
            'title' => 'Home',
            'slug' => 'home',
            'page_type' => 'home',
        ]);

        $service = new WebsiteStructuredDataService;
        $graph = $service->buildGraph(
            $page,
            ['site_name' => 'Nexa', 'site_description' => ''],
            null,
            'https://demo.test/',
            'Home',
            'Welkom bij Nexa.',
            null,
        );

        $org = collect($graph['@graph'])->first(fn ($n) => in_array($n['@type'], ['Organization', 'LocalBusiness'], true));
        $this->assertSame('Organization', $org['@type']);
    }
}

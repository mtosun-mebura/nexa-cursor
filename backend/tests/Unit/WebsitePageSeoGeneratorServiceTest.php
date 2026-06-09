<?php

namespace Tests\Unit;

use App\Services\WebsitePageSeoGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePageSeoGeneratorServiceTest extends TestCase
{
    #[Test]
    public function it_generates_meta_description_within_ideal_length(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => 'Over ons',
            'page_type' => 'about',
            'site_name' => 'Nexa Taxi',
            'site_description' => 'Betrouwbaar taxivervoer in de regio.',
            'company_name' => 'Demo Taxi BV',
            'include_sections' => true,
        ]);

        $this->assertNotEmpty($result['title']);
        $this->assertNotEmpty($result['meta_description']);
        $this->assertLessThanOrEqual(160, mb_strlen($result['meta_description']));
        $this->assertArrayHasKey('hero', $result['sections']);
        $this->assertNotEmpty($result['sections']['hero']['title']);
        $this->assertSame('template', $result['source']);
        $this->assertNotEmpty($result['tips']);
    }

    #[Test]
    public function it_generates_home_copy_with_brand(): void
    {
        $service = new WebsitePageSeoGeneratorService;

        $result = $service->generate([
            'title' => '',
            'page_type' => 'home',
            'site_name' => 'Nexa',
            'company_name' => 'Acme BV',
            'include_sections' => true,
        ]);

        $this->assertStringContainsString('Acme', $result['meta_description']);
        $this->assertStringContainsString('Acme', $result['sections']['hero']['subtitle']);
    }
}

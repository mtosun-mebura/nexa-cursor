<?php

namespace Tests\Feature;

use App\Models\WebsitePage;
use App\Services\PublicSitemapBuilder;
use App\Services\WebsiteBuilderService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicSitemapTest extends TestCase
{
    #[Test]
    public function sitemap_xml_is_valid_and_includes_homepage(): void
    {
        $this->mock(WebsiteBuilderService::class, function ($mock): void {
            $mock->shouldReceive('loadAllPagesForAdminIndex')->andReturn(collect());
            $mock->shouldReceive('getAboutPage')->andReturn(null);
            $mock->shouldReceive('getContactPage')->andReturn(null);
        });

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $body = $response->getContent();
        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $body);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', $body);
        $this->assertStringContainsString('<loc>'.url('/').'</loc>', $body);
        $this->assertStringContainsString('<changefreq>', $body);
        $this->assertStringContainsString('<priority>', $body);
        $this->assertNotFalse(@simplexml_load_string($body));
    }

    #[Test]
    public function sitemap_includes_active_website_pages(): void
    {
        $page = new WebsitePage([
            'slug' => 'prijzen',
            'title' => 'Prijzen',
            'is_active' => true,
        ]);
        $page->updated_at = now();

        $this->mock(WebsiteBuilderService::class, function ($mock) use ($page): void {
            $mock->shouldReceive('loadAllPagesForAdminIndex')->andReturn(new Collection([$page]));
            $mock->shouldReceive('getAboutPage')->andReturn(null);
            $mock->shouldReceive('getContactPage')->andReturn(null);
        });

        $xml = app(PublicSitemapBuilder::class)->toXml();

        $this->assertStringContainsString('<loc>'.url('/prijzen').'</loc>', $xml);
        $this->assertStringContainsString('<priority>0.8</priority>', $xml);
    }

    #[Test]
    public function robots_txt_points_to_sitemap_and_blocks_admin(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $body = $response->getContent();
        $this->assertStringContainsString('User-agent: *', $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Sitemap: '.url('/sitemap.xml'), $body);
    }
}

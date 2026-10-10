<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\NexaTaxiWelcomePageService;
use App\Services\PublicSitemapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaTaxiTenantWebsiteTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('website_pages') || ! Schema::hasTable('companies')) {
            $this->markTestSkipped('website_pages + companies required');
        }

        config([
            'app.url' => 'http://localhost:8085',
            'tenancy.central_domains' => ['localhost'],
        ]);

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $this->company = Company::query()->create([
            'name' => 'Nexa Taxi',
            'slug' => 'nexa-taxi-demo',
            'is_active' => true,
        ]);

        app(NexaTaxiWelcomePageService::class)->ensureMarketingPagesExist($this->company);
    }

    #[Test]
    public function nexataxi_host_serves_tenant_home_with_booking_and_seo(): void
    {
        $response = $this->get('http://nexataxi.nl/');

        $response->assertOk();
        $response->assertSee('Boek een taxi', false);
        $response->assertSee('rel="canonical"', false);
        $response->assertSee('nexataxi.nl/', false);
        $home = WebsitePage::query()
            ->where('company_id', $this->company->id)
            ->where('slug', 'home')
            ->first();
        $this->assertNotNull($home);
        $response->assertSee(e($home->meta_description), false);
    }

    #[Test]
    public function boek_page_has_marketplace_module_and_unique_meta(): void
    {
        $response = $this->get('http://nexataxi.nl/boek');

        $response->assertOk();
        $response->assertSee('Taxi boeken online', false);
        $response->assertSee('data-nexataxi-booking-module', false);
        $response->assertSee('nexataxi.nl/boek', false);
    }

    #[Test]
    public function sitemap_on_nexataxi_lists_tenant_pages_only(): void
    {
        app()->instance('resolved_tenant_id', $this->company->id);

        $xml = app(PublicSitemapBuilder::class)->toXml($this->company->id);

        $this->assertStringContainsString('/boek', $xml);
        $this->assertStringContainsString('/hoe-het-werkt', $xml);
        $this->assertStringContainsString('/aansluiten', $xml);
        $this->assertStringNotContainsString('/contractvervoer', $xml);
        $this->assertStringNotContainsString('/prijzen', $xml);
    }
}

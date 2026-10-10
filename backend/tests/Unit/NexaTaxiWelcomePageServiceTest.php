<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\FrontendTheme;
use App\Models\WebsitePage;
use App\Services\NexaTaxiWelcomePageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NexaTaxiWelcomePageServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ensure_creates_pages_and_domains_for_nexa_taxi_tenant(): void
    {
        if (! Schema::hasTable('website_pages') || ! Schema::hasTable('companies')) {
            $this->markTestSkipped('website_pages + companies required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $company = Company::query()->create([
            'name' => 'Nexa Taxi',
            'slug' => 'nexa-taxi-demo',
            'is_active' => true,
        ]);

        $pages = app(NexaTaxiWelcomePageService::class)->ensureMarketingPagesExist($company);

        $this->assertGreaterThanOrEqual(6, $pages->count());
        $this->assertTrue(
            CompanyDomain::query()->where('company_id', $company->id)->where('host', 'nexataxi.nl')->exists()
        );
        $this->assertTrue(
            CompanyDomain::query()->where('company_id', $company->id)->where('host', 'www.nexataxi.nl')->exists()
        );

        $home = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where('slug', 'home')
            ->first();
        $this->assertNotNull($home);
        $this->assertSame('home', $home->page_type);
        $this->assertContains(
            NexaTaxiWelcomePageService::HOME_BOOKING_SECTION_KEY,
            $home->getHomeSections()['section_order'] ?? []
        );

        $boek = WebsitePage::query()
            ->where('company_id', $company->id)
            ->where('slug', 'boek')
            ->first();
        $this->assertNotNull($boek);
        $this->assertStringContainsString('nexataxi.nl', (string) $boek->meta_description);
        $this->assertContains(
            NexaTaxiWelcomePageService::HOME_BOOKING_SECTION_KEY,
            $boek->getHomeSections()['section_order'] ?? []
        );

        foreach (['hoe-het-werkt', 'app', 'aansluiten', 'contact'] as $slug) {
            $this->assertNotNull(
                WebsitePage::query()->where('company_id', $company->id)->where('slug', $slug)->first(),
                "Missing page {$slug}"
            );
        }
    }

    #[Test]
    public function pages_are_scoped_to_tenant_not_central(): void
    {
        if (! Schema::hasTable('website_pages') || ! Schema::hasTable('companies')) {
            $this->markTestSkipped('website_pages + companies required');
        }

        FrontendTheme::firstOrCreate(
            ['slug' => 'modern'],
            ['name' => 'Modern', 'is_active' => true]
        );

        $company = Company::query()->create([
            'name' => 'Nexa Taxi',
            'slug' => 'nexa-taxi-demo',
            'is_active' => true,
        ]);

        app(NexaTaxiWelcomePageService::class)->ensureMarketingPagesExist($company);

        $this->assertSame(
            0,
            WebsitePage::query()
                ->whereNull('company_id')
                ->where('slug', 'hoe-het-werkt')
                ->count()
        );
        $this->assertSame(
            1,
            WebsitePage::query()
                ->where('company_id', $company->id)
                ->where('slug', 'hoe-het-werkt')
                ->count()
        );
    }
}

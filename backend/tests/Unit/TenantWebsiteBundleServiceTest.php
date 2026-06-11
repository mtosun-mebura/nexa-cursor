<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Services\TenantWebsiteBundleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantWebsiteBundleServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function upsert_website_page_entry_creates_and_updates_tenant_page(): void
    {
        if (! Schema::hasTable('website_pages')) {
            $this->markTestSkipped('website_pages table required');
        }

        $company = Company::query()->create(['name' => 'Page Sync Co', 'slug' => 'page-sync-'.uniqid()]);
        $service = app(TenantWebsiteBundleService::class);
        $method = new \ReflectionMethod(TenantWebsiteBundleService::class, 'upsertWebsitePageEntry');
        $method->setAccessible(true);
        $conn = (string) config('database.default');

        $entry = [
            'attributes' => [
                'slug' => 'home-'.uniqid(),
                'title' => 'Home',
                'page_type' => 'home',
                'is_active' => true,
                'home_sections' => [],
            ],
        ];

        $this->assertSame('inserted', $method->invoke($service, $conn, (int) $company->id, $entry));
        $entry['attributes']['title'] = 'Home bijgewerkt';
        $this->assertSame('updated', $method->invoke($service, $conn, (int) $company->id, $entry));

        $this->assertSame(
            'Home bijgewerkt',
            WebsitePage::query()
                ->where('company_id', $company->id)
                ->where('slug', $entry['attributes']['slug'])
                ->value('title')
        );
    }
}

<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\GeneralSetting;
use App\Services\GoogleSeoSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GoogleSeoSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stores_and_reads_encrypted_service_account_json(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }

        $company = Company::create(['name' => 'SEO Test BV', 'is_active' => true]);
        $companyId = (int) $company->id;

        $service = new GoogleSeoSettingsService;
        $json = json_encode([
            'type' => 'service_account',
            'project_id' => 'demo',
            'private_key_id' => 'abc',
            'private_key' => "-----BEGIN PRIVATE KEY-----\nMIIB\n-----END PRIVATE KEY-----\n",
            'client_email' => 'seo@demo.iam.gserviceaccount.com',
            'client_id' => '123',
        ], JSON_THROW_ON_ERROR);

        $service->storeServiceAccountJson($companyId, $json);

        $this->assertTrue($service->hasServiceAccount($companyId));
        $this->assertSame('seo@demo.iam.gserviceaccount.com', $service->serviceAccountClientEmail($companyId));

        $stored = GeneralSetting::get(GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT, null, $companyId);
        $this->assertNotSame($json, $stored);
        $this->assertSame($json, Crypt::decryptString((string) $stored));
    }

    #[Test]
    public function it_builds_sitemap_public_url(): void
    {
        $company = Company::create(['name' => 'Sitemap BV', 'is_active' => true]);
        $service = new GoogleSeoSettingsService;
        GeneralSetting::set(GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_SITEMAP_PATH, 'sitemap.xml', $company->id);

        $this->assertStringEndsWith('/sitemap.xml', $service->sitemapPublicUrl((int) $company->id));
    }
}

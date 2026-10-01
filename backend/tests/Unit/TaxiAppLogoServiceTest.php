<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Module;
use App\Modules\NexaTaxi\Services\TaxiAppLogoService;
use App\Services\CompanyEmailLogoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiAppLogoServiceTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82";

    #[Test]
    public function tenant_with_company_logo_keeps_own_brand(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Royaal',
            'logo_blob' => base64_encode(self::TINY_PNG),
            'logo_mime_type' => 'image/png',
        ]);

        $urls = app(TaxiAppLogoService::class)->pwaLogoUrls($company->id);

        $this->assertNotNull($urls['light']);
        $this->assertStringContainsString('/email-logo/'.$company->id, $urls['light']);
        $this->assertStringNotContainsString('nexa-taxi-logo', $urls['light']);
    }

    #[Test]
    public function marketplace_without_company_logo_uses_nexa_taxi_defaults(): void
    {
        $company = Company::query()->create(['name' => 'Marketplace Partner']);

        $urls = app(TaxiAppLogoService::class)->pwaLogoUrls($company->id);

        $this->assertStringContainsString('nexa-taxi-logo.png', $urls['light']);
        $this->assertStringContainsString('nexa-taxi-logo-dark.png', $urls['dark']);
    }

    #[Test]
    public function uploaded_module_logos_override_defaults_when_no_company_logo(): void
    {
        Storage::fake('public');
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'configuration' => [],
        ]);

        $light = UploadedFile::fake()->image('light.png', 40, 20);
        $dark = UploadedFile::fake()->image('dark.png', 40, 20);

        $service = app(TaxiAppLogoService::class);
        $stored = $service->storeUploads($light, $dark);

        $this->assertNotNull($stored['light']);
        $this->assertNotNull($stored['dark']);
        Storage::disk('public')->assertExists($stored['light']);
        Storage::disk('public')->assertExists($stored['dark']);

        $company = Company::query()->create(['name' => 'Network Only']);
        $urls = $service->pwaLogoUrls($company->id);

        $this->assertStringContainsString('storage/'.$stored['light'], $urls['light']);
        $this->assertStringContainsString('storage/'.$stored['dark'], $urls['dark']);
    }

    #[Test]
    public function company_logo_still_wins_over_module_uploads(): void
    {
        Storage::fake('public');
        Module::query()->create([
            'name' => 'taxi',
            'display_name' => 'Nexa Taxi',
            'version' => '1.0.0',
            'installed' => true,
            'active' => true,
            'configuration' => [],
        ]);

        app(TaxiAppLogoService::class)->storeUploads(
            UploadedFile::fake()->image('mod-light.png', 40, 20),
            UploadedFile::fake()->image('mod-dark.png', 40, 20),
        );

        $company = Company::query()->create([
            'name' => 'Subscribed Tenant',
            'logo_blob' => base64_encode(self::TINY_PNG),
            'logo_mime_type' => 'image/png',
        ]);

        $urls = app(TaxiAppLogoService::class)->pwaLogoUrls($company->id);

        $this->assertStringContainsString('/email-logo/'.$company->id, $urls['light']);
        $this->assertTrue(app(CompanyEmailLogoService::class)->hasLogoSource($company->id));
    }
}

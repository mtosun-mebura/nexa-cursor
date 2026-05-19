<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class AdminTenantStorageBundleExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function tenant_storage_bundle_export_streams_zip_with_v2_manifest(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $company = Company::query()->create(['name' => 'Export Co']);

        Storage::disk('public')->put('website/test-export-marker.txt', 'marker');

        $response = $this->actingAs($user)->get(
            route('admin.settings.tenant-storage-bundle.export', ['company_id' => $company->id])
        );

        $response->assertOk();
        $this->assertStringContainsString('zip', (string) $response->headers->get('Content-Type'));

        $binary = $response->streamedContent();
        $this->assertIsString($binary);
        $this->assertGreaterThan(64, strlen($binary));
        $this->assertSame('PK', substr($binary, 0, 2));

        $tmp = tempnam(sys_get_temp_dir(), 'nexa_tse_');
        $this->assertNotFalse($tmp);
        try {
            file_put_contents($tmp, $binary);
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($tmp) === true);
            $manifestJson = $zip->getFromName('manifest.json');
            $this->assertIsString($manifestJson);
            $manifest = json_decode($manifestJson, true);
            $this->assertIsArray($manifest);
            $this->assertSame('tenant_media', $manifest['bundle_type'] ?? null);
            $this->assertSame(2, (int) ($manifest['bundle_version'] ?? 0));
            $this->assertArrayHasKey('pages', $manifest);
            $this->assertArrayHasKey('general_settings', $manifest);
            $this->assertArrayHasKey('storage_paths', $manifest);
            $this->assertIsArray($manifest['pages']);
            $this->assertIsArray($manifest['general_settings']);
            $this->assertArrayHasKey('private_storage_paths', $manifest);
            $this->assertIsArray($manifest['private_storage_paths']);
            $marker = $zip->getFromName('files/website/test-export-marker.txt');
            $this->assertIsString($marker);
            $this->assertSame('marker', $marker);
            $zip->close();
        } finally {
            @unlink($tmp);
        }
    }

    #[Test]
    public function legacy_v1_zip_imports_files_only_without_pages_key_requirement(): void
    {
        if (! Schema::hasTable('general_settings')) {
            $this->markTestSkipped('general_settings table required');
        }

        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $company = Company::query()->create(['name' => 'Import Co']);

        $manifest = [
            'bundle_type' => 'tenant_media',
            'bundle_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'source_company_id' => $company->id,
            'source_company_name' => 'X',
            'storage_paths' => [],
        ];

        $tmpZip = tempnam(sys_get_temp_dir(), 'nexa_tsi_');
        $this->assertNotFalse($tmpZip);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($tmpZip, ZipArchive::OVERWRITE) === true);
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->close();

        try {
            $file = new \Illuminate\Http\UploadedFile($tmpZip, 'legacy.zip', 'application/zip', null, true);
            $response = $this->actingAs($user)->post(
                route('admin.settings.tenant-storage-bundle.import'),
                [
                    'company_id' => $company->id,
                    'bundle' => $file,
                ]
            );
            $response->assertRedirect();
            $response->assertSessionHas('success');
        } finally {
            @unlink($tmpZip);
        }
    }
}

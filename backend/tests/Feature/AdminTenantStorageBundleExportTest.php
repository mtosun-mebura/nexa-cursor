<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\TenantCompanyDataPushService;
use App\Services\TenantStorageBundleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
    public function tenant_sync_scope_includes_payment_company_scoped_tables(): void
    {
        $scope = app(TenantCompanyDataPushService::class)->describeSyncScope();
        $payment = $scope['payment_company_scoped_tables'] ?? [];
        $this->assertIsArray($payment);
        foreach (['payment_providers', 'invoice_settings', 'invoices', 'payments', 'payment_reminders', 'ride_payments'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'company_id')) {
                $this->assertContains($table, $payment, "Expected {$table} in payment_company_scoped_tables");
                $this->assertContains($table, $scope['tables_with_company_id'] ?? []);
            }
        }
    }

    #[Test]
    public function tenant_storage_export_includes_private_invoice_pdfs(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'pdf_path')) {
            $this->markTestSkipped('invoices.pdf_path required');
        }

        $company = Company::query()->create(['name' => 'Invoice PDF Co']);
        $pdfDbRel = 'invoices/'.$company->id.'/TEST-2026-0001.pdf';
        $pdfZipRel = 'private/'.$pdfDbRel;
        Storage::disk('local')->put($pdfDbRel, '%PDF-1.4 test');

        DB::table('invoices')->insert([
            'invoice_number' => 'TEST-2026-0001',
            'company_id' => $company->id,
            'amount' => 10,
            'tax_amount' => 0,
            'total_amount' => 10,
            'currency' => 'EUR',
            'status' => 'sent',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'pdf_path' => $pdfDbRel,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $privatePaths = app(TenantStorageBundleService::class)->collectPrivateAppPathsForExport($company);
        $this->assertContains($pdfZipRel, $privatePaths);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user)->get(
            route('admin.settings.tenant-storage-bundle.export', ['company_id' => $company->id])
        );
        $response->assertOk();
        $binary = $response->streamedContent();
        $this->assertIsString($binary);

        $tmp = tempnam(sys_get_temp_dir(), 'nexa_tse_inv_');
        $this->assertNotFalse($tmp);
        try {
            file_put_contents($tmp, $binary);
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($tmp) === true);
            $manifestJson = $zip->getFromName('manifest.json');
            $this->assertIsString($manifestJson);
            $manifest = json_decode($manifestJson, true);
            $this->assertIsArray($manifest);
            $this->assertContains($pdfZipRel, $manifest['private_storage_paths'] ?? [], 'PDF path missing from manifest private_storage_paths');
            $entry = 'private_files/'.$pdfZipRel;
            $this->assertNotFalse($zip->locateName($entry), 'ZIP entry missing; private paths in manifest: '.json_encode($manifest['private_storage_paths'] ?? []));
            $this->assertSame('%PDF-1.4 test', $zip->getFromName($entry));
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

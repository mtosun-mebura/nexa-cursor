<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\WebsiteMedia;
use App\Models\WebsitePage;
use App\Services\TenantWebsiteBundleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use ZipArchive;

class TenantWebsiteBundleCarouselMediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function export_includes_carousel_website_media_and_import_restores_it(): void
    {
        $theme = FrontendTheme::query()->create([
            'slug' => 'modern-carousel',
            'name' => 'Modern',
            'is_active' => true,
        ]);
        $source = Company::query()->create([
            'name' => 'Carousel Source BV',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
        ]);

        $uuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
        $encryptedPath = 'website_media/'.$uuid.'.enc';
        $absDir = storage_path('app/website_media');
        if (! is_dir($absDir)) {
            mkdir($absDir, 0755, true);
        }
        file_put_contents(storage_path('app/'.$encryptedPath), 'ENCRYPTED-BYTES');

        WebsiteMedia::query()->create([
            'uuid' => $uuid,
            'original_filename' => 'slide.jpg',
            'mime_type' => 'image/jpeg',
            'encrypted_path' => $encryptedPath,
            'size' => 15,
        ]);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'page_type' => 'home',
            'frontend_theme_id' => $theme->id,
            'company_id' => $source->id,
            'is_active' => true,
            'home_sections' => [
                'section_order' => ['carousel'],
                'visibility' => ['carousel' => true],
                'carousel' => ['items' => [['uuid' => $uuid, 'alt' => 'Slide 1']]],
            ],
        ]);

        $service = app(TenantWebsiteBundleService::class);

        $records = $service->collectWebsiteMediaRecordsForCompany($source);
        $this->assertCount(1, $records);
        $this->assertSame($uuid, (string) $records->first()->uuid);

        // Verwijder media-record + bestand om import te simuleren op een "schone" doelomgeving.
        WebsiteMedia::query()->where('uuid', $uuid)->delete();
        @unlink(storage_path('app/'.$encryptedPath));
        $this->assertDatabaseMissing('website_media', ['uuid' => $uuid]);

        // Bouw een ZIP zoals export doet (manifest + media-local bestand).
        $zipPath = tempnam(sys_get_temp_dir(), 'nexa_wb_test_');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $manifest = [
            'bundle_version' => TenantWebsiteBundleService::BUNDLE_VERSION,
            'pages' => [],
            'storage_paths' => [],
            'website_media' => [[
                'uuid' => $uuid,
                'original_filename' => 'slide.jpg',
                'mime_type' => 'image/jpeg',
                'encrypted_path' => $encryptedPath,
                'size' => 15,
            ]],
        ];
        $zip->addFromString('manifest.json', json_encode($manifest));
        $zip->addFromString('media-local/'.$encryptedPath, 'ENCRYPTED-BYTES');
        $zip->close();

        $target = Company::query()->create([
            'name' => 'Carousel Target BV',
            'frontend_theme_id' => $theme->id,
            'is_active' => true,
        ]);

        $upload = new UploadedFile($zipPath, 'bundle.zip', 'application/zip', null, true);
        $result = $service->importZip($target, $upload);

        $this->assertDatabaseHas('website_media', ['uuid' => $uuid, 'encrypted_path' => $encryptedPath]);
        $this->assertFileExists(storage_path('app/'.$encryptedPath));
        $this->assertGreaterThanOrEqual(1, $result['copied_files']);

        @unlink(storage_path('app/'.$encryptedPath));
    }
}

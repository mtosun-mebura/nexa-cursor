<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * ZIP-export/import van alle publieke storage-bestanden die bij een tenant horen
 * (website-media, bedrijfslogo, gebruikers-CV’s, factuur-PDF’s, module-uploadmappen, …).
 * Alleen bestanden — geen databaserijen. Combineer met tenant DB-sync waar nodig.
 */
final class TenantStorageBundleService
{
    public const BUNDLE_VERSION = 1;

    public const BUNDLE_TYPE = 'tenant_media';

    public function __construct(
        protected TenantWebsiteBundleService $websiteBundle
    ) {}

    /**
     * @return list<string> relatieve paden t.o.v. storage/app/public
     */
    public function collectAllTenantStoragePaths(Company $company): array
    {
        $paths = $this->websiteBundle->collectWebsiteMediaPathsForCompany($company);

        $logo = $company->getAttribute('logo_path');
        if (is_string($logo) && trim($logo) !== '') {
            $paths[] = $logo;
        }

        $paths = array_merge($paths, $this->collectPathsFromCompanyTables((int) $company->id));

        $unique = [];
        foreach ($paths as $p) {
            $n = $this->websiteBundle->normalizeStorageRelativePath((string) $p);
            if ($n === '' || str_contains($n, '..')) {
                continue;
            }
            $unique[$n] = true;
        }

        $keys = array_keys($unique);
        sort($keys);

        return $keys;
    }

    public function exportZip(Company $company): StreamedResponse
    {
        $allPaths = $this->collectAllTenantStoragePaths($company);

        $manifest = [
            'bundle_type' => self::BUNDLE_TYPE,
            'bundle_version' => self::BUNDLE_VERSION,
            'exported_at' => now()->toIso8601String(),
            'source_company_id' => (int) $company->id,
            'source_company_name' => (string) $company->name,
            'storage_paths' => $allPaths,
            'note' => 'Import schrijft bestanden naar storage/app/public (overschrijft bestaande bestanden met dezelfde relatieve padnaam). Gebruik na DB-tenant-sync op dezelfde omgeving.',
        ];

        $safeSlug = Str::slug($company->name) ?: 'company';
        $filename = 'tenant-bestanden-'.$company->id.'-'.$safeSlug.'-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($manifest, $allPaths) {
            $zipPath = tempnam(sys_get_temp_dir(), 'nexa_ts_');
            if ($zipPath === false) {
                throw new RuntimeException('Kon geen tijdelijk bestand aanmaken.');
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
                @unlink($zipPath);
                throw new RuntimeException('Kon ZIP-archief niet openen.');
            }

            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            foreach ($allPaths as $rel) {
                $rel = $this->websiteBundle->normalizeStorageRelativePath((string) $rel);
                if ($rel === '') {
                    continue;
                }
                $abs = storage_path('app/public/'.$rel);
                if (is_file($abs) && is_readable($abs)) {
                    $zip->addFile($abs, 'files/'.$rel);
                }
            }

            $zip->close();

            $h = fopen($zipPath, 'rb');
            if ($h !== false) {
                while (! feof($h)) {
                    echo fread($h, 1024 * 1024);
                    flush();
                }
                fclose($h);
            }
            @unlink($zipPath);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * @return array{copied_files: int}
     */
    public function importZip(Company $targetCompany, UploadedFile $file): array
    {
        $tmp = $file->getRealPath();
        if (! is_string($tmp) || ! is_readable($tmp)) {
            throw new RuntimeException('Upload onleesbaar.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tmp) !== true) {
            throw new RuntimeException('Geen geldig ZIP-bestand.');
        }

        $manifestJson = $zip->getFromName('manifest.json');
        $zip->close();
        if (! is_string($manifestJson) || $manifestJson === '') {
            throw new RuntimeException('ZIP mist manifest.json.');
        }

        $manifest = json_decode($manifestJson, true);
        if (! is_array($manifest)) {
            throw new RuntimeException('Ongeldig manifest.');
        }
        if (($manifest['bundle_type'] ?? '') !== self::BUNDLE_TYPE) {
            throw new RuntimeException('Dit is geen tenant-media-bundle (verwacht bundle_type "'.self::BUNDLE_TYPE.'").');
        }
        if ((int) ($manifest['bundle_version'] ?? 0) !== self::BUNDLE_VERSION) {
            throw new RuntimeException('Onbekende bundle_version in manifest.');
        }

        $copied = $this->copyFilesFromZipToPublicDisk($tmp);

        return ['copied_files' => $copied];
    }

    /**
     * @return list<string>
     */
    private function collectPathsFromCompanyTables(int $companyId): array
    {
        $paths = [];
        $specs = [
            ['table' => 'users', 'column' => 'cv_path'],
            ['table' => 'notifications', 'column' => 'file_path'],
            ['table' => 'invoices', 'column' => 'pdf_path'],
        ];

        foreach ($specs as $spec) {
            $table = $spec['table'];
            $column = $spec['column'];
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id') || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $rows = DB::table($table)->where('company_id', $companyId)->whereNotNull($column)->pluck($column);
            foreach ($rows as $v) {
                if (is_string($v) && trim($v) !== '') {
                    $paths[] = $v;
                }
            }
        }

        if (Schema::hasTable('cv_files') && Schema::hasColumn('cv_files', 'file_path')
            && Schema::hasTable('users') && Schema::hasColumn('users', 'company_id')) {
            $rows = DB::table('cv_files')
                ->join('users', 'cv_files.user_id', '=', 'users.id')
                ->where('users.company_id', $companyId)
                ->whereNotNull('cv_files.file_path')
                ->pluck('cv_files.file_path');
            foreach ($rows as $v) {
                if (is_string($v) && trim($v) !== '') {
                    $paths[] = $v;
                }
            }
        }

        return $paths;
    }

    private function copyFilesFromZipToPublicDisk(string $zipRealPath): int
    {
        $zip = new ZipArchive;
        if ($zip->open($zipRealPath) !== true) {
            return 0;
        }

        $copied = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! str_starts_with($name, 'files/')) {
                continue;
            }
            $rel = substr($name, strlen('files/'));
            $rel = $this->websiteBundle->normalizeStorageRelativePath($rel);
            if ($rel === '' || str_contains($rel, '..')) {
                continue;
            }

            $target = storage_path('app/public/'.$rel);
            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $content = $zip->getFromIndex($i);
            if (is_string($content)) {
                file_put_contents($target, $content);
                $copied++;
            }
        }
        $zip->close();

        return $copied;
    }
}

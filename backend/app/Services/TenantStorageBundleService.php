<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GeneralSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * ZIP-export/import: tenant-bestanden + website-pagina’s + tenant-general_settings in één bundle (v2).
 * Legacy v1: alleen manifest + files/ (zelfde bundle_type).
 */
final class TenantStorageBundleService
{
    public const BUNDLE_VERSION = 2;

    public const BUNDLE_VERSION_LEGACY = 1;

    public const BUNDLE_TYPE = 'tenant_media';

    public function __construct(
        protected TenantWebsiteBundleService $websiteBundle
    ) {}

    /**
     * Alle publieke storage-bestanden voor de tenant: expliciet uit DB/pagina’s + volledige bomen onder standaard-uploadmappen.
     *
     * @return list<string> relatieve paden t.o.v. storage/app/public
     */
    public function collectAllTenantStoragePaths(Company $company): array
    {
        $paths = $this->websiteBundle->collectWebsiteMediaPathsForCompany($company);
        $paths = array_merge($paths, $this->websiteBundle->collectTenantGeneralSettingsStoragePaths((int) $company->id));

        $logo = $company->getAttribute('logo_path');
        if (is_string($logo) && trim($logo) !== '') {
            $paths[] = $logo;
        }

        $paths = array_merge($paths, $this->collectPathsFromCompanyTables((int) $company->id));
        $paths = array_merge($paths, $this->collectRecursiveStandardPublicUploadDirs());

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

    /**
     * Private app-bestanden (factuur-PDF’s e.d.) onder storage/app. Versleutelde website_media
     * gaat NIET via private_files/ maar via media-plain/ (her-versleuteld op import → cross-key veilig).
     *
     * @return list<string> paden relatief t.o.v. storage/app
     */
    public function collectPrivateAppPathsForExport(Company $company): array
    {
        $paths = $this->collectPrivateInvoicePdfPaths((int) $company->id);

        $unique = [];
        foreach ($paths as $p) {
            $n = str_replace('\\', '/', trim((string) $p, '/'));
            if ($n !== '' && ! str_contains($n, '..')) {
                $unique[$n] = true;
            }
        }
        $keys = array_keys($unique);
        sort($keys);

        return $keys;
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    private function buildGeneralSettingsExportPayload(int $companyId): array
    {
        if (! Schema::hasTable('general_settings')) {
            return [];
        }

        $rows = GeneralSetting::query()
            ->where('company_id', $companyId)
            ->get(['key', 'value']);

        $out = [];
        foreach ($rows as $row) {
            $key = (string) $row->getAttribute('key');
            if ($key === '' || GeneralSetting::isGlobalPlatformKey($key)) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'value' => (string) $row->getAttribute('value'),
            ];
        }

        return $out;
    }

    public function exportZip(Company $company): StreamedResponse
    {
        $allPaths = $this->collectAllTenantStoragePaths($company);
        $privateAppPaths = $this->collectPrivateAppPathsForExport($company);
        $pagePayloads = $this->websiteBundle->buildPageExportPayloads($company);
        $settingsPayload = $this->buildGeneralSettingsExportPayload((int) $company->id);
        $mediaPayloads = $this->websiteBundle->buildWebsiteMediaExportPayloads($company);
        $userPhotos = $this->buildUserPhotoExportPayload((int) $company->id);

        $manifest = [
            'bundle_type' => self::BUNDLE_TYPE,
            'bundle_version' => self::BUNDLE_VERSION,
            'exported_at' => now()->toIso8601String(),
            'source_company_id' => (int) $company->id,
            'source_company_name' => (string) $company->name,
            'pages' => $pagePayloads,
            'general_settings' => $settingsPayload,
            'storage_paths' => $allPaths,
            'private_storage_paths' => $privateAppPaths,
            'website_media' => $mediaPayloads,
            'user_photos' => $userPhotos,
            'payment_storage_note' => 'Factuur-PDF’s staan op de local disk (storage/app/private/invoices/{company_id}/) als private_files/private/invoices/… in de ZIP. Betalingsproviders en transacties zitten in de database en gaan via tenant-sync (payment_providers, invoice_settings, invoices, payments, payment_reminders, ride_payments).',
            'note' => 'ZIP: files/ = storage/app/public; private_files/ = storage/app (factuur-PDF’s). Carousel/slider-media (website_media) gaan ontsleuteld onder media-plain/ en worden bij import met de doel-APP_KEY her-versleuteld. Profielfoto’s (user_photos) overschrijven de avatar van bestaande doel-gebruikers (match op e-mail).',
        ];

        $safeSlug = Str::slug($company->name) ?: 'company';
        $filename = 'tenant-export-'.$company->id.'-'.$safeSlug.'-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($manifest, $allPaths, $privateAppPaths, $mediaPayloads) {
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
                    $this->zipAddFileOrFromString($zip, $abs, 'files/'.$rel);
                }
            }

            foreach ($privateAppPaths as $rel) {
                $rel = str_replace('\\', '/', trim((string) $rel, '/'));
                if ($rel === '' || str_contains($rel, '..')) {
                    continue;
                }
                $abs = $this->absolutePrivateStoragePath($rel);
                if ($abs !== null) {
                    $this->zipAddFileOrFromString($zip, $abs, 'private_files/'.$rel);
                }
            }

            // Carousel/slider-media: ontsleuteld onder media-plain/ (cross-key veilig), anders ruw onder media-local/.
            $this->websiteBundle->addWebsiteMediaFilesToZip($zip, $mediaPayloads);

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
     * @return array{copied_files: int, imported_pages: int, imported_settings: int}
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

        $version = (int) ($manifest['bundle_version'] ?? 0);
        if ($version !== self::BUNDLE_VERSION && $version !== self::BUNDLE_VERSION_LEGACY) {
            throw new RuntimeException('Onbekende bundle_version in manifest (ondersteund: '.self::BUNDLE_VERSION_LEGACY.' of '.self::BUNDLE_VERSION.').');
        }

        $copied = $this->copyFilesFromZipToPublicDisk($tmp)
            + $this->copyPrivateFilesFromZipToAppDisk($tmp);

        $importedPages = 0;
        $importedSettings = 0;
        $importedPhotos = 0;

        if ($version === self::BUNDLE_VERSION) {
            $pages = $manifest['pages'] ?? [];
            if (is_array($pages) && $pages !== []) {
                $importedPages = $this->websiteBundle->importWebsitePagesFromManifestEntries($targetCompany, $pages);
            }

            $settings = $manifest['general_settings'] ?? [];
            if (is_array($settings) && $settings !== []) {
                $importedSettings = $this->importGeneralSettingsPayload($targetCompany, $settings);
            }

            $mediaEntries = is_array($manifest['website_media'] ?? null) ? $manifest['website_media'] : [];
            $copied += $this->websiteBundle->restoreWebsiteMediaFromZip($tmp, $mediaEntries);

            $userPhotos = is_array($manifest['user_photos'] ?? null) ? $manifest['user_photos'] : [];
            $importedPhotos = $this->importUserPhotos($targetCompany, $userPhotos);
        }

        return [
            'copied_files' => $copied,
            'imported_pages' => $importedPages,
            'imported_settings' => $importedSettings,
            'imported_photos' => $importedPhotos,
        ];
    }

    /**
     * Profielfoto’s (users.photo_blob, base64) van company-gebruikers exporteren, op e-mail te matchen.
     *
     * @return list<array{email: string, photo_blob: string, photo_mime_type: ?string}>
     */
    private function buildUserPhotoExportPayload(int $companyId): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'photo_blob') || ! Schema::hasColumn('users', 'email')) {
            return [];
        }

        $hasMime = Schema::hasColumn('users', 'photo_mime_type');
        $columns = ['email', 'photo_blob'];
        if ($hasMime) {
            $columns[] = 'photo_mime_type';
        }

        $rows = DB::table('users')
            ->where('company_id', $companyId)
            ->whereNotNull('photo_blob')
            ->where('photo_blob', '!=', '')
            ->get($columns);

        $out = [];
        foreach ($rows as $row) {
            $email = trim((string) ($row->email ?? ''));
            $blob = (string) ($row->photo_blob ?? '');
            if ($email === '' || $blob === '') {
                continue;
            }
            $out[] = [
                'email' => $email,
                'photo_blob' => $blob,
                'photo_mime_type' => $hasMime ? ($row->photo_mime_type ?? null) : null,
            ];
        }

        return $out;
    }

    /**
     * Profielfoto’s terugzetten op bestaande doel-gebruikers (match op e-mail binnen de tenant).
     * Overschrijft een bestaande avatar als die er al staat (zoals gevraagd).
     *
     * @param  list<array<string, mixed>|mixed>  $entries
     */
    private function importUserPhotos(Company $targetCompany, array $entries): int
    {
        if ($entries === [] || ! Schema::hasTable('users') || ! Schema::hasColumn('users', 'photo_blob') || ! Schema::hasColumn('users', 'email')) {
            return 0;
        }

        $hasMime = Schema::hasColumn('users', 'photo_mime_type');
        $hasPhotoPath = Schema::hasColumn('users', 'photo');
        $hasUpdatedAt = Schema::hasColumn('users', 'updated_at');

        $n = 0;
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $email = isset($entry['email']) && is_string($entry['email']) ? trim($entry['email']) : '';
            $blob = $entry['photo_blob'] ?? null;
            if ($email === '' || ! is_string($blob) || $blob === '') {
                continue;
            }

            $update = ['photo_blob' => $blob];
            if ($hasMime) {
                $mime = $entry['photo_mime_type'] ?? null;
                $update['photo_mime_type'] = is_string($mime) && $mime !== '' ? $mime : null;
            }
            if ($hasPhotoPath) {
                $update['photo'] = null;
            }
            if ($hasUpdatedAt) {
                $update['updated_at'] = now();
            }

            $affected = DB::table('users')
                ->where('company_id', (int) $targetCompany->id)
                ->where('email', $email)
                ->update($update);

            if ($affected > 0) {
                $n++;
            }
        }

        return $n;
    }

    /**
     * @param  list<array<string, mixed>|mixed>  $entries
     */
    private function importGeneralSettingsPayload(Company $targetCompany, array $entries): int
    {
        if (! Schema::hasTable('general_settings')) {
            return 0;
        }

        $n = 0;
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }
            $key = isset($entry['key']) && is_string($entry['key']) ? trim($entry['key']) : '';
            if ($key === '' || GeneralSetting::isGlobalPlatformKey($key)) {
                continue;
            }
            $value = $entry['value'] ?? '';
            if (is_bool($value) || is_int($value) || is_float($value)) {
                $value = (string) $value;
            } elseif (! is_string($value)) {
                continue;
            }

            GeneralSetting::query()->updateOrCreate(
                ['key' => $key, 'company_id' => (int) $targetCompany->id],
                ['value' => $value]
            );
            $n++;
        }

        return $n;
    }

    /**
     * Standaard uploadmappen onder public volledig meenemen (plaatjes/documenten die niet overal in JSON staan).
     *
     * @return list<string>
     */
    private function collectRecursiveStandardPublicUploadDirs(): array
    {
        $out = [];
        foreach (['website', 'settings', 'vehicles'] as $root) {
            $out = array_merge($out, $this->listFilesRecursivelyUnderPublicPrefix($root));
        }

        $modulesRoot = storage_path('app/public/modules');
        if (is_dir($modulesRoot)) {
            foreach (scandir($modulesRoot) as $slug) {
                if ($slug === '.' || $slug === '..') {
                    continue;
                }
                $out = array_merge($out, $this->listFilesRecursivelyUnderPublicPrefix('modules/'.$slug.'/website'));
            }
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function listFilesRecursivelyUnderPublicPrefix(string $prefixRelative): array
    {
        $prefixRelative = trim(str_replace('\\', '/', $prefixRelative), '/');
        if ($prefixRelative === '' || str_contains($prefixRelative, '..')) {
            return [];
        }

        $abs = storage_path('app/public/'.$prefixRelative);
        if (! is_dir($abs)) {
            return [];
        }

        $base = realpath(storage_path('app/public'));
        if ($base === false) {
            return [];
        }

        $out = [];
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($abs, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $real = $file->getRealPath();
                if ($real === false) {
                    continue;
                }
                $rel = ltrim(str_replace('\\', '/', substr($real, strlen($base))), '/');
                if ($rel !== '' && ! str_contains($rel, '..')) {
                    $out[] = $rel;
                }
                if (count($out) >= 8000) {
                    break;
                }
            }
        } catch (\Throwable) {
            // permissies / verwijderde map tijdens iteratie
        }

        return $out;
    }

    private function zipAddFileOrFromString(ZipArchive $zip, string $absolutePath, string $zipEntryName): void
    {
        if ($zip->addFile($absolutePath, $zipEntryName)) {
            return;
        }
        $bytes = file_get_contents($absolutePath);
        if (is_string($bytes) && $bytes !== '') {
            $zip->addFromString($zipEntryName, $bytes);
        }
    }

    /**
     * @return list<string>
     */
    /**
     * Factuur-PDF’s op de Laravel-local disk (storage/app/private), niet op public.
     * Manifest/export-paden: {@see privateStorageZipRelative}.
     *
     * @return list<string>
     */
    private function collectPrivateInvoicePdfPaths(int $companyId): array
    {
        $paths = [];
        $dbRels = [];

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'company_id') && Schema::hasColumn('invoices', 'pdf_path')) {
            $rows = DB::table('invoices')
                ->where('company_id', $companyId)
                ->whereNotNull('pdf_path')
                ->pluck('pdf_path');
            foreach ($rows as $v) {
                if (! is_string($v)) {
                    continue;
                }
                $rel = str_replace('\\', '/', trim($v, '/'));
                if ($rel !== '' && ! str_contains($rel, '..') && str_starts_with($rel, 'invoices/')) {
                    $dbRels[$rel] = true;
                }
            }
        }

        foreach (array_keys($dbRels) as $dbRel) {
            if ($this->absolutePrivateStoragePath($this->privateStorageZipRelative($dbRel)) !== null) {
                $paths[] = $this->privateStorageZipRelative($dbRel);
            }
        }

        $scanDirs = [
            storage_path('app/private/invoices/'.$companyId),
            storage_path('app/invoices/'.$companyId),
        ];
        foreach ($scanDirs as $dir) {
            if (! is_dir($dir)) {
                continue;
            }
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iterator as $file) {
                    if (! $file->isFile()) {
                        continue;
                    }
                    $name = $file->getFilename();
                    $dbRel = 'invoices/'.$companyId.'/'.$name;
                    $zipRel = $this->privateStorageZipRelative($dbRel);
                    if ($this->absolutePrivateStoragePath($zipRel) !== null) {
                        $paths[$zipRel] = true;
                    }
                }
            } catch (\Throwable) {
                // permissies
            }
        }

        return array_keys($paths);
    }

    /**
     * ZIP/import-pad onder storage/app (local disk = app/private/…).
     */
    private function privateStorageZipRelative(string $dbRelativePath): string
    {
        $dbRelativePath = str_replace('\\', '/', trim($dbRelativePath, '/'));
        if ($dbRelativePath === '' || str_contains($dbRelativePath, '..')) {
            return $dbRelativePath;
        }
        if (is_file(storage_path('app/private/'.$dbRelativePath))) {
            return 'private/'.$dbRelativePath;
        }

        return $dbRelativePath;
    }

    private function absolutePrivateStoragePath(string $zipRelativePath): ?string
    {
        $zipRelativePath = str_replace('\\', '/', trim($zipRelativePath, '/'));
        if ($zipRelativePath === '' || str_contains($zipRelativePath, '..')) {
            return null;
        }

        $candidates = [storage_path('app/'.$zipRelativePath)];
        if (str_starts_with($zipRelativePath, 'private/')) {
            $without = substr($zipRelativePath, strlen('private/'));
            $candidates[] = storage_path('app/private/'.$without);
        } elseif (str_starts_with($zipRelativePath, 'invoices/')) {
            $candidates[] = storage_path('app/private/'.$zipRelativePath);
        }

        foreach ($candidates as $abs) {
            if (is_file($abs) && is_readable($abs)) {
                return $abs;
            }
        }

        return null;
    }

    private function collectPathsFromCompanyTables(int $companyId): array
    {
        $paths = [];
        $specs = [
            ['table' => 'users', 'column' => 'cv_path'],
            ['table' => 'notifications', 'column' => 'file_path'],
            ['table' => 'invoice_settings', 'column' => 'logo_path'],
        ];

        foreach ($specs as $spec) {
            $table = $spec['table'];
            $column = $spec['column'];
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'company_id') || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $rows = DB::table($table)->where('company_id', $companyId)->whereNotNull($column)->pluck($column);
            foreach ($rows as $v) {
                if (! is_string($v) || trim($v) === '') {
                    continue;
                }
                $rel = $this->websiteBundle->normalizeStorageRelativePath($v);
                if ($rel === '' || str_contains($rel, '..') || str_starts_with($rel, 'invoices/')) {
                    continue;
                }
                $paths[] = $rel;
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

    private function copyPrivateFilesFromZipToAppDisk(string $zipRealPath): int
    {
        $zip = new ZipArchive;
        if ($zip->open($zipRealPath) !== true) {
            return 0;
        }

        $copied = 0;
        $prefix = 'private_files/';
        $plen = strlen($prefix);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (! is_string($name) || ! str_starts_with($name, $prefix)) {
                continue;
            }
            $rel = substr($name, $plen);
            $rel = str_replace('\\', '/', trim($rel, '/'));
            if ($rel === '' || str_contains($rel, '..')) {
                continue;
            }

            $target = storage_path('app/'.$rel);
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

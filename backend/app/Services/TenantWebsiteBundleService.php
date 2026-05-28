<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FrontendTheme;
use App\Models\GeneralSetting;
use App\Support\TenantSync\TenantSyncConnectionConfig;
use App\Support\TenantSync\TenantSyncDatabaseUrl;
use App\Models\WebsiteMedia;
use App\Models\WebsitePage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\ConfigurationUrlParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Export / import van tenant-website (website_pages + public storage-bestanden).
 * Push naar een tweede database: alleen rijen (geen bestanden over het net); gebruik ZIP-import op PROD voor media.
 */
final class TenantWebsiteBundleService
{
    public const BUNDLE_VERSION = 1;

    public const SYNC_CONNECTION = 'tenant_website_sync_target';

    public const SYNC_MODULE_TAXI_CONNECTION = 'tenant_sync_module_taxi';

    public function __construct(
        protected WebsiteBuilderService $websiteBuilder,
        protected TenantSyncSettingsService $tenantSyncSettings,
        protected TenantSyncSshTunnelService $sshTunnel,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runWithSyncTarget(callable $callback): mixed
    {
        return $this->sshTunnel->runIsolated($callback);
    }

    public function syncTargetDatabaseUrl(): ?string
    {
        $url = $this->tenantSyncSettings->connectionConfig()->resolvedDatabaseUrl();

        return $url !== '' ? $url : null;
    }

    /**
     * Voorgestelde doel-URL voor het admin-veld (prefill-knop).
     * Volgorde: TENANT_SYNC_TARGET_DATABASE_URL → DB_URL → huidige default database-connection.
     */
    public function suggestedTargetDatabaseUrl(): ?string
    {
        $fromEnv = trim((string) env('TENANT_SYNC_TARGET_DATABASE_URL', ''));
        if ($fromEnv !== '') {
            return $fromEnv;
        }

        $dbUrl = trim((string) env('DB_URL', ''));
        if ($dbUrl !== '') {
            return $dbUrl;
        }

        $built = $this->buildDatabaseUrlFromConfig();

        return $built !== null ? TenantSyncDatabaseUrl::stripPassword($built) : null;
    }

    private function buildDatabaseUrlFromConfig(): ?string
    {
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            return null;
        }

        if (! empty($config['url'])) {
            return trim((string) $config['url']);
        }

        $driver = (string) ($config['driver'] ?? $connection);
        if (! in_array($driver, ['pgsql', 'mysql', 'mariadb'], true)) {
            return null;
        }

        $host = (string) ($config['host'] ?? '');
        $database = (string) ($config['database'] ?? '');
        if ($host === '' || $database === '') {
            return null;
        }

        $port = $config['port'] ?? null;
        $portPart = $port !== null && $port !== '' ? ':'.(int) $port : '';

        $username = rawurlencode((string) ($config['username'] ?? ''));
        $password = rawurlencode((string) ($config['password'] ?? ''));
        $authority = $username;
        if ($password !== '') {
            $authority .= ':'.$password;
        }
        if ($authority !== '') {
            $authority .= '@';
        }

        return "{$driver}://{$authority}{$host}{$portPart}/{$database}";
    }

    public function isPushEnabledInSettings(): bool
    {
        return GeneralSetting::get('tenant_sync_push_enabled', '0') === '1';
    }

    public function isPushGloballyEnabled(): bool
    {
        return $this->isPushEnabledInSettings()
            || filter_var(env('TENANT_SYNC_PUSH_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function pushAllowedForEnvironment(): bool
    {
        if (! app()->isProduction()) {
            return true;
        }

        return filter_var(env('TENANT_SYNC_ALLOW_PRODUCTION_PUSH', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function testSyncConnection(?string $url = null, ?TenantSyncConnectionConfig $config = null): void
    {
        $this->sshTunnel->runIsolated(function () use ($url, $config) {
            $this->registerSyncConnection($url, $config);
            try {
                DB::connection(self::SYNC_CONNECTION)->getPdo();
            } finally {
                DB::purge(self::SYNC_CONNECTION);
            }
        }, $config);
    }

    public function registerSyncConnection(?string $url = null, ?TenantSyncConnectionConfig $config = null): void
    {
        $config ??= $this->tenantSyncSettings->connectionConfig();
        $url = $this->resolveTargetUrl($url, $config);
        if ($url === '') {
            throw new RuntimeException('Geen doel-database-URL geconfigureerd (Instellingen → Omgeving-sync of TENANT_SYNC_TARGET_DATABASE_URL).');
        }

        $url = $this->sshTunnel->applyTunnelToDatabaseUrl($url, $config);

        $plainPassword = $config->databasePassword;
        if ($plainPassword === null || $plainPassword === '') {
            $plainPassword = TenantSyncDatabaseUrl::extractPassword($url);
        }

        $urlWithoutPassword = TenantSyncDatabaseUrl::stripPassword($url);

        $parser = new ConfigurationUrlParser;
        $parsed = $parser->parseConfiguration(['url' => $urlWithoutPassword]);
        if (empty($parsed['driver'])) {
            throw new RuntimeException('Database-URL kon niet worden geïnterpreteerd (driver ontbreekt).');
        }

        if ($plainPassword !== null && $plainPassword !== '') {
            $parsed['password'] = $plainPassword;
        }

        $defaultConn = (string) config('database.default');
        $defaults = (array) config("database.connections.{$defaultConn}", []);

        $merged = array_merge(
            Arr::only($defaults, ['charset', 'collation', 'prefix', 'prefix_indexes', 'strict', 'engine', 'options']),
            $parsed,
            [
                'prefix' => $parsed['prefix'] ?? '',
            ]
        );

        config(['database.connections.'.self::SYNC_CONNECTION => $merged]);
        DB::purge(self::SYNC_CONNECTION);
    }

    /**
     * Module-taxi connection naar het sync-doel (zelfde host/DB als SYNC_CONNECTION, schema/search_path van module_taxi).
     */
    public function registerSyncModuleTaxiConnection(): string
    {
        $sync = config('database.connections.'.self::SYNC_CONNECTION);
        if (! is_array($sync)) {
            throw new RuntimeException('Sync-connection niet geregistreerd; roep eerst registerSyncConnection() aan.');
        }

        $dbService = app(ModuleDatabaseService::class);
        $moduleName = (string) (config('tenant_sync.taxi_module.module_name') ?? 'taxi');
        $merged = $sync;

        if ($dbService->usesSchemaStrategy()) {
            $schema = $dbService->getModuleSchemaName($moduleName);
            $merged['search_path'] = $schema.',public';
        } elseif ($dbService->usesDatabaseStrategy()) {
            $merged['database'] = $dbService->getModuleDatabaseName($moduleName);
        } else {
            $sourceModule = config('database.connections.'.app(ModuleDatabaseService::class)->getModuleConnectionName($moduleName));
            if (is_array($sourceModule) && ! empty($sourceModule['search_path'])) {
                $merged['search_path'] = $sourceModule['search_path'];
            }
        }

        config(['database.connections.'.self::SYNC_MODULE_TAXI_CONNECTION => $merged]);
        DB::purge(self::SYNC_MODULE_TAXI_CONNECTION);

        return self::SYNC_MODULE_TAXI_CONNECTION;
    }

    private function resolveTargetUrl(?string $url, TenantSyncConnectionConfig $config): string
    {
        if ($url !== null && trim($url) !== '') {
            $plain = trim($url);
            $password = $config->databasePassword;
            if ($password !== null && $password !== '') {
                return TenantSyncDatabaseUrl::injectPassword(
                    TenantSyncDatabaseUrl::stripPassword($plain),
                    $password
                );
            }

            return $plain;
        }

        return $config->resolvedDatabaseUrl();
    }

    /**
     * Relatieve paden onder storage/app/public die door website-pagina's van dit bedrijf worden gerefereerd.
     *
     * @return list<string>
     */
    public function collectWebsiteMediaPathsForCompany(Company $company): array
    {
        $pages = $this->websiteBuilder->loadAllPagesForAdminIndex((int) $company->id, true)
            ->reject(fn (WebsitePage $p) => WebsitePage::isCentralMarketingWelcomeSlug($p->slug));

        $allPaths = [];
        foreach ($pages as $page) {
            $paths = $this->collectReferencedPublicPaths($page);
            $allPaths = array_values(array_unique(array_merge($allPaths, $paths)));
        }

        return $allPaths;
    }

    /**
     * Publieke storage-paden die voorkomen in tenant-gebonden general_settings (logo’s, afbeeldingen in waarden, …).
     *
     * @return list<string>
     */
    public function collectTenantGeneralSettingsStoragePaths(int $companyId): array
    {
        if (! Schema::hasTable('general_settings')) {
            return [];
        }

        $paths = [];
        $values = GeneralSetting::query()
            ->where('company_id', $companyId)
            ->pluck('value');
        foreach ($values as $v) {
            if (is_string($v) && $v !== '') {
                $paths = array_merge($paths, $this->collectStoragePathsFromText($v));
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * @return list<array{connection: string, theme_slug: ?string, attributes: array<string, mixed>}>
     */
    public function buildPageExportPayloads(Company $company): array
    {
        $pages = $this->websiteBuilder->loadAllPagesForAdminIndex((int) $company->id, true)
            ->reject(fn (WebsitePage $p) => WebsitePage::isCentralMarketingWelcomeSlug($p->slug));

        $pagePayloads = [];
        foreach ($pages as $page) {
            $themeSlug = null;
            if ($page->relationLoaded('theme') && $page->getRelation('theme') instanceof FrontendTheme) {
                $themeSlug = $page->getRelation('theme')->slug;
            } elseif ($page->frontend_theme_id) {
                $themeSlug = FrontendTheme::query()->whereKey($page->frontend_theme_id)->value('slug');
            }

            $attrs = $page->getAttributes();
            unset($attrs['theme']);

            $pagePayloads[] = [
                'connection' => $page->getConnection()->getName(),
                'theme_slug' => $themeSlug,
                'attributes' => $attrs,
            ];
        }

        return $pagePayloads;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    public function importWebsitePagesFromManifestEntries(Company $targetCompany, array $pages): int
    {
        $imported = 0;
        foreach ($pages as $entry) {
            if (! is_array($entry) || ! isset($entry['attributes']) || ! is_array($entry['attributes'])) {
                continue;
            }
            $conn = isset($entry['connection']) && is_string($entry['connection']) ? $entry['connection'] : (string) config('database.default');
            $themeSlug = isset($entry['theme_slug']) && is_string($entry['theme_slug']) ? $entry['theme_slug'] : null;

            $attrs = $entry['attributes'];
            unset($attrs['id']);
            $attrs['company_id'] = (int) $targetCompany->id;

            if ($themeSlug) {
                $tid = FrontendTheme::query()->where('slug', $themeSlug)->value('id');
                if ($tid) {
                    $attrs['frontend_theme_id'] = (int) $tid;
                }
            }

            $slug = (string) ($attrs['slug'] ?? '');
            $moduleName = $attrs['module_name'] ?? null;

            $q = WebsitePage::on($conn)->where('company_id', (int) $targetCompany->id)->where('slug', $slug);
            if ($moduleName === null || $moduleName === '') {
                $q->whereNull('module_name');
            } else {
                $q->where('module_name', $moduleName);
            }

            $model = $q->first();
            if ($model === null) {
                $model = new WebsitePage;
                $model->setConnection($conn);
            }
            $model->fill($attrs);
            $model->save();
            $imported++;
        }

        return $imported;
    }

    public function exportZip(Company $company): StreamedResponse
    {
        $allPaths = $this->collectWebsiteMediaPathsForCompany($company);

        $pagePayloads = $this->buildPageExportPayloads($company);

        $manifest = [
            'bundle_version' => self::BUNDLE_VERSION,
            'exported_at' => now()->toIso8601String(),
            'source_company_id' => (int) $company->id,
            'source_company_name' => (string) $company->name,
            'pages' => $pagePayloads,
            'storage_paths' => $allPaths,
            'note' => 'Import overschrijft website_pages per slug/module_name/company_id op de doelomgeving. Bestanden onder storage/app/public worden toegevoegd.',
        ];

        $safeSlug = Str::slug($company->name) ?: 'company';
        $filename = 'website-bundle-'.$company->id.'-'.$safeSlug.'-'.now()->format('Y-m-d-His').'.zip';

        return response()->streamDownload(function () use ($manifest, $allPaths) {
            $zipPath = tempnam(sys_get_temp_dir(), 'nexa_wb_');
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
                $rel = $this->normalizeStorageRelativePath((string) $rel);
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
     * @return array{imported_pages: int, copied_files: int}
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
        if (! is_array($manifest) || (int) ($manifest['bundle_version'] ?? 0) !== self::BUNDLE_VERSION) {
            throw new RuntimeException('Onbekende of ontbrekende bundle_version in manifest.');
        }

        $pages = $manifest['pages'] ?? null;
        if (! is_array($pages)) {
            throw new RuntimeException('Manifest mist pages-array.');
        }

        $imported = $this->importWebsitePagesFromManifestEntries($targetCompany, $pages);

        $copied = $this->copyFilesFromZipToPublicDisk($tmp);

        return ['imported_pages' => $imported, 'copied_files' => $copied];
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
            $rel = $this->normalizeStorageRelativePath($rel);
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

    /**
     * @return list<string> relatieve paden t.o.v. storage/app/public
     */
    private function collectReferencedPublicPaths(WebsitePage $page): array
    {
        $blob = json_encode([
            'home_sections' => $page->home_sections,
            'content' => $page->content,
            'meta_description' => $page->meta_description,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($blob)) {
            return [];
        }

        return array_slice($this->collectStoragePathsFromText($blob), 0, 500);
    }

    /**
     * @return list<string>
     */
    public function collectStoragePathsFromText(string $blob): array
    {
        $paths = [];
        if (preg_match_all('#/storage/([^"\'\s<>]+)#i', $blob, $m)) {
            foreach ($m[1] as $raw) {
                $p = $this->normalizeStorageRelativePath(rawurldecode((string) $raw));
                if ($p !== '') {
                    $paths[] = $p;
                }
            }
        }

        if (preg_match_all('#(?<=[/"\'\s])(?:settings|modules|vehicles|website)/[^"\'\s<>]+\.(?:jpe?g|png|gif|webp|svg|pdf|docx?|xlsx?|pptx?|txt|csv)(?:\?[^"\'\s]*)?#i', $blob, $m2)) {
            foreach ($m2[0] as $raw) {
                $p = $this->normalizeStorageRelativePath((string) $raw);
                if ($p !== '') {
                    $paths[] = $p;
                }
            }
        }

        // /file/encoded  →  storage/app/public (encoded gebruikt "--" i.p.v. "/")
        if (preg_match_all('#(?:https?://[^/"\']+)?/file/([^"\'\\s<>]+)#i', $blob, $mf)) {
            foreach ($mf[1] as $enc) {
                $decoded = str_replace('--', '/', rawurldecode((string) $enc));
                $p = $this->normalizeStorageRelativePath($decoded);
                if ($p !== '') {
                    $paths[] = $p;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    /**
     * Versleutelde carousel-/website-media op schijf "local" (storage/app/…), gerefereerd vanuit pagina’s van dit bedrijf.
     *
     * @return list<string> paden relatief t.o.v. storage/app (niet public)
     */
    public function collectEncryptedWebsiteMediaAppPathsForCompany(Company $company): array
    {
        if (! Schema::hasTable('website_media')) {
            return [];
        }

        $pages = $this->websiteBuilder->loadAllPagesForAdminIndex((int) $company->id, true)
            ->reject(fn (WebsitePage $p) => WebsitePage::isCentralMarketingWelcomeSlug($p->slug));

        $blob = '';
        foreach ($pages as $page) {
            $blob .= json_encode([
                'home_sections' => $page->home_sections,
                'content' => $page->content,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).' ';
        }

        if ($blob === '') {
            return [];
        }

        if (! preg_match_all('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $blob, $mu)) {
            return [];
        }

        $uuids = array_values(array_unique(array_slice($mu[0], 0, 500)));
        if ($uuids === []) {
            return [];
        }

        $paths = WebsiteMedia::query()
            ->whereIn('uuid', $uuids)
            ->pluck('encrypted_path')
            ->filter(fn ($p) => is_string($p) && trim($p) !== '' && ! str_contains((string) $p, '..'))
            ->map(fn ($p) => str_replace('\\', '/', trim((string) $p, '/')))
            ->unique()
            ->values()
            ->all();

        return array_values($paths);
    }

    public function normalizeStorageRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }
}

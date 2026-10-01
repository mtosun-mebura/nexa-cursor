<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Module;
use App\Services\CompanyEmailLogoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Logo’s voor chauffeur- en contract-app.
 *
 * - Tenant met eigen merk (company logo) → tenantlogo (zoals nu).
 * - Marketplace / network (geen eigen merk) → NEXA | TAXI (module-upload of defaults).
 */
final class TaxiAppLogoService
{
    public const CONFIG_LIGHT = 'app_logo_light';

    public const CONFIG_DARK = 'app_logo_dark';

    public const DEFAULT_LIGHT = 'images/nexa-taxi-logo.png';

    public const DEFAULT_DARK = 'images/nexa-taxi-logo-dark.png';

    public function __construct(
        protected CompanyEmailLogoService $companyLogos,
    ) {}

    /**
     * @return array{light: ?string, dark: ?string}
     */
    public function pwaLogoUrls(?int $companyId): array
    {
        if ($companyId !== null && $companyId > 0 && $this->companyLogos->hasLogoSource($companyId)) {
            return $this->companyLogos->pwaLogoUrls($companyId);
        }

        return $this->nexaTaxiLogoUrls();
    }

    /**
     * @return array{light: string, dark: string}
     */
    public function nexaTaxiLogoUrls(): array
    {
        $config = $this->taxiModuleConfiguration();
        $lightPath = $this->normalizedStoragePath($config[self::CONFIG_LIGHT] ?? null);
        $darkPath = $this->normalizedStoragePath($config[self::CONFIG_DARK] ?? null);

        $light = $lightPath !== null
            ? $this->publicUrlForStoragePath($lightPath)
            : asset(self::DEFAULT_LIGHT);

        $dark = $darkPath !== null
            ? $this->publicUrlForStoragePath($darkPath)
            : asset(self::DEFAULT_DARK);

        return ['light' => $light, 'dark' => $dark];
    }

    /**
     * @return array{light: ?string, dark: ?string}
     */
    public function uploadedPreviewUrls(): array
    {
        $config = $this->taxiModuleConfiguration();
        $lightPath = $this->normalizedStoragePath($config[self::CONFIG_LIGHT] ?? null);
        $darkPath = $this->normalizedStoragePath($config[self::CONFIG_DARK] ?? null);

        return [
            'light' => $lightPath !== null ? $this->publicUrlForStoragePath($lightPath) : null,
            'dark' => $darkPath !== null ? $this->publicUrlForStoragePath($darkPath) : null,
        ];
    }

    /**
     * @return array{light: ?string, dark: ?string} relative public-disk paths
     */
    public function storeUploads(
        ?UploadedFile $light,
        ?UploadedFile $dark,
        bool $removeLight = false,
        bool $removeDark = false,
    ): array {
        $module = $this->taxiModule();
        $config = $this->taxiModuleConfiguration();

        if ($removeLight) {
            $this->deleteStoragePath($config[self::CONFIG_LIGHT] ?? null);
            unset($config[self::CONFIG_LIGHT]);
        }
        if ($removeDark) {
            $this->deleteStoragePath($config[self::CONFIG_DARK] ?? null);
            unset($config[self::CONFIG_DARK]);
        }

        if ($light instanceof UploadedFile && $light->isValid()) {
            $this->deleteStoragePath($config[self::CONFIG_LIGHT] ?? null);
            $config[self::CONFIG_LIGHT] = $light->store('modules/taxi/logos', 'public');
        }
        if ($dark instanceof UploadedFile && $dark->isValid()) {
            $this->deleteStoragePath($config[self::CONFIG_DARK] ?? null);
            $config[self::CONFIG_DARK] = $dark->store('modules/taxi/logos', 'public');
        }

        if ($module !== null) {
            $module->configuration = $config;
            $module->save();
        }

        return [
            'light' => $this->normalizedStoragePath($config[self::CONFIG_LIGHT] ?? null),
            'dark' => $this->normalizedStoragePath($config[self::CONFIG_DARK] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function taxiModuleConfiguration(): array
    {
        $module = $this->taxiModule();
        if ($module === null) {
            return [];
        }

        return is_array($module->configuration) ? $module->configuration : [];
    }

    private function taxiModule(): ?Module
    {
        return Module::query()->whereRaw('LOWER(name) = ?', ['taxi'])->first();
    }

    private function normalizedStoragePath(mixed $path): ?string
    {
        if (! is_string($path)) {
            return null;
        }
        $path = trim($path);
        if ($path === '' || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    private function publicUrlForStoragePath(string $path): string
    {
        return Storage::disk('public')->url($path);
    }

    private function deleteStoragePath(mixed $path): void
    {
        if (! is_string($path) || trim($path) === '') {
            return;
        }
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

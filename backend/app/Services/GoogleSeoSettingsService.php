<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleSeoSettingsService
{
    public const KEY_PROPERTY_ID = 'GOOGLE_SEO_PROPERTY_ID';

    public const KEY_ANALYTICS_ID = 'GOOGLE_ANALYTICS_ID';

    public const KEY_TAG_MANAGER_ID = 'GOOGLE_TAG_MANAGER_ID';

    public const KEY_SITE_VERIFICATION = 'GOOGLE_SITE_VERIFICATION';

    public const KEY_META_DESCRIPTION = 'META_DESCRIPTION';

    public const KEY_META_KEYWORDS = 'META_KEYWORDS';

    public const KEY_SEARCH_CONSOLE_ENABLED = 'GOOGLE_SEARCH_CONSOLE_ENABLED';

    public const KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT = 'GOOGLE_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON';

    public const KEY_SEARCH_CONSOLE_SITEMAP_PATH = 'GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH';

    public const KEY_SEARCH_CONSOLE_AUTO_SITEMAP = 'GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP';

    /**
     * @return array<string, string>
     */
    public function formSettings(?int $companyId = null): array
    {
        $keys = [
            self::KEY_PROPERTY_ID,
            self::KEY_ANALYTICS_ID,
            self::KEY_TAG_MANAGER_ID,
            self::KEY_SITE_VERIFICATION,
            self::KEY_META_DESCRIPTION,
            self::KEY_META_KEYWORDS,
            self::KEY_SEARCH_CONSOLE_ENABLED,
            self::KEY_SEARCH_CONSOLE_SITEMAP_PATH,
            self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP,
        ];

        $values = GeneralSetting::getMany($keys, $companyId);

        return [
            self::KEY_PROPERTY_ID => (string) ($values[self::KEY_PROPERTY_ID] ?? ''),
            self::KEY_ANALYTICS_ID => (string) ($values[self::KEY_ANALYTICS_ID] ?? ''),
            self::KEY_TAG_MANAGER_ID => (string) ($values[self::KEY_TAG_MANAGER_ID] ?? ''),
            self::KEY_SITE_VERIFICATION => (string) ($values[self::KEY_SITE_VERIFICATION] ?? ''),
            self::KEY_META_DESCRIPTION => (string) ($values[self::KEY_META_DESCRIPTION] ?? ''),
            self::KEY_META_KEYWORDS => (string) ($values[self::KEY_META_KEYWORDS] ?? ''),
            self::KEY_SEARCH_CONSOLE_ENABLED => ($values[self::KEY_SEARCH_CONSOLE_ENABLED] ?? '0') === '1' ? '1' : '0',
            self::KEY_SEARCH_CONSOLE_SITEMAP_PATH => (string) ($values[self::KEY_SEARCH_CONSOLE_SITEMAP_PATH] ?? 'sitemap.xml'),
            self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP => ($values[self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP] ?? '1') === '1' ? '1' : '0',
            'service_account_configured' => $this->hasServiceAccount($companyId) ? '1' : '0',
            'service_account_client_email' => $this->serviceAccountClientEmail($companyId) ?? '',
        ];
    }

    /**
     * Tracking + verificatie voor frontend (tenant).
     *
     * @return array{
     *     analytics_id: string,
     *     tag_manager_id: string,
     *     site_verification: string,
     *     default_meta_description: string,
     *     default_meta_keywords: string,
     * }
     */
    public function trackingConfigForCompany(?int $companyId): array
    {
        $values = GeneralSetting::getMany([
            self::KEY_ANALYTICS_ID,
            self::KEY_TAG_MANAGER_ID,
            self::KEY_SITE_VERIFICATION,
            self::KEY_META_DESCRIPTION,
            self::KEY_META_KEYWORDS,
        ], $companyId);

        return [
            'analytics_id' => trim((string) ($values[self::KEY_ANALYTICS_ID] ?? '')),
            'tag_manager_id' => trim((string) ($values[self::KEY_TAG_MANAGER_ID] ?? '')),
            'site_verification' => trim((string) ($values[self::KEY_SITE_VERIFICATION] ?? '')),
            'default_meta_description' => trim((string) ($values[self::KEY_META_DESCRIPTION] ?? '')),
            'default_meta_keywords' => trim((string) ($values[self::KEY_META_KEYWORDS] ?? '')),
        ];
    }

    public function isSearchConsoleEnabled(?int $companyId): bool
    {
        return GeneralSetting::get(self::KEY_SEARCH_CONSOLE_ENABLED, '0', $companyId) === '1';
    }

    public function hasServiceAccount(?int $companyId): bool
    {
        return $this->readServiceAccountJson($companyId) !== null;
    }

    public function serviceAccountClientEmail(?int $companyId): ?string
    {
        $json = $this->readServiceAccountJson($companyId);
        if ($json === null) {
            return null;
        }

        $email = trim((string) ($json['client_email'] ?? ''));

        return $email !== '' ? $email : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readServiceAccountJson(?int $companyId): ?array
    {
        $stored = GeneralSetting::get(self::KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT, null, $companyId);
        if (! is_string($stored) || trim($stored) === '') {
            return null;
        }

        try {
            $plain = Crypt::decryptString($stored);
        } catch (\Throwable) {
            $plain = $stored;
        }

        $decoded = json_decode($plain, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function storeServiceAccountJson(?int $companyId, string $json): void
    {
        $json = trim($json);
        if ($json === '') {
            return;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            throw new \InvalidArgumentException('Ongeldige service account JSON. Plak het volledige JSON-bestand uit Google Cloud.');
        }

        GeneralSetting::set(
            self::KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT,
            Crypt::encryptString($json),
            $companyId
        );
    }

    public function propertySiteUrl(?int $companyId): ?string
    {
        $property = trim((string) GeneralSetting::get(self::KEY_PROPERTY_ID, '', $companyId));
        if ($property === '') {
            return null;
        }

        if (Str::startsWith($property, ['sc-domain:', 'http://', 'https://'])) {
            return $property;
        }

        return 'https://'.ltrim($property, '/');
    }

    public function sitemapPublicUrl(?int $companyId = null): string
    {
        $path = trim((string) GeneralSetting::get(self::KEY_SEARCH_CONSOLE_SITEMAP_PATH, 'sitemap.xml', $companyId));
        $path = ltrim($path !== '' ? $path : 'sitemap.xml', '/');

        return rtrim(url('/'), '/').'/'.$path;
    }

    public function shouldAutoSubmitSitemap(?int $companyId): bool
    {
        return GeneralSetting::get(self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP, '1', $companyId) === '1';
    }

    /**
     * @param  array<string, string|null>  $input
     */
    public function saveFromRequest(array $input, ?int $companyId): void
    {
        $map = [
            self::KEY_PROPERTY_ID => (string) ($input[self::KEY_PROPERTY_ID] ?? ''),
            self::KEY_ANALYTICS_ID => (string) ($input[self::KEY_ANALYTICS_ID] ?? ''),
            self::KEY_TAG_MANAGER_ID => (string) ($input[self::KEY_TAG_MANAGER_ID] ?? ''),
            self::KEY_SITE_VERIFICATION => (string) ($input[self::KEY_SITE_VERIFICATION] ?? ''),
            self::KEY_META_DESCRIPTION => (string) ($input[self::KEY_META_DESCRIPTION] ?? ''),
            self::KEY_META_KEYWORDS => (string) ($input[self::KEY_META_KEYWORDS] ?? ''),
            self::KEY_SEARCH_CONSOLE_ENABLED => ! empty($input[self::KEY_SEARCH_CONSOLE_ENABLED]) ? '1' : '0',
            self::KEY_SEARCH_CONSOLE_SITEMAP_PATH => (string) ($input[self::KEY_SEARCH_CONSOLE_SITEMAP_PATH] ?? 'sitemap.xml'),
            self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP => ! empty($input[self::KEY_SEARCH_CONSOLE_AUTO_SITEMAP]) ? '1' : '0',
        ];

        foreach ($map as $key => $value) {
            GeneralSetting::set($key, $value, $companyId);
        }

        $json = trim((string) ($input[self::KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT] ?? ''));
        if ($json !== '') {
            $this->storeServiceAccountJson($companyId, $json);
        }
    }
}

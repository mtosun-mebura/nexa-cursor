<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GeneralSetting extends Model
{
    /** Platform-breed: niet per tenant (sync-doel, vlag, WhatsApp Business API, algemene configuraties). */
    public const GLOBAL_PLATFORM_KEYS = [
        'nexa_release_version',
        'tenant_sync_target_database_url',
        'tenant_sync_target_database_password_enc',
        'tenant_sync_push_enabled',
        'tenant_sync_ssh_enabled',
        'tenant_sync_ssh_host',
        'tenant_sync_ssh_port',
        'tenant_sync_ssh_username',
        'tenant_sync_ssh_password_enc',
        'tenant_sync_ssh_remote_db_host',
        'tenant_sync_ssh_remote_db_port',
        'tenant_sync_ssh_db_username',
        'tenant_sync_ssh_db_database',
        'database_backup_enabled',
        'database_backup_frequency',
        'database_backup_time',
        'database_backup_retention_days',
        'database_backup_sync_target_id',
        'database_backup_last_run_at',
        'WHATSAPP_API_TOKEN',
        'WHATSAPP_PHONE_NUMBER_ID',
        'WHATSAPP_BUSINESS_ACCOUNT_ID',
        'WHATSAPP_API_VERSION',
        'WHATSAPP_WEBHOOK_VERIFY_TOKEN',
        'WHATSAPP_DEFAULT_MESSAGE',
        'WHATSAPP_BOOKING_TEMPLATE',
        'WHATSAPP_BOOKING_TEMPLATE_LANG',
        'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE',
        'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG',
        'WHATSAPP_BOOKING_DETAIL_FIELDS',
        'WHATSAPP_RIDE_STATUS_TEMPLATE',
        'WHATSAPP_RIDE_STATUS_TEMPLATE_LANG',
        'WHATSAPP_RIDE_STATUS_EVENTS',
        'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE',
        'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG',
        'WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED',
        'GOOGLE_MAPS_API_KEY',
        'GOOGLE_MAPS_MAP_ID',
        'GOOGLE_MAPS_ZOOM',
        'GOOGLE_MAPS_CENTER_LAT',
        'GOOGLE_MAPS_CENTER_LNG',
        'GOOGLE_MAPS_TYPE',
        // Algemene configuraties (admin.settings.general) — platform-breed
        'logo',
        'logo_dark',
        'logo_mode',
        'logo_size',
        'favicon',
        'site_name',
        'site_description',
        'ai_chat_enabled',
        'ai_chat_nexa_taxi_webhook_url',
        'admin_footer_brand',
        'info_request_success_title',
        'info_request_success_subtitle',
        'info_request_success_footer',
        'info_request_success_texts_enabled',
        'info_request_success_icon',
        'info_request_success_icon_size',
        'info_request_success_image_size_percent',
        'info_request_success_image',
        'nexa_pricing',
        'frontend_component_disabled_overrides',
    ];

    /** Tenant-mailserver; leeg = NEXA Suite-mailserver (`company_id` null) of `.env`. */
    public const MAIL_SETTING_KEYS = [
        'MAIL_MAILER',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
        'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
    ];

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    /** @var array<string, bool> */
    private static array $tableExistsCache = [];

    /** @var array<string, mixed> */
    private static array $getCache = [];

    private static ?int $resolvedScopeCompanyId = null;

    private static bool $resolvedScopeCompanyIdComputed = false;

    public static function clearRequestCache(): void
    {
        self::$getCache = [];
        self::$tableExistsCache = [];
        self::$resolvedScopeCompanyId = null;
        self::$resolvedScopeCompanyIdComputed = false;
    }

    public static function isGlobalPlatformKey(string $key): bool
    {
        if (in_array($key, self::GLOBAL_PLATFORM_KEYS, true)) {
            return true;
        }

        // AI-chat module webhooks op Algemene configuraties
        return str_starts_with($key, 'ai_chat_') && str_ends_with($key, '_webhook_url');
    }

    public static function isMailSettingKey(string $key): bool
    {
        return in_array($key, self::MAIL_SETTING_KEYS, true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    private static function settingsTableAvailable(): bool
    {
        $model = new static;
        $conn = $model->getConnection()->getName();
        if (array_key_exists($conn, self::$tableExistsCache)) {
            return self::$tableExistsCache[$conn];
        }
        try {
            self::$tableExistsCache[$conn] = Schema::connection($conn)->hasTable($model->getTable());
        } catch (\Throwable) {
            self::$tableExistsCache[$conn] = false;
        }

        return self::$tableExistsCache[$conn];
    }

    /**
     * Tenant- of admin-context voor scoped keys (niet voor GLOBAL_PLATFORM_KEYS).
     * Publieke site: resolved_tenant_id. Admin: geselecteerde tenant of company van de gebruiker.
     */
    public static function resolveScopeCompanyId(): ?int
    {
        if (self::$resolvedScopeCompanyIdComputed) {
            return self::$resolvedScopeCompanyId;
        }

        self::$resolvedScopeCompanyIdComputed = true;

        if (! app()->runningInConsole() && request()) {
            $path = request()->path();
            if (str_starts_with($path, 'admin')) {
                $user = self::adminSessionUser();
                if ($user instanceof User && $user->hasRole('super-admin')) {
                    $st = session('selected_tenant');
                    if ($st !== null && $st !== '' && is_numeric($st)) {
                        $id = (int) $st;
                        // Geen Class::alias: bij ontbrekende/autoload-fout van Company anders 500 op elke admin-POST.
                        $companyExists = class_exists(Company::class)
                            && Company::query()->whereKey($id)->exists();
                        self::$resolvedScopeCompanyId = $companyExists ? $id : null;

                        return self::$resolvedScopeCompanyId;
                    }

                    self::$resolvedScopeCompanyId = null;

                    return null;
                }
                if ($user instanceof User && $user->company_id) {
                    self::$resolvedScopeCompanyId = (int) $user->company_id;

                    return self::$resolvedScopeCompanyId;
                }

                self::$resolvedScopeCompanyId = null;

                return null;
            }
        }

        if (app()->bound('resolved_tenant_id')) {
            $id = app('resolved_tenant_id');
            if ($id !== null && $id !== '') {
                self::$resolvedScopeCompanyId = (int) $id;

                return self::$resolvedScopeCompanyId;
            }
        }

        self::$resolvedScopeCompanyId = null;

        return null;
    }

    /**
     * Admin-tenant uit de web-sessie, zonder de hele request te laten 500'en
     * als het User-model (nog) niet geladen kan worden.
     */
    private static function adminSessionUser(): ?User
    {
        try {
            if (! class_exists(User::class)) {
                return null;
            }

            $user = Auth::guard('web')->user();

            return $user instanceof User ? $user : null;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string|null>
     */
    public static function getMany(array $keys, ?int $forCompanyId = null): array
    {
        $keys = array_values(array_unique(array_filter($keys, fn ($k) => is_string($k) && $k !== '')));
        $result = array_fill_keys($keys, null);
        if ($keys === [] || ! self::settingsTableAvailable()) {
            return $result;
        }

        $platformKeys = array_values(array_filter($keys, [self::class, 'isGlobalPlatformKey']));
        $scopedKeys = array_values(array_diff($keys, $platformKeys));

        if ($platformKeys !== []) {
            foreach (self::query()->whereIn('key', $platformKeys)->whereNull('company_id')->pluck('value', 'key') as $key => $value) {
                $result[$key] = $value;
            }
        }

        $cid = $forCompanyId;
        if ($scopedKeys !== [] && $cid === null) {
            $cid = self::resolveScopeCompanyId();
        }

        if ($scopedKeys !== [] && $cid !== null) {
            foreach (self::query()->whereIn('key', $scopedKeys)->where('company_id', $cid)->pluck('value', 'key') as $key => $value) {
                $result[$key] = $value;
            }
        }

        $missingScoped = array_values(array_filter($scopedKeys, fn ($key) => $result[$key] === null));
        if ($missingScoped !== []) {
            foreach (self::query()->whereIn('key', $missingScoped)->whereNull('company_id')->pluck('value', 'key') as $key => $value) {
                if ($result[$key] === null) {
                    $result[$key] = $value;
                }
            }
        }

        foreach ($result as $key => $value) {
            self::$getCache[self::getCacheKey($key, $forCompanyId)] = $value;
        }

        return $result;
    }

    private static function getCacheKey(string $key, ?int $forCompanyId): string
    {
        if (self::isGlobalPlatformKey($key)) {
            return 'g|'.$key;
        }

        $cid = $forCompanyId ?? self::resolveScopeCompanyId();

        return 't|'.$key.'|'.($cid ?? 'null');
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null, ?int $forCompanyId = null)
    {
        $cacheKey = self::getCacheKey($key, $forCompanyId);
        if (array_key_exists($cacheKey, self::$getCache)) {
            $cached = self::$getCache[$cacheKey];

            return $cached !== null ? $cached : $default;
        }

        if (! self::settingsTableAvailable()) {
            return $default;
        }

        if (self::isGlobalPlatformKey($key)) {
            try {
                $setting = self::query()
                    ->where('key', $key)
                    ->whereNull('company_id')
                    ->orderByDesc('id')
                    ->first();
                $value = $setting ? $setting->value : null;
                self::$getCache[$cacheKey] = $value;

                return $value !== null ? $value : $default;
            } catch (\Throwable) {
                return $default;
            }
        }

        $cid = $forCompanyId ?? self::resolveScopeCompanyId();

        try {
            if ($cid !== null) {
                $setting = self::query()->where('key', $key)->where('company_id', $cid)->first();
                if ($setting) {
                    self::$getCache[$cacheKey] = $setting->value;

                    return $setting->value;
                }
            }

            $fallback = self::query()->where('key', $key)->whereNull('company_id')->first();
            $value = $fallback ? $fallback->value : null;
            self::$getCache[$cacheKey] = $value;

            return $value !== null ? $value : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * @param  string|int|float|bool|null  $value
     */
    public static function set(string $key, $value, ?int $forCompanyId = null): self
    {
        if (! self::settingsTableAvailable()) {
            throw new RuntimeException(
                'Database-tabel "general_settings" ontbreekt. Voer migraties uit: php artisan migrate'
            );
        }

        if (self::isGlobalPlatformKey($key)) {
            $companyId = null;
        } else {
            $companyId = $forCompanyId ?? self::resolveScopeCompanyId();
            if ($companyId === null && ! self::isMailSettingKey($key)) {
                throw new RuntimeException(
                    'GeneralSetting::set vereist een tenant (company_id). Selecteer een tenant in de admin of gebruik een account met bedrijf.'
                );
            }
        }

        if (self::isGlobalPlatformKey($key) || ($companyId === null && self::isMailSettingKey($key))) {
            $model = self::upsertNullCompanySetting($key, (string) $value);
            self::clearRequestCache();

            return $model;
        }

        /** @var self $model */
        $model = self::query()->updateOrCreate(
            ['key' => $key, 'company_id' => $companyId],
            ['value' => (string) $value]
        );

        self::clearRequestCache();

        return $model;
    }

    private static function upsertNullCompanySetting(string $key, string $value): self
    {
        $model = self::query()
            ->where('key', $key)
            ->whereNull('company_id')
            ->orderByDesc('id')
            ->first();

        if ($model) {
            $model->update(['value' => $value]);
            self::query()
                ->where('key', $key)
                ->whereNull('company_id')
                ->where('id', '!=', $model->id)
                ->delete();

            return $model;
        }

        return self::query()->create([
            'key' => $key,
            'company_id' => null,
            'value' => $value,
        ]);
    }
}

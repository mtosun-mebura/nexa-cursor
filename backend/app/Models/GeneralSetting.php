<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GeneralSetting extends Model
{
    /** Platform-breed: niet per tenant (sync-doel, vlag). */
    public const GLOBAL_PLATFORM_KEYS = [
        'tenant_sync_target_database_url',
        'tenant_sync_push_enabled',
    ];

    protected $fillable = [
        'company_id',
        'key',
        'value',
    ];

    /** @var array<string, bool> */
    private static array $tableExistsCache = [];

    public static function isGlobalPlatformKey(string $key): bool
    {
        return in_array($key, self::GLOBAL_PLATFORM_KEYS, true);
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
        if (! app()->runningInConsole() && request()) {
            $path = request()->path();
            if (str_starts_with($path, 'admin')) {
                $user = auth()->user();
                if ($user && $user->hasRole('super-admin')) {
                    $st = session('selected_tenant');
                    if ($st !== null && $st !== '' && is_numeric($st)) {
                        $id = (int) $st;

                        return Company::query()->whereKey($id)->exists() ? $id : null;
                    }

                    return null;
                }
                if ($user && $user->company_id) {
                    return (int) $user->company_id;
                }

                return null;
            }
        }

        if (app()->bound('resolved_tenant_id')) {
            $id = app('resolved_tenant_id');
            if ($id !== null && $id !== '') {
                return (int) $id;
            }
        }

        return null;
    }

    /**
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null, ?int $forCompanyId = null)
    {
        if (! self::settingsTableAvailable()) {
            return $default;
        }

        if (self::isGlobalPlatformKey($key)) {
            try {
                $setting = self::query()->where('key', $key)->whereNull('company_id')->first();

                return $setting ? $setting->value : $default;
            } catch (\Throwable) {
                return $default;
            }
        }

        $cid = $forCompanyId ?? self::resolveScopeCompanyId();

        try {
            if ($cid !== null) {
                $setting = self::query()->where('key', $key)->where('company_id', $cid)->first();
                if ($setting) {
                    return $setting->value;
                }
            }

            $fallback = self::query()->where('key', $key)->whereNull('company_id')->first();

            return $fallback ? $fallback->value : $default;
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
            if ($companyId === null) {
                throw new RuntimeException(
                    'GeneralSetting::set vereist een tenant (company_id). Selecteer een tenant in de admin of gebruik een account met bedrijf.'
                );
            }
        }

        /** @var self $model */
        $model = self::query()->updateOrCreate(
            ['key' => $key, 'company_id' => $companyId],
            ['value' => (string) $value]
        );

        return $model;
    }
}

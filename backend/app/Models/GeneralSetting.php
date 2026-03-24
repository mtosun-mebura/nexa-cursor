<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class GeneralSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /** @var array<string, bool> */
    private static array $tableExistsCache = [];

    /**
     * Of de general_settings-tabel op deze connection bestaat (cache per request/connection).
     */
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
     * Get a setting value by key
     */
    public static function get($key, $default = null)
    {
        if (! self::settingsTableAvailable()) {
            return $default;
        }
        try {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Set a setting value by key
     */
    public static function set($key, $value)
    {
        if (! self::settingsTableAvailable()) {
            throw new RuntimeException(
                'Database-tabel "general_settings" ontbreekt. Voer migraties uit: php artisan migrate'
            );
        }

        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
}

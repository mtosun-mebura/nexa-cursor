<?php

namespace App\Modules\NexaTaxi\Support;

use App\Modules\NexaTaxi\Models\DefaultRate;
use Illuminate\Support\Facades\Schema;

/**
 * Zorgt dat default_rates de kolommen heeft die tarieven-admin en pricing verwachten,
 * ook wanneer de tabel al bestond vóór latere migraties (ensureModuleStorageReady
 * draait dan geen migraties meer omdat de tabel "al klaar" lijkt).
 */
final class DefaultRateSchema
{
    /** @var array<string, bool> */
    private static array $ready = [];

    public static function ensureColumns(string $connection): void
    {
        $key = $connection.':default_rates_columns';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('default_rates')) {
            return;
        }

        if (! $schema->hasColumn('default_rates', 'company_id')) {
            $schema->table('default_rates', function ($table) {
                $table->unsignedBigInteger('company_id')->nullable()->index();
            });
            DefaultRate::resetCompanyIdColumnCache();
        }

        if (! $schema->hasColumn('default_rates', 'evening_night_multiplier')) {
            $schema->table('default_rates', function ($table) {
                $table->decimal('evening_night_multiplier', 4, 2)->default(1.20);
            });
        }
        if (! $schema->hasColumn('default_rates', 'evening_night_from_hour')) {
            $schema->table('default_rates', function ($table) {
                $table->unsignedTinyInteger('evening_night_from_hour')->default(22);
            });
        }
        if (! $schema->hasColumn('default_rates', 'evening_night_until_hour')) {
            $schema->table('default_rates', function ($table) {
                $table->unsignedTinyInteger('evening_night_until_hour')->default(6);
            });
        }

        self::$ready[$key] = true;
    }

    public static function resetCache(): void
    {
        self::$ready = [];
    }
}

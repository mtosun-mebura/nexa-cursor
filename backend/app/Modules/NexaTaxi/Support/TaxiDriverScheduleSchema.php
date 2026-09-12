<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class TaxiDriverScheduleSchema
{
    public static function tableExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('driver_schedules');
    }

    public static function ensureTable(string $connection): void
    {
        if (! self::tableExists($connection)) {
            Schema::connection($connection)->create('driver_schedules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('driver_id')->index();
                $table->unsignedBigInteger('vehicle_id')->index();
                $table->dateTime('starts_at');
                $table->dateTime('ends_at');
                $table->unsignedBigInteger('series_id')->nullable()->index();
                $table->string('weekdays', 32)->nullable();
                $table->boolean('repeat_weekly')->default(false);
                $table->date('repeat_until')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'starts_at', 'ends_at']);
                $table->index(['driver_id', 'starts_at', 'ends_at']);
                $table->index(['vehicle_id', 'starts_at', 'ends_at']);
            });
        }

        self::ensureRecurrenceColumns($connection);
    }

    public static function ensureRecurrenceColumns(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('driver_schedules')) {
            return;
        }

        if (! $schema->hasColumn('driver_schedules', 'weekdays')) {
            $schema->table('driver_schedules', function (Blueprint $table) {
                $table->string('weekdays', 32)->nullable();
            });
        }
        if (! $schema->hasColumn('driver_schedules', 'repeat_weekly')) {
            $schema->table('driver_schedules', function (Blueprint $table) {
                $table->boolean('repeat_weekly')->default(false);
            });
        }
        if (! $schema->hasColumn('driver_schedules', 'repeat_until')) {
            $schema->table('driver_schedules', function (Blueprint $table) {
                $table->date('repeat_until')->nullable();
            });
        }
    }
}

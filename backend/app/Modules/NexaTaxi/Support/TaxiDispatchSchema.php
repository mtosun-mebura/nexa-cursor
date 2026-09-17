<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiDispatchSchema
{
    /** @var array<string, bool> */
    private static array $ready = [];

    public static function tablesExist(string $connection): bool
    {
        return self::remember($connection.':tables', function () use ($connection) {
            $schema = Schema::connection($connection);

            return $schema->hasTable('driver_availability')
                && $schema->hasTable('ride_dispatch_offers');
        });
    }

    public static function driverAvailabilityExists(string $connection): bool
    {
        return self::remember($connection.':availability', function () use ($connection) {
            return Schema::connection($connection)->hasTable('driver_availability');
        });
    }

    public static function ensureOfferDeclineReasonColumn(string $connection): void
    {
        $key = $connection.':decline_reason';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! $schema->hasColumn('ride_dispatch_offers', 'decline_reason')) {
            $schema->table('ride_dispatch_offers', function ($table) {
                $table->string('decline_reason', 500)->nullable();
            });
        }

        self::$ready[$key] = true;
    }

    public static function ensurePickupProposalColumns(string $connection): void
    {
        $key = $connection.':pickup';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_requests')) {
            return;
        }

        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_at')) {
            $schema->table('ride_requests', function ($table) {
                $table->dateTime('pickup_proposal_at')->nullable();
            });
        }
        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_status')) {
            $schema->table('ride_requests', function ($table) {
                $table->string('pickup_proposal_status', 32)->nullable();
            });
        }
        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_customer_remark')) {
            $schema->table('ride_requests', function ($table) {
                $table->text('pickup_proposal_customer_remark')->nullable();
            });
        }
        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_sent_at')) {
            $schema->table('ride_requests', function ($table) {
                $table->timestamp('pickup_proposal_sent_at')->nullable();
            });
        }
        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_responded_at')) {
            $schema->table('ride_requests', function ($table) {
                $table->timestamp('pickup_proposal_responded_at')->nullable();
            });
        }
        if (! $schema->hasColumn('ride_requests', 'pickup_proposal_whatsapp_wamid')) {
            $schema->table('ride_requests', function ($table) {
                $table->string('pickup_proposal_whatsapp_wamid', 191)->nullable();
            });
        }

        self::$ready[$key] = true;
    }

    public static function ensureOfferArchiveColumn(string $connection): void
    {
        $key = $connection.':archive';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! $schema->hasColumn('ride_dispatch_offers', 'archived_at')) {
            $schema->table('ride_dispatch_offers', function ($table) {
                $table->timestamp('archived_at')->nullable();
            });
        }

        self::$ready[$key] = true;
    }

    public static function ensureVehicleIdColumn(string $connection): void
    {
        $key = $connection.':vehicle';
        if (! empty(self::$ready[$key])) {
            return;
        }

        $schema = Schema::connection($connection);
        if (! $schema->hasTable('driver_availability')) {
            return;
        }

        if (! $schema->hasColumn('driver_availability', 'vehicle_id')) {
            $schema->table('driver_availability', function ($table) {
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
            });
        }

        self::$ready[$key] = true;
    }

    /**
     * @param  callable(): bool  $callback
     */
    private static function remember(string $key, callable $callback): bool
    {
        if (array_key_exists($key, self::$ready)) {
            return self::$ready[$key];
        }

        return self::$ready[$key] = (bool) $callback();
    }
}

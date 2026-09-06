<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiDispatchSchema
{
    public static function tablesExist(string $connection): bool
    {
        $schema = Schema::connection($connection);

        return $schema->hasTable('driver_availability')
            && $schema->hasTable('ride_dispatch_offers');
    }

    public static function driverAvailabilityExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('driver_availability');
    }

    public static function ensureOfferDeclineReasonColumn(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! $schema->hasColumn('ride_dispatch_offers', 'decline_reason')) {
            $schema->table('ride_dispatch_offers', function ($table) {
                $table->string('decline_reason', 500)->nullable();
            });
        }
    }

    public static function ensurePickupProposalColumns(string $connection): void
    {
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
    }

    public static function ensureOfferArchiveColumn(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('ride_dispatch_offers')) {
            return;
        }

        if (! $schema->hasColumn('ride_dispatch_offers', 'archived_at')) {
            $schema->table('ride_dispatch_offers', function ($table) {
                $table->timestamp('archived_at')->nullable();
            });
        }
    }

    public static function ensureVehicleIdColumn(string $connection): void
    {
        $schema = Schema::connection($connection);
        if (! $schema->hasTable('driver_availability')) {
            return;
        }

        if ($schema->hasColumn('driver_availability', 'vehicle_id')) {
            return;
        }

        $schema->table('driver_availability', function ($table) {
            $table->unsignedBigInteger('vehicle_id')->nullable()->index();
        });
    }
}

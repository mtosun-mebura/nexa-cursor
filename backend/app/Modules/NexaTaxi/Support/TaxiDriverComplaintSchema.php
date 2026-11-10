<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Klachten, chauffeur-inboxmeldingen en app-blokkade op de taxi-module-DB.
 */
final class TaxiDriverComplaintSchema
{
    /** @var array<string, bool> */
    private static array $ready = [];

    public static function ensure(string $connection): void
    {
        $key = $connection.':complaints';
        $schema = Schema::connection($connection);

        if (
            ! empty(self::$ready[$key])
            && $schema->hasTable('taxi_ride_complaints')
            && $schema->hasTable('taxi_driver_notifications')
            && $schema->hasColumn('taxi_driver_profiles', 'app_blocked_at')
        ) {
            return;
        }

        TaxiDriverProfileSchema::ensure($connection);

        if ($schema->hasTable('taxi_driver_profiles') && ! $schema->hasColumn('taxi_driver_profiles', 'app_blocked_at')) {
            $schema->table('taxi_driver_profiles', function ($table) {
                $table->timestamp('app_blocked_at')->nullable()->after('rating_avg');
                $table->string('app_blocked_reason', 500)->nullable()->after('app_blocked_at');
                $table->unsignedBigInteger('app_blocked_by')->nullable()->after('app_blocked_reason');
                $table->unsignedBigInteger('app_blocked_complaint_id')->nullable()->after('app_blocked_by');
            });
        }

        if (! $schema->hasTable('taxi_ride_complaints')) {
            $schema->create('taxi_ride_complaints', function ($table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('ride_request_id')->index();
                $table->unsignedBigInteger('driver_id')->index();
                $table->unsignedBigInteger('vehicle_id')->nullable()->index();
                $table->string('category', 40);
                $table->string('subject', 160)->nullable();
                $table->text('description');
                $table->string('customer_name', 160)->nullable();
                $table->string('customer_email', 190)->nullable();
                $table->string('customer_phone', 40)->nullable();
                $table->string('preferred_contact', 20)->default('email');
                $table->string('status', 30)->default('open')->index();
                $table->text('admin_notes')->nullable();
                $table->unsignedBigInteger('handled_by')->nullable();
                $table->timestamp('handled_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('taxi_driver_notifications')) {
            $schema->create('taxi_driver_notifications', function ($table) {
                $table->id();
                $table->unsignedBigInteger('driver_id')->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('type', 40)->index();
                $table->string('title', 160);
                $table->text('body');
                $table->unsignedBigInteger('related_complaint_id')->nullable()->index();
                $table->unsignedBigInteger('related_ride_id')->nullable()->index();
                $table->unsignedBigInteger('related_rating_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        self::$ready[$key] = true;
    }

    /** @internal tests */
    public static function resetReadyCache(): void
    {
        self::$ready = [];
    }
}

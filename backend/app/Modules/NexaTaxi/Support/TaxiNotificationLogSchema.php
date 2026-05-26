<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class TaxiNotificationLogSchema
{
    public static function tableExists(string $connection): bool
    {
        return Schema::connection($connection)->hasTable('ride_request_notification_logs');
    }
}

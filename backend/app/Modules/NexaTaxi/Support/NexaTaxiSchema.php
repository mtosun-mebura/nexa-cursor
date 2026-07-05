<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Facades\Schema;

final class NexaTaxiSchema
{
    /** @var list<string> */
    public const CORE_TABLES = ['vehicles', 'ride_requests', 'default_rates'];

    /** @var list<string> */
    public const CONTRACTVERVOER_TABLES = [
        'transport_customers',
        'transport_contracts',
        'transport_payment_mandates',
        'transport_passengers',
        'transport_groups',
        'transport_group_members',
        'transport_route_templates',
        'transport_route_stops',
        'transport_assignments',
        'transport_individual_bookings',
        'transport_occurrences',
        'ride_stops',
        'transport_schedule_exceptions',
    ];

    public static function coreTablesExist(string $connection): bool
    {
        $schema = Schema::connection($connection);
        foreach (self::CORE_TABLES as $table) {
            if (! $schema->hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public static function missingContractvervoerTables(string $connection): array
    {
        $schema = Schema::connection($connection);
        $missing = [];
        foreach (self::CONTRACTVERVOER_TABLES as $table) {
            if (! $schema->hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public static function contractvervoerTablesExist(string $connection): bool
    {
        return self::missingContractvervoerTables($connection) === [];
    }
}

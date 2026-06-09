<?php

namespace App\Support;

use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Of optionele module-tabellen op de gegeven connection bestaan.
 * Bij MODULE_DATABASE_STRATEGY=schema staan module-tabellen in PG-schema's (nexa_taxi, …);
 * bij strategy=database op een aparte module-DB.
 * niet op de hoofd-DB (nexa) — dan mogen admin-queries geen withCount('vacancies') doen op de hoofdconnection.
 */
final class ModuleSchemaAvailability
{
    public static function defaultConnectionName(): string
    {
        return (string) config('database.default');
    }

    public static function vacanciesTableExists(?string $connection = null): bool
    {
        $conn = $connection ?? self::defaultConnectionName();

        return Schema::connection($conn)->hasTable('vacancies');
    }

    public static function matchesTableExists(?string $connection = null): bool
    {
        if (! self::vacanciesTableExists($connection)) {
            return false;
        }

        $dbService = app(ModuleDatabaseService::class);
        if ($dbService->supportsModuleDatabases()) {
            try {
                $dbService->ensureModuleStorageReady('skillmatching');
                $conn = $dbService->getModuleConnectionName('skillmatching');

                return Schema::connection($conn)->hasTable('matches');
            } catch (\Throwable) {
                return false;
            }
        }

        $conn = $connection ?? self::defaultConnectionName();

        try {
            if (! Schema::connection($conn)->hasTable('matches')) {
                return false;
            }

            DB::connection($conn)->table('matches')->selectRaw('1')->limit(1)->get();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function rideRequestsTableExists(): bool
    {
        $dbService = app(ModuleDatabaseService::class);
        if (! $dbService->supportsModuleDatabases()) {
            return Schema::hasTable('ride_requests');
        }

        try {
            $dbService->ensureModuleStorageReady('taxi');
            $conn = $dbService->getModuleConnectionName('taxi');

            return Schema::connection($conn)->hasTable('ride_requests');
        } catch (\Throwable) {
            return false;
        }
    }
}

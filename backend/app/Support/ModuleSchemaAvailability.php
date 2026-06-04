<?php

namespace App\Support;

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
}

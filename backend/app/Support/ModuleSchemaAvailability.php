<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Of optionele module-tabellen op de gegeven connection bestaan.
 * Bij MODULE_USE_SINGLE_DATABASE=false staan skillmatching-/taxi-tabellen typisch op de module-DB,
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

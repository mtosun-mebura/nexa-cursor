<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Single database mode
    |--------------------------------------------------------------------------
    |
    | When true, all module tables (vehicles, ride_requests, etc.) live in the
    | main database (e.g. nexa). No separate databases (nexa_taxiroyaal, …) are
    | created. Use one database/schema instead of one per module.
    |
    */
    'use_single_database' => env('MODULE_USE_SINGLE_DATABASE', false),
];

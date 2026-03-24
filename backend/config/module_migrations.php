<?php

/*
| Pre-2026 schema staat in App\Database\Pre2026Baseline (gegenereerd uit het vroegere archief).
| Module-databases: zie module_migration_sets — zelfde baseline gefilterd op set (core/shared/module).
| Losse module-updates: database/migrations/modules/{naam} via ModuleMigrationPathResolver.
*/

return [
    'module_migration_sets' => [
        'taxiroyaal' => ['core', 'shared', 'taxiroyaal'],
        'skillmatching' => ['core', 'shared', 'skillmatching'],
    ],

    /*
    | Voor een onbekende module: alleen core + shared (geen module-specifieke tabellen).
    */
    'default_set' => ['core', 'shared'],
];

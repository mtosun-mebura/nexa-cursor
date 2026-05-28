<?php

/*
| Pre-2026 schema staat in App\Database\Pre2026Baseline (gegenereerd uit het vroegere archief).
| Module-databases: zie module_migration_sets — zelfde baseline gefilterd op set (core/shared/module).
| Losse module-updates: database/migrations/modules/{naam} via ModuleMigrationPathResolver.
*/

return [
    /*
    | Bij strategy=database: core+shared+module-specifiek (volledige standalone DB).
    | Bij strategy=schema: alleen module-specifiek (core+shared staan al in public).
    */
    'module_migration_sets' => [
        'taxi' => ['core', 'shared', 'taxiroyaal'],
        'skillmatching' => ['core', 'shared', 'skillmatching'],
    ],

    'schema_only_sets' => [
        'taxi' => ['taxiroyaal'],
        'skillmatching' => ['skillmatching'],
    ],

    /*
    | Minimale tabellen per module; ensureModuleStorageReady draait migraties als ze ontbreken.
    */
    'required_tables' => [
        'taxi' => ['vehicles', 'ride_requests', 'default_rates'],
    ],

    'default_set' => ['core', 'shared'],
];

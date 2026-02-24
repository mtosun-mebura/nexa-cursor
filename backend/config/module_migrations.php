<?php

return [
    /*
    | Migratie-paden per type. Module-databases krijgen alleen core + shared + hun eigen module-pad.
    | Zo komt nexa_taxiroyaal geen skillmatching-tabellen in, en nexa_skillmatching geen vehicles/ride_requests.
    */
    'paths' => [
        'core'    => 'database/migrations/core',
        'shared'  => 'database/migrations/shared',
        'taxiroyaal' => 'database/migrations/modules/taxiroyaal',
        'skillmatching' => 'database/migrations/modules/skillmatching',
    ],

    'module_migration_sets' => [
        'taxiroyaal'   => ['core', 'shared', 'taxiroyaal'],
        'skillmatching' => ['core', 'shared', 'skillmatching'],
    ],

    /*
    | Voor een onbekende module: alleen core + shared (geen module-specifieke tabellen).
    */
    'default_set' => ['core', 'shared'],
];

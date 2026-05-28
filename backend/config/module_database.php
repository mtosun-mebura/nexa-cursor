<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module database strategy
    |--------------------------------------------------------------------------
    |
    |  "schema"   – Één database (DB_DATABASE), per module een PostgreSQL-schema
    |               (nexa_taxi, nexa_skillmatching). Standaard en aanbevolen.
    |  "database" – Per module een eigen database (legacy).
    |  "single"   – Alle tabellen in public (niet gebruikt in nieuwe omgevingen).
    |
    | ENV: MODULE_DATABASE_STRATEGY=schema
    |
    */
    'strategy' => env('MODULE_DATABASE_STRATEGY', 'schema'),

    /*
    |--------------------------------------------------------------------------
    | Tabellen op de hoofd-DB die weg mogen bij strategy=database
    |--------------------------------------------------------------------------
    |
    | Bij strategy=schema staan module-tabellen in hun eigen schema; public blijft schoon.
    |
    */
    'main_database_prune_tables' => [
        'vehicles',
        'ride_requests',
        'default_rates',
        'job_configurations',
        'vacancies',
        'matches',
        'interviews',
        'candidates',
        'favorites',
        'skills',
        'experiences',
        'cv_files',
        'candidate_embeddings',
        'candidate_texts',
        'applications',
        'vacancy_embeddings',
        'branch_functions',
        'branch_function_skills',
        'job_configuration_types',
        'candidate_activities',
        'stage_instances',
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module database strategy
    |--------------------------------------------------------------------------
    |
    | Bepaalt hoe module-tabellen worden opgeslagen:
    |
    |  "single"   – Alle tabellen in de hoofddatabase (public schema).
    |  "schema"   – Één database, per module een eigen PostgreSQL-schema
    |               (bv. nexa_taxi, nexa_skillmatching) in dezelfde DB.
    |  "database" – Per module een eigen database (nexa_taxi, nexa_skillmatching).
    |
    | "schema" is de aanbevolen modus voor PostgreSQL: geen cross-database
    | FK-problemen, eenvoudiger backup, en toch nette scheiding per module.
    |
    | ENV: MODULE_DATABASE_STRATEGY=schema  (of single / database)
    | Legacy: MODULE_USE_SINGLE_DATABASE=true wordt automatisch omgezet naar "single".
    |
    */
    'strategy' => env('MODULE_DATABASE_STRATEGY',
        env('MODULE_USE_SINGLE_DATABASE', false) ? 'single' : 'schema'
    ),

    /*
    |--------------------------------------------------------------------------
    | Legacy alias (backward compat)
    |--------------------------------------------------------------------------
    */
    'use_single_database' => env('MODULE_USE_SINGLE_DATABASE', false),

    /*
    |--------------------------------------------------------------------------
    | Tabellen op de hoofd-DB die weg mogen bij losse module-DB's
    |--------------------------------------------------------------------------
    |
    | Alleen relevant bij strategy=database. Bij strategy=schema staan
    | module-tabellen in hun eigen schema; de hoofd-DB (public) blijft schoon.
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

<?php

return [

    /*
    | Tabellen die nooit automatisch meegaan bij tenant-push.
    */
    'excluded_tables' => [
        'migrations',
        'failed_jobs',
        'job_batches',
        'jobs',
        'password_reset_tokens',
        'password_resets',
        'sessions',
        'cache',
        'cache_locks',
        'personal_access_tokens',
        // Spatie: globale permission-definities en directe user-permissions niet meekopiëren
        'model_has_permissions',
        'role_has_permissions',
        'permissions',
    ],

    /*
    | Tie-break / vroege volgorde wanneer FK-grafiek gelijk is (of bij SQLite-heuristiek).
    | companies staat altijd eerst buiten deze lijst.
    */
    /*
    | Tabellen met company_id die bij betalingen/facturatie horen (sync + documentatie).
    */
    'payment_company_scoped_tables' => [
        'payment_providers',
        'invoice_settings',
        'invoices',
        'payments',
        'payment_reminders',
        'ride_payments',
    ],

    /*
    | Globale tabellen zonder company_id die vóór company-scoped data naar doel gaan
    | (FK-parents zoals modules → company_module). Volgorde telt; auto-discovery vult aan.
    */
    'prerequisite_tables' => [
        'frontend_themes',
        'modules',
    ],

    /*
    | Tabellen zonder company_id die na de hoofd-push nog worden gevuld (tenant-gebonden).
    */
    'post_sync_tables' => [
        'role_has_permissions',
    ],

    /*
    | Nexa Taxi (connection module_taxi / schema nexa_taxi): voertuigen per tenant + standaardtarieven.
    */
    'taxi_module' => [
        'module_name' => 'taxi',
        'company_scoped_tables' => ['vehicles'],
        'global_tables' => ['default_rates'],
        'natural_keys' => [
            'vehicles' => ['company_id', 'name'],
            'default_rates' => ['person_range'],
        ],
    ],

    /*
    | Natuurlijke sleutels om bestaande rijen op doel te herkennen (geen dubbele inserts).
    | Waarden zijn kolomnamen; volgorde telt. Lege/null waarden in een matchkolom → fallback-kolommen (zie service).
    */
    'existing_row_keys' => [
        'email_templates' => ['company_id', 'type', 'name'],
        'general_settings' => ['company_id', 'key'],
        'users' => ['email'],
        'company_domains' => ['host'],
        'company_module' => ['company_id', 'module_id'],
        'modules' => ['name'],
        'frontend_themes' => ['slug'],
        'roles' => ['company_id', 'name', 'guard_name'],
        'payment_providers' => ['company_id', 'provider_type'],
        'website_pages' => ['company_id', 'slug'],
        'vacancies' => ['company_id', 'slug'],
        'notifications' => ['company_id', 'title'],
        'invoices' => ['company_id', 'invoice_number'],
        'invoice_settings' => ['company_id', 'location_id'],
        'ride_payments' => ['mollie_payment_id'],
        'model_has_roles' => ['company_id', 'role_id', 'model_id', 'model_type'],
        'vehicles' => ['company_id', 'name'],
        'default_rates' => ['person_range'],
    ],

    'priority_tables' => [
        'company_domains',
        'general_settings',
        'company_module',
        'company_locations',
        'roles',
        'users',
        'model_has_roles',
        'email_templates',
        'website_pages',
        'vacancies',
        'notifications',
        'payment_providers',
        'invoice_settings',
        'invoices',
        'payments',
        'payment_reminders',
        'ride_payments',
        'chats',
        'pipeline_templates',
        'job_configurations',
        'interviews',
    ],

];

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
        // Spatie / rollen: niet blind kopiëren (rechten op PROD apart beheren)
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'roles',
        'permissions',
    ],

    /*
    | Tie-break / vroege volgorde wanneer FK-grafiek gelijk is (of bij SQLite-heuristiek).
    | companies staat altijd eerst buiten deze lijst.
    */
    'priority_tables' => [
        'company_domains',
        'general_settings',
        'company_module',
        'company_locations',
        'users',
        'email_templates',
        'website_pages',
        'vacancies',
        'notifications',
        'invoices',
        'payments',
        'chats',
        'pipeline_templates',
        'job_configurations',
        'interviews',
    ],

];

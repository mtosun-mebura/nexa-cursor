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
    | Hoofd-database-tabellen die op het sync-doel moeten bestaan vóór data-push (migratiepad relatief
    | t.o.v. Laravel base_path). Alleen als de tabel op de bron bestaat.
    */
    'main_required_tables' => [
        'company_domains' => 'database/migrations/2026_04_20_000002_create_company_domains_table.php',
        'ai_chat_audit_logs' => 'database/migrations/2026_06_08_140000_create_ai_chat_audit_logs_table.php',
    ],

    /*
    | Tabellen waar bestaande rijen op doel worden bijgewerkt (niet alleen overgeslagen).
    */
    'update_on_existing_tables' => [
        'companies',
        'company_domains',
        'general_settings',
        'email_templates',
        'invoice_settings',
        'payment_providers',
        'website_pages',
        'company_locations',
        'roles',
    ],

    /*
    | Kolommen die naar een parent-tabel verwijzen zonder DB-FK (worden via idMaps hermapped).
    */
    'manual_foreign_keys' => [
        'ai_chat_audit_logs' => [
            'user_id' => 'users',
        ],
    ],

    /*
    | general_settings die globaal kunnen staan (company_id IS NULL) maar wél naar het doel moeten,
    | zodat frontend-functies (o.a. WhatsApp-widget) ook werken als ze niet per tenant zijn opgeslagen.
    | Company-scoped general_settings reizen al mee via de gewone company-push (o.a. ai_chat_enabled,
    | ai_chat_webhook_*, ai_chat_taxi_* teksten uit AI-chatbot instellingen).
    */
    'global_general_setting_keys' => [
        'WHATSAPP_WIDGET_ENABLED',
        'WHATSAPP_WIDGET_PHONE',
        'WHATSAPP_WIDGET_DEFAULT_MESSAGE',
    ],

    /*
    | Binaire kolommen (bytea/blob) die als ruwe bytes moeten worden overgezet (geen stream-resource).
    | Voorkomt dat o.a. profielfoto's (users.photo_blob) leeg/corrupt op het doel belanden.
    */
    'binary_columns' => [
        'users' => ['photo_blob'],
    ],

    /*
    | Nexa Taxi (connection module_taxi / schema nexa_taxi): voertuigen per tenant + standaardtarieven.
    */
    'taxi_module' => [
        'module_name' => 'taxi',
        'company_scoped_tables' => [
            'vehicles',
            'ride_requests',
            'ride_dispatch_offers',
            'driver_availability',
        ],
        'global_tables' => ['default_rates', 'knowledge_documents', 'knowledge_chunks'],
        'global_table_foreign_keys' => [
            'knowledge_chunks' => [
                'document_id' => 'knowledge_documents',
            ],
        ],
        'manual_foreign_keys' => [
            'ride_requests' => [
                'vehicle_id' => 'vehicles',
                'driver_id' => 'users',
                'customer_user_id' => 'users',
                'invoice_id' => 'invoices',
            ],
            'ride_dispatch_offers' => [
                'ride_request_id' => 'ride_requests',
                'driver_id' => 'users',
            ],
            'driver_availability' => [
                'driver_id' => 'users',
            ],
        ],
        'natural_keys' => [
            'vehicles' => ['company_id', 'name'],
            'ride_requests' => ['company_id', 'pickup_at', 'customer_email', 'pickup_address'],
            'ride_dispatch_offers' => ['ride_request_id', 'driver_id'],
            'driver_availability' => ['driver_id'],
            'default_rates' => ['person_range'],
            'knowledge_documents' => ['title', 'category'],
            'knowledge_chunks' => ['document_id', 'chunk_text'],
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
        'ride_requests' => ['company_id', 'pickup_at', 'customer_email', 'pickup_address'],
        'ride_dispatch_offers' => ['ride_request_id', 'driver_id'],
        'driver_availability' => ['driver_id'],
        'default_rates' => ['person_range'],
        'knowledge_documents' => ['title', 'category'],
        'knowledge_chunks' => ['document_id', 'chunk_text'],
        'ai_chat_audit_logs' => ['company_id', 'created_at', 'channel', 'intent', 'message'],
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
        'ai_chat_audit_logs',
        'pipeline_templates',
        'job_configurations',
        'interviews',
    ],

];

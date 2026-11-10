<?php

return [
    'enabled' => env('TAXI_PARTNER_FINDER_ENABLED', true),

    'sources' => [
        'knv' => [
            'enabled' => env('TAXI_PARTNER_FINDER_KNV_ENABLED', true),
            'url' => env('TAXI_PARTNER_FINDER_KNV_URL', 'https://www.knv.nl/ledenoverzicht-taxi/'),
            'request_delay_ms' => (int) env('TAXI_PARTNER_FINDER_KNV_DELAY_MS', 500),
            'user_agent' => env(
                'TAXI_PARTNER_FINDER_USER_AGENT',
                'NexaTaxiPartnerFinder/1.0 (+https://nexasuite.nl; partner-discovery)'
            ),
        ],
        'knv_csv' => [
            'enabled' => env('TAXI_PARTNER_FINDER_KNV_CSV_ENABLED', true),
        ],
        'kvk' => [
            'enabled' => env('TAXI_PARTNER_FINDER_KVK_ENABLED', false),
            'api_key' => env('KVK_API_KEY', ''),
            'base_url' => env('KVK_API_BASE_URL', 'https://api.kvk.nl/api/v2'),
            // KvK ondersteunt geen landelijke dump op SBI; alleen gerichte zoekopdrachten.
            'sbi_codes' => ['49320', '4932'],
        ],
        'google_places' => [
            'enabled' => env('TAXI_PARTNER_FINDER_GOOGLE_ENABLED', true),
            // Alleen Place ID + verwijzing; duurzame bedrijfsdata uit andere bronnen.
            'store_place_id_only' => true,
        ],
        'website' => [
            'enabled' => env('TAXI_PARTNER_FINDER_WEBSITE_ENABLED', true),
        ],
        'tto' => [
            'enabled' => env('TAXI_PARTNER_FINDER_TTO_ENABLED', false),
        ],
    ],

    'newsletter_bridge' => [
        // Alleen contact_ready met e-mail naar nieuwsbrief-opt-in, nooit automatisch.
        'auto_push_on_import' => (bool) env('TAXI_PARTNER_FINDER_AUTO_PUSH_NEWSLETTER', false),
    ],

    // Periodieke KNV-sync alleen als expliciet toegestaan (robots/voorwaarden).
    'schedule' => [
        'knv_weekly' => (bool) env('TAXI_PARTNER_FINDER_KNV_WEEKLY', false),
    ],

    'lead_statuses' => [
        'discovered',
        'verified',
        'contact_ready',
        'invited',
        'interested',
        'registered',
        'approved',
        'rejected',
        'opted_out',
    ],
];

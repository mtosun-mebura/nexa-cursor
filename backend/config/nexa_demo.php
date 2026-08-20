<?php

return [
    'enabled' => env('NEXA_DEMO_ENABLED', true),
    'email' => env('NEXA_DEMO_EMAIL', 'demo@nexasuite.nl'),
    'password' => env('NEXA_DEMO_PASSWORD', 'DemoTaxi2026!'),
    'company_name' => env('NEXA_DEMO_COMPANY', 'Nexa Taxi Demo'),
    'company_slug' => env('NEXA_DEMO_COMPANY_SLUG', 'nexa-taxi-demo'),
    'first_name' => 'Demo',
    'last_name' => 'Gebruiker',
    'modules' => ['taxi'],
    'role' => 'demo',
    'menu_keys' => [
        'vehicles',
        'tarieven',
        'ride_requests',
        'transport_customers',
        'dispatch_settings',
    ],
    'permissions' => [
        'vehicles.view',
        'vehicles.create',
        'vehicles.update',
        'vehicles.delete',
        'rates.view',
        'rates.update',
        'rides.view',
        'rides.create',
        'rides.update',
        'rides.delete',
    ],
];

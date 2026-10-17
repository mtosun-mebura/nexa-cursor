<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Independent driver payouts
    |--------------------------------------------------------------------------
    | Mollie Connect Marketplace onboarding is for legal entities. Keep this
    | false until legal/tax validates that independent drivers may settle
    | directly. Employee/contractor drivers settle via the taxi company.
    */
    'allow_independent_driver_payouts' => (bool) env('NEXA_ALLOW_INDEPENDENT_DRIVER_PAYOUTS', false),

    /*
    |--------------------------------------------------------------------------
    | Destination change cooling-off
    |--------------------------------------------------------------------------
    */
    'destination_change_cooling_off_hours' => (int) env('NEXA_PAYOUT_COOLING_OFF_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Recent authentication window for high-risk payout actions (seconds)
    |--------------------------------------------------------------------------
    */
    'step_up_password_max_age_seconds' => (int) env('NEXA_PAYOUT_STEP_UP_MAX_AGE', 300),

    'default_provider' => 'mollie',

    /*
    |--------------------------------------------------------------------------
    | Platform collect (marketplace + network)
    |--------------------------------------------------------------------------
    | Klant betaalt NEXA; NEXA houdt fee in en betaalt netto uit aan taxipartij(en).
    | Fee-facturen zijn naslag, geen inningsinstrument.
    */
    'platform_collect_enabled' => (bool) env('NEXA_PLATFORM_COLLECT_ENABLED', true),

    /*
    | Of de stub-transfer automatisch slaagt (tests/dev). Zet false om failed→manual te oefenen.
    | Geldt niet voor chauffeur-uitbetaling bij ritafsluiting; die loopt via Mollie Connect.
    */
    'platform_payout_auto_succeed' => (bool) env('NEXA_PLATFORM_PAYOUT_AUTO_SUCCEED', true),

    /*
    |--------------------------------------------------------------------------
    | Mollie Connect balance transfer (chauffeur, direct na afronden)
    |--------------------------------------------------------------------------
    | Platformbreed, één keer voor NEXA. Niet per chauffeur of tenant, en niet
    | hetzelfde als PLATFORM_MOLLIE_API_KEY (Admin → SaaS-facturatie).
    | Zet deze twee waarden in de omgeving (.env):
    | - NEXA_MOLLIE_CONNECT_ACCESS_TOKEN: Mollie → organization access token
    |   of Connect OAuth, scope balance-transfers.write (geen test_/live_ sleutel).
    | - NEXA_MOLLIE_CONNECT_ORGANIZATION_ID: org_… van NEXA in het Mollie-dashboard.
    | Zonder token of org_ faalt de uitbetaling; er wordt niets als betaald gemarkeerd.
    */
    'connect_access_token' => env('NEXA_MOLLIE_CONNECT_ACCESS_TOKEN'),
    'connect_source_organization_id' => env('NEXA_MOLLIE_CONNECT_ORGANIZATION_ID'),
    'connect_testmode' => (bool) env('NEXA_MOLLIE_CONNECT_TESTMODE', false),

    /*
    | Network split van het netto-bedrag (na NEXA-fee). Som moet 100 zijn.
    | Owner = klantrelatie (Taxi A), fulfiller = uitvoerder (Taxi B).
    */
    'network_owner_share_of_net_percent' => (int) env('NEXA_NETWORK_OWNER_SHARE_PERCENT', 15),
    'network_fulfiller_share_of_net_percent' => (int) env('NEXA_NETWORK_FULFILLER_SHARE_PERCENT', 85),
];

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
    */
    'platform_payout_auto_succeed' => (bool) env('NEXA_PLATFORM_PAYOUT_AUTO_SUCCEED', true),

    /*
    | Network split van het netto-bedrag (na NEXA-fee). Som moet 100 zijn.
    | Owner = klantrelatie (Taxi A), fulfiller = uitvoerder (Taxi B).
    */
    'network_owner_share_of_net_percent' => (int) env('NEXA_NETWORK_OWNER_SHARE_PERCENT', 15),
    'network_fulfiller_share_of_net_percent' => (int) env('NEXA_NETWORK_FULFILLER_SHARE_PERCENT', 85),
];

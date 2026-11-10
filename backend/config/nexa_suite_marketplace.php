<?php

return [
    'fee_percent' => 10,
    /*
    | Minimale fee per rit, afgeleid van de Mollie-kosten (pay-in + uitbetaling).
    | fee = max(fee_percent, minimum), nooit meer dan het ritbedrag. Bedragen excl. btw.
    | Bron tarieven: https://www.mollie.com/pricing (standaard, NL, geraadpleegd 26-10-2026).
    | payout.fixed is een aanname: Mollie publiceert geen tarief voor Connect balance transfers.
    | Overschrijfbaar in Admin → NEXA Suite boekingsinstellingen.
    */
    'fee_minimum' => [
        'enabled' => true,
        'methods' => [
            'ideal' => ['fixed' => 0.32, 'percent' => 0.0],
            'creditcard' => ['fixed' => 0.25, 'percent' => 1.8],
            'applepay' => ['fixed' => 0.25, 'percent' => 1.8],
            'bancontact' => ['fixed' => 0.25, 'percent' => 1.4],
            'default' => ['fixed' => 0.25, 'percent' => 1.8],
        ],
        'payout_fixed' => 0.25,
        'margin_fixed' => 0.0,
        'margin_percent' => 0.0,
    ],
    /** Marketplace-pakket mag zelf NEXA Network-partners koppelen (marketplace↔marketplace). Standaard uit. */
    'allow_marketplace_network_owner' => false,
    'auto_generate' => true,
    'auto_send' => true,
    'billing_day' => 1,
    'billing_time' => '06:30',
    'tax_rate_percent' => 21,
    'payment_terms_days' => 14,
    'dunning_first_interval_days' => 1,
    'dunning_interval_days' => 14,
    'invoice_number_prefix' => 'NSB',
    'invoice_number_format' => '{prefix}-{year}-{number}',
    'invoice_title' => 'NEXA Suite boekingsfactuur',
    /**
     * Publieke host voor Mollie-redirects en webhooks van betalingen uit de native app.
     * Lege databasekolom valt hierop terug. De app kiest zelf welke server hij aanroept.
     */
    'public_base_url' => 'https://nexasuite.nl',
    'public_environments' => [
        'productie' => 'https://nexasuite.nl',
        'test' => 'https://nexasuite.online',
    ],
];

<?php

return [
    // Optionele fallback; voorkeur is Admin → SaaS-facturatie → Mollie-instellingen.
    'mollie_api_key' => env('PLATFORM_MOLLIE_API_KEY'),

    'mandate_verification_amount' => 0.01,

    'invoice_number_prefix' => env('PLATFORM_BILLING_INVOICE_PREFIX', 'SAAS'),

    // Optionele fallback; voorkeur is platform_billing_settings.mollie_webhook_url.
    'webhook_url' => env('PLATFORM_MOLLIE_WEBHOOK_URL'),
];

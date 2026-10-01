<?php

$streamEnabledEnv = env('TAXI_DISPATCH_STREAM_ENABLED');
$streamEnabled = $streamEnabledEnv !== null
    ? filter_var($streamEnabledEnv, FILTER_VALIDATE_BOOL)
    : env('APP_ENV') === 'production';

return [
    /**
     * Standaard acceptatietijd (seconden) als er geen waarde in admin → Chauffeur dispatch staat.
     * Per tenant overschrijfbaar via GeneralSetting `taxi_dispatch_offer_ttl_seconds`.
     */
    'offer_ttl_seconds' => (int) env('TAXI_DISPATCH_OFFER_TTL', 300),

    /**
     * Minuten na het ophaalmoment dat een rit nog in de chauffeur-wachtrij (Nieuwe ritaanvraag) mag staan.
     * Per tenant overschrijfbaar via GeneralSetting `taxi_dispatch_past_pickup_grace_minutes`.
     * Legacy: TAXI_DISPATCH_PAST_PICKUP_GRACE_HOURS (uren → minuten).
     */
    'past_pickup_grace_minutes' => (int) (
        env('TAXI_DISPATCH_PAST_PICKUP_GRACE_MINUTES') !== null
            ? env('TAXI_DISPATCH_PAST_PICKUP_GRACE_MINUTES')
            : (env('TAXI_DISPATCH_PAST_PICKUP_GRACE_HOURS') !== null
                ? ((int) env('TAXI_DISPATCH_PAST_PICKUP_GRACE_HOURS') * 60)
                : 60)
    ),

    /**
     * Minuten na het (actuele) ophaalmoment waarna de klant mag kiezen: wachten of annuleren.
     * 0 = geen keuze-prompt / geen auto-annulering.
     * Per tenant: Admin → NexaTaxi → Chauffeur dispatch
     * (`taxi_dispatch_unaccepted_auto_cancel_minutes`). Fallback: deze env.
     */
    'unaccepted_auto_cancel_minutes' => (int) env('TAXI_DISPATCH_UNACCEPTED_AUTO_CANCEL_MINUTES', 30),

    /**
     * Minuten zonder klantreactie (wachten/annuleren) waarna de rit alsnog automatisch
     * wordt geannuleerd. 0 = nooit automatisch na de prompt.
     * Per tenant: Admin → NexaTaxi → Chauffeur dispatch
     * (`taxi_dispatch_customer_unaccepted_decision_minutes`). Fallback: deze env.
     */
    'customer_unaccepted_decision_minutes' => (int) env('TAXI_CUSTOMER_UNACCEPTED_DECISION_MINUTES', 30),

    /**
     * Werkdagen waarbinnen een terugbetaling na annulering doorgaans zichtbaar is
     * (communicatie naar klant; Mollie/bank kan afwijken).
     */
    'customer_refund_business_days' => (int) env('TAXI_CUSTOMER_REFUND_BUSINESS_DAYS', 10),

    /**
     * Geldigheid eenmalige inlogcode Mijn Taxi (minuten) als er geen waarde in admin staat.
     * Per tenant: GeneralSetting `taxi_dispatch_customer_login_code_expires_minutes`.
     */
    'customer_login_code_expires_minutes' => (int) env('TAXI_CUSTOMER_LOGIN_CODE_EXPIRES_MINUTES', 15),

    /**
     * Geldigheid eenmalige first-login code voor chauffeur-app en contractportaal (minuten).
     */
    'app_first_login_code_expires_minutes' => (int) env('TAXI_APP_FIRST_LOGIN_CODE_EXPIRES_MINUTES', 15),

    /** Minimum seconden tussen twee code-aanvragen voor hetzelfde adres. */
    'app_first_login_code_cooldown_seconds' => (int) env('TAXI_APP_FIRST_LOGIN_CODE_COOLDOWN_SECONDS', 60),

    /** Max chauffeurs per golf. */
    'offer_batch_size' => (int) env('TAXI_DISPATCH_BATCH_SIZE', 8),

    /**
     * SSE push (alleen bij PHP-FPM/Octane met meerdere workers).
     * Uit in local/Docker met `php artisan serve` — anders blokkeert één stream alle requests.
     */
    'stream_enabled' => $streamEnabled,

    /** Polling (ms): sneller zonder SSE, trager als fallback met SSE. */
    'inbox_poll_interval_ms' => (int) env(
        'TAXI_DISPATCH_POLL_MS',
        $streamEnabled ? 15000 : 2000
    ),

    /** SSE push-stream: max verbindingstijd (s) voordat client opnieuw verbindt. */
    'stream_max_seconds' => (int) env('TAXI_DISPATCH_STREAM_MAX_SECONDS', 55),

    /** SSE: interval tussen cache-checks (ms). */
    'stream_tick_ms' => (int) env('TAXI_DISPATCH_STREAM_TICK_MS', 500),

    /** Sanctum token geldigheid voor chauffeur-app (dagen). 0 = tot uitloggen. */
    'token_expiry_days' => (int) env('TAXI_DRIVER_TOKEN_DAYS', 0),

    /**
     * Mollie testmodus: in local/staging ook providers met test_-sleutel of testmodus-vinkje,
     * ook als "Actief" uit staat (handig om te testen zonder live-betalingen).
     */
    'allow_mollie_test_providers' => filter_var(
        env('TAXI_DISPATCH_ALLOW_MOLLIE_TEST', env('APP_ENV') !== 'production'),
        FILTER_VALIDATE_BOOL
    ),

    /**
     * Publieke webhook-URL voor Mollie (bijv. ngrok). Leeg = afgeleid uit provider/APP_URL;
     * localhost en 192.168.x.x worden bij betalingen niet naar Mollie gestuurd.
     */
    'mollie_webhook_url' => env('TAXI_MOLLIE_WEBHOOK_URL'),

    /**
     * NEXA Network (owner ≠ fulfiller). Default OFF — per tenant via GeneralSetting.
     */
    'network_enabled' => filter_var(env('TAXI_NETWORK_ENABLED', false), FILTER_VALIDATE_BOOL),
    'network_mode' => env('TAXI_NETWORK_MODE', 'off'),
    'network_fallback_seconds' => (int) env('TAXI_NETWORK_FALLBACK_SECONDS', 120),
    'network_max_radius_km' => (int) env('TAXI_NETWORK_MAX_RADIUS_KM', 25),

    /**
     * Hours to hold a completed ride before settlement_eligible (Phase 4 gate).
     * High-risk rides use RideSettlementEligibilityService::HOLD_HOURS_HIGH_RISK.
     */
    'settlement_hold_hours' => (int) env('TAXI_SETTLEMENT_HOLD_HOURS', 24),

    /**
     * Hours after completion during which the customer may confirm or report a problem (Phase 5).
     */
    'customer_settlement_signal_hours' => (int) env('TAXI_CUSTOMER_SETTLEMENT_SIGNAL_HOURS', 48),
];

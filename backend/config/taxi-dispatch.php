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

    /** Sanctum token geldigheid voor chauffeur-app (dagen). */
    'token_expiry_days' => (int) env('TAXI_DRIVER_TOKEN_DAYS', 14),

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
];

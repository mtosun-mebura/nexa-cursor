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

<?php

return [
    /*
    | Mock inbound WhatsApp-antwoorden (super-admin testpagina).
    | true/false overschrijft de automatische detectie.
    | Leeg: aan buiten productie, of in productie als APP_URL geen live-webhook-host is.
    */
    'inbound_mock_enabled' => env('WHATSAPP_WEBHOOK_MOCK_ENABLED'),

    /*
    | Hosts waar Meta de echte webhook naartoe stuurt. Daar geen mock-triggers.
    */
    'live_webhook_hosts' => array_values(array_filter(array_map(
        'strtolower',
        array_map('trim', explode(',', (string) env('WHATSAPP_LIVE_WEBHOOK_HOSTS', 'nexasuite.nl,www.nexasuite.nl')))
    ))),
];

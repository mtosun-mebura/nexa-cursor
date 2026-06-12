<?php

return [

    /*
    |--------------------------------------------------------------------------
    | n8n → Laravel SQL gateway
    |--------------------------------------------------------------------------
    |
    | Gebruik een gedeeld geheim (min. 32 tekens) voor HMAC-handtekeningen op
    | live-data requests vanuit n8n. Zet N8N_AI_CHAT_HMAC_SECRET in .env.
    |
    */
    'n8n_hmac_secret' => env('N8N_AI_CHAT_HMAC_SECRET'),

    /*
    | SQL-token TTL (seconden) dat Laravel meestuurt naar n8n voor live queries.
    */
    'sql_token_ttl_seconds' => (int) env('AI_CHAT_SQL_TOKEN_TTL', 120),

    /*
    | Vaste tekst wanneer een publieke gebruiker live operationele data vraagt.
    */
    'live_data_denied_message' => 'Daar kan ik je helaas geen informatie over geven. Stel je vraag gerust op een andere manier, of neem contact met ons op.',

    'not_found_message' => 'Ik kan helaas geen informatie vinden over je vraag. Probeer het anders te formuleren of neem contact met ons op.',

    'unavailable_message' => 'Ik kan helaas geen informatie vinden over je vraag. Probeer het anders te formuleren of neem contact met ons op.',

    /*
    | Publieke Laravel-basis-URL die n8n kan bereiken voor live-query callbacks.
    |
    | APP_URL is vaak http://localhost:8085 (lokaal) — dat werkt NIET vanuit n8n in de cloud.
    | Zet daarom AI_CHAT_LARAVEL_API_URL op je publieke HTTPS-domein, bijv. https://nexasuite.nl
    */
    'laravel_api_url' => env('AI_CHAT_LARAVEL_API_URL', env('APP_URL', 'http://localhost')),

    'laravel_live_query_path' => env(
        'AI_CHAT_LARAVEL_LIVE_QUERY_PATH',
        '/integrations/n8n/ai-chat/live-query'
    ),

    'laravel_rag_search_path' => env(
        'AI_CHAT_LARAVEL_RAG_SEARCH_PATH',
        '/integrations/n8n/ai-chat/rag-search'
    ),

    /*
    | Publieke tarieventabel in het Nexa Taxi schema (tenant via module-DB).
    */
    'public_rates_table' => env('AI_CHAT_PUBLIC_RATES_TABLE', 'default_rates'),

    /*
    | Oude n8n-hostnamen → nieuwe host (POST op n8n.nexasuite.nl geeft HTTP 405).
    | Geldt ook voor ai_chat_webhook_* in general_settings tot handmatig bijgewerkt.
    */
    'webhook_host_aliases' => [
        'n8n.nexasuite.nl' => 'automations.nexasuite.nl',
    ],

];

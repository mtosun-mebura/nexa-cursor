<?php

return [
    'google' => [
        'enabled' => env('GOOGLE_PLACES_ENABLED', true),
        'api_key' => env('GOOGLE_PLACES_API_KEY', ''),
        'requests_per_second' => (int) env('GOOGLE_PLACES_RPS', 4),
        'page_size' => 20,
        'max_pages_per_query' => 3,
    ],

    'hunter' => [
        'enabled' => env('HUNTER_ENABLED', false),
        'api_key' => env('HUNTER_API_KEY', ''),
    ],

    'kvk' => [
        'enabled' => env('KVK_ENABLED', false),
        'api_key' => env('KVK_API_KEY', ''),
    ],

    'crawler' => [
        'user_agent' => env('COMPANY_CRAWLER_USER_AGENT', 'NexaCompanyCrawler/1.0 (+https://nexasuite.nl)'),
        'max_pages' => (int) env('COMPANY_CRAWLER_MAX_PAGES', 6),
        'timeout' => (int) env('COMPANY_CRAWLER_TIMEOUT', 10),
        'delay_ms' => (int) env('COMPANY_CRAWLER_DELAY_MS', 150),
        'max_body_bytes' => (int) env('COMPANY_CRAWLER_MAX_BODY_BYTES', 512000),
    ],

    'search' => [
        'default_radius_km' => 50,
        'default_max_results' => 300,
        'max_results_cap' => 500,
        'grid_radius_km' => 25,
    ],

    /*
     | Zoekpunten per provincie voor Google Places (naam + coördinaten).
     | Bij een expliciete plaats wordt alleen die plaats gebruikt.
     */
    'province_grid' => [
        'Drenthe' => [
            ['city' => 'Assen', 'lat' => 52.9925, 'lng' => 6.5642],
            ['city' => 'Emmen', 'lat' => 52.7792, 'lng' => 6.9069],
            ['city' => 'Hoogeveen', 'lat' => 52.7225, 'lng' => 6.4764],
        ],
        'Flevoland' => [
            ['city' => 'Almere', 'lat' => 52.3508, 'lng' => 5.2647],
            ['city' => 'Lelystad', 'lat' => 52.5185, 'lng' => 5.4714],
        ],
        'Friesland' => [
            ['city' => 'Leeuwarden', 'lat' => 53.2012, 'lng' => 5.7999],
            ['city' => 'Drachten', 'lat' => 53.1125, 'lng' => 6.0989],
            ['city' => 'Heerenveen', 'lat' => 52.9593, 'lng' => 5.9185],
        ],
        'Gelderland' => [
            ['city' => 'Arnhem', 'lat' => 51.9851, 'lng' => 5.8987],
            ['city' => 'Nijmegen', 'lat' => 51.8126, 'lng' => 5.8372],
            ['city' => 'Apeldoorn', 'lat' => 52.2112, 'lng' => 5.9699],
            ['city' => 'Ede', 'lat' => 52.0403, 'lng' => 5.6651],
        ],
        'Groningen' => [
            ['city' => 'Groningen', 'lat' => 53.2194, 'lng' => 6.5665],
            ['city' => 'Hoogezand', 'lat' => 53.1617, 'lng' => 6.7611],
        ],
        'Limburg' => [
            ['city' => 'Maastricht', 'lat' => 50.8514, 'lng' => 5.6909],
            ['city' => 'Venlo', 'lat' => 51.3704, 'lng' => 6.1724],
            ['city' => 'Heerlen', 'lat' => 50.8882, 'lng' => 5.9795],
        ],
        'Noord-Brabant' => [
            ['city' => 'Eindhoven', 'lat' => 51.4416, 'lng' => 5.4697],
            ['city' => 'Tilburg', 'lat' => 51.5555, 'lng' => 5.0913],
            ['city' => 'Breda', 'lat' => 51.5719, 'lng' => 4.7683],
            ['city' => 'Den Bosch', 'lat' => 51.6978, 'lng' => 5.3037],
        ],
        'Noord-Holland' => [
            ['city' => 'Amsterdam', 'lat' => 52.3676, 'lng' => 4.9041],
            ['city' => 'Haarlem', 'lat' => 52.3874, 'lng' => 4.6462],
            ['city' => 'Alkmaar', 'lat' => 52.6324, 'lng' => 4.7534],
            ['city' => 'Hilversum', 'lat' => 52.2292, 'lng' => 5.1669],
        ],
        'Overijssel' => [
            ['city' => 'Zwolle', 'lat' => 52.5168, 'lng' => 6.0830],
            ['city' => 'Enschede', 'lat' => 52.2215, 'lng' => 6.8937],
            ['city' => 'Deventer', 'lat' => 52.2552, 'lng' => 6.1639],
            ['city' => 'Hengelo', 'lat' => 52.2661, 'lng' => 6.7931],
            ['city' => 'Almelo', 'lat' => 52.3570, 'lng' => 6.6686],
        ],
        'Utrecht' => [
            ['city' => 'Utrecht', 'lat' => 52.0907, 'lng' => 5.1214],
            ['city' => 'Amersfoort', 'lat' => 52.1561, 'lng' => 5.3878],
            ['city' => 'Nieuwegein', 'lat' => 52.0292, 'lng' => 5.0804],
        ],
        'Zeeland' => [
            ['city' => 'Middelburg', 'lat' => 51.4988, 'lng' => 3.6100],
            ['city' => 'Terneuzen', 'lat' => 51.3355, 'lng' => 3.8278],
            ['city' => 'Goes', 'lat' => 51.5042, 'lng' => 3.8883],
        ],
        'Zuid-Holland' => [
            ['city' => 'Rotterdam', 'lat' => 51.9244, 'lng' => 4.4777],
            ['city' => 'Den Haag', 'lat' => 52.0705, 'lng' => 4.3007],
            ['city' => 'Leiden', 'lat' => 52.1601, 'lng' => 4.4970],
            ['city' => 'Dordrecht', 'lat' => 51.8133, 'lng' => 4.6901],
        ],
    ],
];

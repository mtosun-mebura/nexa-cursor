<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    |
    | Platform-brede waarden uit Algemene configuraties (general_settings).
    | Gebruik EnvService::getGoogleMapsApiKey() of mapsFormSettings().
    | Bij opstarten worden deze keys gesynchroniseerd via EnvService::syncMapsConfig().
    | .env is alleen fallback wanneer er nog niets in de admin is ingesteld.
    |
    */

    'api_key' => env('GOOGLE_MAPS_API_KEY', ''),

    /** Map ID (optioneel): nodig voor Advanced Markers, voorkomt deprecation-warning. Aanmaken in Google Cloud Console → Map Management. */
    'map_id' => env('GOOGLE_MAPS_MAP_ID', ''),

    'zoom' => env('GOOGLE_MAPS_ZOOM', 12),

    'center_lat' => env('GOOGLE_MAPS_CENTER_LAT', '52.3676'),

    'center_lng' => env('GOOGLE_MAPS_CENTER_LNG', '4.9041'),

    'type' => env('GOOGLE_MAPS_TYPE', 'roadmap'),

];

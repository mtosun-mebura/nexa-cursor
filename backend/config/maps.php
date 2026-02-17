<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Maps
    |--------------------------------------------------------------------------
    |
    | De API key wordt gelezen uit de root .env (projectroot, niet backend/.env).
    | Gebruik EnvService::getGoogleMapsApiKey() om de waarde te krijgen.
    | Admin → Instellingen → Maps toont en beheert de key (opslaan schrijft naar backend/.env).
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

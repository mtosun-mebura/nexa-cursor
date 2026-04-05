<?php

return [

    /*
    | Hostnames waar géén tenant uit de database wordt opgelost (centrale app / admin).
    | Wordt aangevuld met de host uit APP_URL. Komma-gescheiden extra hosts via .env.
    */
    'central_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', ''))))),

];

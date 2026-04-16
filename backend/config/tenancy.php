<?php

return [

    /*
    | Hostnames waar géén tenant uit de database wordt opgelost (centrale app / admin).
    | Wordt aangevuld met de host uit APP_URL. Komma-gescheiden extra hosts via .env.
    */
    'central_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', ''))))),

    /*
    | Hostnamen die als *ouder* gelden voor automatische tenant-resolutie op subdomeinen.
    | Voorbeeld: bij "nexasuite.nl" wordt "taxi.nexasuite.nl" gekoppeld aan een actief bedrijf
    | waarvan slug of naam-slug overeenkomt met "taxi" (zonder entry in company_domains).
    | Komma-gescheiden, zelfde normalisatie als centrale domeinen.
    */
    'tenant_parent_domains' => array_values(array_filter(array_map('trim', explode(',', (string) env('TENANCY_TENANT_PARENT_DOMAINS', ''))))),

];

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

    /*
    | Niet-productie: koppel een Host-header direct aan een company_id (lokale tests zonder DNS/slug-match).
    | Formaat: host:company_id,... bijv. taxitest.nexasuite.nl:2
    | Wordt vóór CentralDomains geëvalueerd en omzeilt zo een foutieve APP_URL op een tenant-host.
    */
    'dev_host_company_map' => collect(explode(',', (string) env('TENANCY_DEV_HOST_COMPANY_MAP', '')))
        ->mapWithKeys(function (string $pair) {
            $pair = trim($pair);
            if ($pair === '') {
                return [];
            }
            $parts = explode(':', $pair, 2);
            if (count($parts) !== 2) {
                return [];
            }
            $host = strtolower(trim($parts[0]));
            $id = (int) trim($parts[1]);
            if ($host === '' || $id <= 0) {
                return [];
            }

            return [$host => $id];
        })
        ->all(),

    /*
    | Alleen niet-productie: queryparameter overschrijft de host voor tenant-resolutie.
    | Zo kun je http://localhost:8085/?_tenant_host=taxitest.nexasuite.nl gebruiken zonder /etc/hosts of DNS.
    | Leeg = uit. De echte browser-URL taxitest.nexasuite.nl:8085 vereist nog steeds dat die hostnaam naar jouw IP wijst.
    */
    'dev_effective_host_query_param' => trim((string) env('TENANCY_DEV_EFFECTIVE_HOST_QUERY', '')),

];

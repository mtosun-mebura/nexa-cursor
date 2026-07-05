<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Routes zonder tenant-keuze (super-admin ziet altijd inhoud)
    |--------------------------------------------------------------------------
    */
    'exempt_route_names' => [
        'admin.dashboard',
        'admin.tenant.switch',
        'admin.handleiding.*',
        'admin.companies.*',
        'admin.modules.index',
        'admin.modules.install',
        'admin.modules.activate',
        'admin.modules.deactivate',
        'admin.modules.uninstall',
        'admin.modules.database-reset',
        'admin.modules.*.database-dummydata',
        'admin.modules.*.run-migrations',
        'admin.permissions.*',
        'admin.roles.*',
        'admin.profile',
        'admin.profile.*',
        'admin.playground.*',
        'admin.meld.*',
        'admin.skillmatching.branches.*',
        'admin.skillmatching.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Melding tonen, maar pagina-inhoud niet verbergen (platform-secties)
    |--------------------------------------------------------------------------
    */
    'notice_only_route_names' => [
        'admin.settings.index',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route-specifieke meldingen (pattern => variant of custom tekst)
    |--------------------------------------------------------------------------
    */
    'route_notice_variants' => [
        'admin.website-pages.*' => 'website-pages',
        'admin.settings.*' => 'settings',
        'admin.modules.config' => 'module-config',
    ],

    'default_notice' => 'Kies links in de zijbalk een tenant (bedrijf) voordat u deze pagina kunt bekijken en beheren.',

];

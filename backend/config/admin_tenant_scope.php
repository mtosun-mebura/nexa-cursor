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
        'admin.settings.upgrade.*',
        'admin.settings.general.*',
        'admin.settings.whatsapp.platform.update',
        'admin.settings.whatsapp.platform.test',
        'admin.settings.upload-logo',
        'admin.settings.remove-logo-light',
        'admin.settings.remove-logo-dark',
        'admin.settings.upload-favicon',
        'admin.settings.logo-size.update',
        'admin.settings.logo',
        'admin.settings.logo-dark',
        'admin.settings.favicon',
        'admin.settings.upload-success-image',
        'admin.settings.remove-success-image',
        'admin.settings.success-image',
        'admin.playground.*',
        'admin.meld.*',
        'admin.skillmatching.branches.*',
        'admin.skillmatching.test',
        'admin.welcome-page.*',
        'admin.nexa-pricing.*',
        'admin.platform-billing.*',
        'admin.website-pages.*',
        'admin.website-ai.*',
        'admin.frontend-themes.*',
        'admin.frontend-components.*',
        'admin.customer-emails.*',
        'admin.email-templates.*',
        'admin.newsletters.*',
        'admin.whatsapp-pickup-proposal-mock.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Melding tonen, maar pagina-inhoud niet verbergen (platform-secties)
    |--------------------------------------------------------------------------
    */
    'notice_only_route_names' => [
        'admin.settings.index',
        'admin.settings.mail.update',
        'admin.settings.mail.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route-specifieke meldingen (pattern => variant of custom tekst)
    |--------------------------------------------------------------------------
    */
    'route_notice_variants' => [
        'admin.settings.*' => 'settings',
        'admin.modules.config' => 'module-config',
    ],

    'default_notice' => 'Kies links in de zijbalk een tenant (bedrijf) voordat u deze pagina kunt bekijken en beheren.',

];

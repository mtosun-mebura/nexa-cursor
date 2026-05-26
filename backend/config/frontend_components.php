<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Component-ids die niet in de admin-picker staan (geen paginacomponent).
    | Blade-partials in excluded_discovered_basenames worden ook niet auto-ontdekt.
    */
    'excluded_component_ids' => [
        'website.features_card',
        // Gebruik de sectie "Tekstblok" (text_block) in de page builder; die heeft de WYSIWYG-editor.
        'website.text_block_section',
    ],
    'excluded_discovered_basenames' => [
        'features-card',
        'features-card-section',
        'text-block-section',
    ],

    /*
    |--------------------------------------------------------------------------
    | Front-end componenten (automatisch ontdekt door de applicatie).
    | Alleen aanpasbaar in code; niet via beheerpagina's.
    | id: unieke key (module.key), gebruikt in section_order als "component:module.key"
    | name: weergavenaam in admin
    | module_name: modulenaam voor groepering (bijv. "Nexa Skillmatching")
    | view: Blade-view pad (zonder .blade.php)
    | description: korte omschrijving
    */
    'components' => [
        [
            'id' => 'website.google_reviews',
            'name' => 'Google Reviews',
            'module_name' => 'Algemeen',
            'view' => 'frontend.website.components.google-reviews',
            'description' => 'Carousel met Google-reviews (Place ID en cache in Configuraties > Algemeen).',
            'available_on_all_pages' => true,
        ],
        [
            'id' => 'website.nexa_modules_overview',
            'name' => 'NEXA modules overzicht',
            'module_name' => 'Algemeen',
            'view' => 'frontend.website.components.nexa-modules-overview',
            'description' => 'Verkooppagina met NEXA Skillmatching, Taxi en Garage modules.',
            'available_on_all_pages' => true,
        ],
        [
            'id' => 'website.email_template_section',
            'name' => 'Email Template Section',
            'module_name' => 'Algemeen',
            'view' => 'frontend.website.components.email-template-section',
            'description' => 'Herbruikbare e-mail template sectie voor website builder contentblokken.',
            'available_on_all_pages' => true,
        ],
        [
            'id' => 'nexa.recente_vacatures',
            'name' => 'Recente Vacatures',
            'module_name' => 'Nexa Skillmatching',
            /** Koppeling aan rij modules.name (skillmatching), i.p.v. alleen weergavenaam. */
            'module_key' => 'skillmatching',
            'view' => 'frontend.website.components.recente-vacatures',
            'description' => 'Blok met recente vacatures (container-custom, grid van vacaturekaarten en link naar vacaturesoverzicht).',
        ],
        [
            'id' => 'taxi.tarieven',
            'name' => 'Nexa Taxi tarieven',
            'module_name' => 'Nexa Taxi',
            'module_key' => 'taxi',
            'view' => 'frontend.website.components.nexataxi-tarieven',
            'description' => 'Tarieven t/m 4 en 5 t/m 8 personen met voertuigfoto\'s en overige kosten.',
        ],
        [
            'id' => 'taxi.boekingsmodule',
            'name' => 'Nexa Taxi boekingsmodule',
            'module_name' => 'Nexa Taxi',
            'module_key' => 'taxi',
            'view' => 'frontend.website.components.nexataxi-boekingsmodule',
            'description' => 'Meerstaps boekingsmodule met bagage, aanbiedingen, reisgegevens en contactgegevens.',
        ],
    ],
];

<?php

return [
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
            'id' => 'nexa.recente_vacatures',
            'name' => 'Recente Vacatures',
            'module_name' => 'Nexa Skillmatching',
            'view' => 'frontend.website.components.recente-vacatures',
            'description' => 'Blok met recente vacatures (container-custom, grid van vacaturekaarten en link naar vacaturesoverzicht).',
        ],
        [
            'id' => 'taxiroyaal.tarieven',
            'name' => 'Taxi Royaal tarieven',
            'module_name' => 'Taxi Royaal',
            'view' => 'frontend.website.components.taxiroyaal-tarieven',
            'description' => 'Tarieven t/m 4 en 5 t/m 8 personen met voertuigfoto\'s en overige kosten.',
        ],
        [
            'id' => 'taxiroyaal.boekingsmodule',
            'name' => 'Boekingsmodule',
            'module_name' => 'Taxi Royaal',
            'view' => 'frontend.website.components.taxiroyaal-boekingsmodule',
            'description' => 'Meerstaps boekingsmodule met bagage, aanbiedingen, reisgegevens en contactgegevens.',
        ],
    ],
];

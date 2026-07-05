<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Admin handleiding – artikelen
  |--------------------------------------------------------------------------
  | Voeg hier nieuwe pagina's toe. Elke slug krijgt een route admin.handleiding.show
  | en een blade onder resources/views/admin/handleiding/pages/.
  */
    'pages' => [
        'aan-de-slag' => [
            'title' => 'Aan de slag',
            'summary' => 'Leer in enkele minuten hoe u zich oriënteert in Nexa: dashboard, navigatie en tenant-keuze.',
            'icon' => 'ki-rocket',
            'order' => 10,
            'view' => 'admin.handleiding.pages.aan-de-slag',
            'estimated_minutes' => 5,
        ],
    ],
];

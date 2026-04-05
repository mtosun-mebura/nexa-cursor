<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Standaard gebruikersavatar (geen foto geüpload)
    |--------------------------------------------------------------------------
    | Pad onder public/, zie public/assets/media/avatars/nexa-default-avatar.png
    */
    'default_user_avatar' => env('NEXA_DEFAULT_USER_AVATAR', 'assets/media/avatars/nexa-default-avatar.png'),

    /*
    |--------------------------------------------------------------------------
    | Standaardavatar: transparant bij nieuwe tenant
    |--------------------------------------------------------------------------
    | Uren na aanmaken van het gekoppelde bedrijf: standaardavatar (zonder eigen foto)
    | wordt extra transparant getoond (visuele hint “nieuw project”).
    */
    'default_avatar_fade_new_company_hours' => (int) env('NEXA_DEFAULT_AVATAR_FADE_NEW_COMPANY_HOURS', 72),
];

<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Nexa release version (platform)
  |--------------------------------------------------------------------------
  |
  | Wordt na een geslaagde upgrade verhoogd en opgeslagen in general_settings.
  |
  */
  'release_version' => env('NEXA_RELEASE_VERSION', '1.0.0'),

  /*
  |--------------------------------------------------------------------------
  | Web upgrade via admin
  |--------------------------------------------------------------------------
  */
  'web_upgrade_enabled' => env('NEXA_WEB_UPGRADE_ENABLED', true),

  /*
  |--------------------------------------------------------------------------
  | Standaard branding (logo / avatar zonder upload)
  |--------------------------------------------------------------------------
  */
  'default_logo' => env('NEXA_DEFAULT_LOGO', 'images/nexa-x-logo.png'),
  'default_user_avatar' => env('NEXA_DEFAULT_USER_AVATAR', 'images/nexa-x-logo.png'),

  /*
  |--------------------------------------------------------------------------
  | Transparantie standaardavatar voor nieuwe tenants (uren)
  |--------------------------------------------------------------------------
  */
  'default_avatar_fade_new_company_hours' => env('NEXA_DEFAULT_AVATAR_FADE_NEW_COMPANY_HOURS', 72),
];

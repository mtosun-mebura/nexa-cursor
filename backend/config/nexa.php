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
];

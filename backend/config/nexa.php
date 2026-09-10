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
    | Host-pad van de git-repo (voor PHP-Docker-upgrade vanuit de container)
    |--------------------------------------------------------------------------
    */
    'host_project_dir' => env('NEXA_HOST_PROJECT_DIR'),

    /*
    |--------------------------------------------------------------------------
    | Lokale compose-binary (buiten bind-mounts, voorkomt ETXTBSY)
    |--------------------------------------------------------------------------
    */
    'docker_compose_local_bin' => env('NEXA_DOCKER_COMPOSE_LOCAL_BIN', '/tmp/nexa-docker-compose'),

    /*
  |--------------------------------------------------------------------------
  | Standaard branding (logo / avatar zonder upload)
  |--------------------------------------------------------------------------
  */
    'default_logo' => env('NEXA_DEFAULT_LOGO', 'images/nexa-logo.png'),
    'default_logo_dark' => env('NEXA_DEFAULT_LOGO_DARK', 'images/nexa-logo-dark.png'),
    'default_user_avatar' => env('NEXA_DEFAULT_USER_AVATAR', 'images/nexa-x-logo.png'),

    /*
  |--------------------------------------------------------------------------
  | Transparantie standaardavatar voor nieuwe tenants (uren)
  |--------------------------------------------------------------------------
  */
    'default_avatar_fade_new_company_hours' => env('NEXA_DEFAULT_AVATAR_FADE_NEW_COMPANY_HOURS', 72),
];

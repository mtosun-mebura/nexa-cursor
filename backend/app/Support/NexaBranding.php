<?php

namespace App\Support;

class NexaBranding
{
    public static function defaultLogoPath(): string
    {
        $path = (string) config('nexa.default_logo', 'images/nexa-x-logo.png');

        return $path !== '' ? $path : 'images/nexa-x-logo.png';
    }

    public static function defaultUserAvatarPath(): string
    {
        $path = (string) config('nexa.default_user_avatar', 'images/nexa-x-logo.png');

        return $path !== '' ? $path : 'images/nexa-x-logo.png';
    }

    public static function defaultLogoUrl(): string
    {
        return asset(self::defaultLogoPath());
    }

    public static function defaultUserAvatarUrl(): string
    {
        return asset(self::defaultUserAvatarPath());
    }
}

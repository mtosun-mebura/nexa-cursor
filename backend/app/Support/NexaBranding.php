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

    public static function defaultLogoDataUri(): ?string
    {
        $candidates = [
            public_path(self::defaultLogoPath()),
            public_path('images/nexa-logo.png'),
        ];

        foreach ($candidates as $path) {
            if (! is_file($path)) {
                continue;
            }

            $binary = @file_get_contents($path);
            if ($binary === false) {
                continue;
            }

            $mime = @mime_content_type($path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($binary);
        }

        return null;
    }

    public static function defaultUserAvatarUrl(): string
    {
        return asset(self::defaultUserAvatarPath());
    }
}

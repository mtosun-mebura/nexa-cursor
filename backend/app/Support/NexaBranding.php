<?php

namespace App\Support;

use Illuminate\Mail\Message;

class NexaBranding
{
    /** Vervangen bij verzending door ingesloten Nexa-logo. */
    public const EMAIL_LOGO_PLACEHOLDER = '<!--NEXA_BRAND_LOGO-->';

    public static function defaultLogoPath(): string
    {
        $path = (string) config('nexa.default_logo', 'images/nexa-logo.png');

        return $path !== '' ? $path : 'images/nexa-logo.png';
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

    /**
     * @return array{NEXA_LOGO: string}
     */
    public static function emailLogoTemplateVariable(): array
    {
        return ['NEXA_LOGO' => self::EMAIL_LOGO_PLACEHOLDER];
    }

    public static function emailLogoImgHtml(string $src): string
    {
        return '<img class="nexa-email-logo" src="'.e($src).'" alt="NEXA" width="140" height="40" style="display:block;height:40px;width:auto;max-height:40px;max-width:180px;border:0;outline:none;text-decoration:none;margin:0 0 10px 0;" />';
    }

    public static function emailLogoPreviewHtml(): string
    {
        return self::emailLogoImgHtml(self::defaultLogoUrl());
    }

    public static function injectPreviewLogo(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'NEXA_LOGO') && ! str_contains($html, self::EMAIL_LOGO_PLACEHOLDER))) {
            return $html;
        }

        $logoHtml = self::emailLogoPreviewHtml();
        foreach (['{{ NEXA_LOGO }}', '{{NEXA_LOGO}}', '{ NEXA_LOGO }', '{NEXA_LOGO}'] as $placeholder) {
            $html = str_replace($placeholder, $logoHtml, $html);
        }

        return str_replace(self::EMAIL_LOGO_PLACEHOLDER, $logoHtml, $html);
    }

    public static function embedInMessage(string $html, Message $message): string
    {
        foreach (['{{ NEXA_LOGO }}', '{{NEXA_LOGO}}', '{ NEXA_LOGO }', '{NEXA_LOGO}'] as $placeholder) {
            $html = str_replace($placeholder, self::EMAIL_LOGO_PLACEHOLDER, $html);
        }

        if (! str_contains($html, self::EMAIL_LOGO_PLACEHOLDER)) {
            return $html;
        }

        $path = public_path(self::defaultLogoPath());
        if (! is_file($path)) {
            return str_replace(self::EMAIL_LOGO_PLACEHOLDER, self::emailLogoPreviewHtml(), $html);
        }

        $cid = $message->embed($path);

        return str_replace(self::EMAIL_LOGO_PLACEHOLDER, self::emailLogoImgHtml($cid), $html);
    }
}

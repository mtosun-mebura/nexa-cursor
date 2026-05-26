<?php

namespace App\Http\Middleware;

use App\Services\EnvService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Overlay config with values from GeneralSetting (via EnvService) so Mail, etc. use DB settings.
 */
class OverlayGeneralSettingConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('mail_config_overlay_applied')) {
            $mail = app(EnvService::class)->getMailOverlayValues();

            config([
                'mail.default' => $mail['MAIL_MAILER'] ?? config('mail.default'),
                'mail.from.address' => $mail['MAIL_FROM_ADDRESS'] ?? config('mail.from.address'),
                'mail.from.name' => $mail['MAIL_FROM_NAME'] ?? config('mail.from.name'),
                'mail.mailers.smtp.host' => $mail['MAIL_HOST'] ?? config('mail.mailers.smtp.host'),
                'mail.mailers.smtp.port' => $mail['MAIL_PORT'] ?? config('mail.mailers.smtp.port'),
                'mail.mailers.smtp.username' => $mail['MAIL_USERNAME'] ?? config('mail.mailers.smtp.username'),
                'mail.mailers.smtp.password' => $mail['MAIL_PASSWORD'] ?? config('mail.mailers.smtp.password'),
                'mail.mailers.smtp.encryption' => $mail['MAIL_ENCRYPTION'] ?? 'tls',
            ]);

            app()->instance('mail_config_overlay_applied', true);
        }

        return $next($request);
    }
}

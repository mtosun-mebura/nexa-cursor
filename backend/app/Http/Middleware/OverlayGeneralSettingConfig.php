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
        $env = app(EnvService::class);

        config([
            'mail.default' => $env->get('MAIL_MAILER', config('mail.default')),
            'mail.from.address' => $env->get('MAIL_FROM_ADDRESS', config('mail.from.address')),
            'mail.from.name' => $env->get('MAIL_FROM_NAME', config('mail.from.name')),
            'mail.mailers.smtp.host' => $env->get('MAIL_HOST', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => $env->get('MAIL_PORT', config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.username' => $env->get('MAIL_USERNAME', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => $env->get('MAIL_PASSWORD', config('mail.mailers.smtp.password')),
            'mail.mailers.smtp.encryption' => $env->get('MAIL_ENCRYPTION', 'tls'),
        ]);

        return $next($request);
    }
}

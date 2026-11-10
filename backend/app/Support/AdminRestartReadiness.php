<?php

namespace App\Support;

/**
 * Beslist of een HTTP-antwoord betekent dat de admin na een herstart weer bereikbaar is.
 * De upgrade-pagina gebruikt dezelfde statuslijst in de browser.
 */
class AdminRestartReadiness
{
    /**
     * 2xx is de pagina zelf. 3xx is een redirect, zoals naar login of het dashboard.
     * 401 en 419 zijn diezelfde login-redirect wanneer de status-call JSON vraagt.
     *
     * @return list<int>
     */
    public static function upStatuses(): array
    {
        return [200, 201, 204, 301, 302, 303, 307, 308, 401, 419];
    }

    public static function isUp(?int $status, bool $opaqueRedirect = false): bool
    {
        if ($opaqueRedirect) {
            return true;
        }

        if ($status === null || $status < 100) {
            return false;
        }

        if ($status >= 500) {
            return false;
        }

        return in_array($status, self::upStatuses(), true);
    }
}

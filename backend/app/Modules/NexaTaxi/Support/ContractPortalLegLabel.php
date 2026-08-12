<?php

namespace App\Modules\NexaTaxi\Support;

use Carbon\Carbon;

final class ContractPortalLegLabel
{
    /**
     * @return array{0: string, 1: string}
     */
    public static function forPlannedAt(?Carbon $plannedAt, ?string $tz = null): array
    {
        $tz = $tz ?: config('app.timezone', 'Europe/Amsterdam');
        if (! $plannedAt) {
            return ['heen', 'Heen'];
        }

        $local = $plannedAt->copy()->timezone($tz);
        if ((int) $local->format('H') < 12) {
            return ['heen', 'Heen'];
        }

        return ['retour', 'Retour'];
    }
}

<?php

namespace App\Modules\NexaTaxi\Support;

use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
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
            return ['heen', 'Heenweg'];
        }

        $local = $plannedAt->copy()->timezone($tz);
        if ((int) $local->format('H') < 12) {
            return ['heen', 'Heenweg'];
        }

        return ['retour', 'Terugweg'];
    }

    /**
     * Gebruik route-richting als die bekend is (terugweg ≠ ochtend-uur), anders tijdstip.
     *
     * @return array{0: string, 1: string}
     */
    public static function forDirectionOrPlannedAt(
        ?string $direction,
        ?Carbon $plannedAt,
        ?string $tz = null
    ): array {
        $direction = strtolower(trim((string) $direction));

        if ($direction === TransportRouteTemplate::DIRECTION_RETURN || $direction === 'retour') {
            return ['retour', 'Terugweg'];
        }

        if ($direction === TransportRouteTemplate::DIRECTION_OUTBOUND || $direction === 'heen') {
            return ['heen', 'Heenweg'];
        }

        return self::forPlannedAt($plannedAt, $tz);
    }

    public static function displayLabel(?string $legKey, ?string $legLabel = null): string
    {
        $key = strtolower(trim((string) $legKey));
        if ($key === 'retour' || preg_match('/terug|retour/i', (string) $legLabel)) {
            return 'Terugweg';
        }
        if ($key === 'heen' || preg_match('/heen/i', (string) $legLabel)) {
            return 'Heenweg';
        }

        $label = trim((string) $legLabel);

        return $label !== '' ? $label : 'Rit';
    }
}

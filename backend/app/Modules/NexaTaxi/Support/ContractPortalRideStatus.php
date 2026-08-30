<?php

namespace App\Modules\NexaTaxi\Support;

use Carbon\CarbonInterface;

final class ContractPortalRideStatus
{
    /**
     * Geplande rit waarvan het ophaalmoment voorbij is, zonder dat de rit is gestart.
     */
    public static function applyExpiry(string $statusKey, ?CarbonInterface $plannedAt): string
    {
        if ($statusKey !== 'planned' || $plannedAt === null) {
            return $statusKey;
        }

        $wall = ContractTransportTimezone::asAmsterdamWall($plannedAt);
        if (! $wall) {
            return $statusKey;
        }

        if ($wall->lt(now(ContractTransportTimezone::TIMEZONE))) {
            return 'expired';
        }

        return $statusKey;
    }

    public static function label(string $statusKey): string
    {
        return match ($statusKey) {
            'absent' => 'Afwezig / afgemeld',
            'picked_up' => 'Opgehaald',
            'completed' => 'Bestemming bereikt',
            'arrived' => 'Chauffeur ter plaatse',
            'en_route' => 'Chauffeur onderweg',
            'planned' => 'Gepland',
            'expired' => 'Verlopen',
            default => 'Geen rit vandaag',
        };
    }

    /**
     * Aantal ritten voor week-/dagtellers: afwezig/afgemeld telt niet mee.
     *
     * @param  array<string, mixed>  $item
     */
    public static function countableRideCount(array $item): int
    {
        $dayStatus = (string) ($item['day_status'] ?? '');
        if (in_array($dayStatus, ['absent', 'exception', 'none'], true)) {
            return 0;
        }
        if (($item['status_key'] ?? '') === 'absent') {
            return 0;
        }

        $legs = $item['legs'] ?? [];
        if (! is_array($legs) || $legs === []) {
            return 0;
        }

        $count = 0;
        foreach ($legs as $leg) {
            if (! is_array($leg)) {
                continue;
            }
            if (($leg['status_key'] ?? '') === 'absent') {
                continue;
            }
            $count++;
        }

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $legs
     */
    public static function allLegsAbsent(array $legs): bool
    {
        if ($legs === []) {
            return false;
        }

        foreach ($legs as $leg) {
            if (! is_array($leg) || ($leg['status_key'] ?? '') !== 'absent') {
                return false;
            }
        }

        return true;
    }
}

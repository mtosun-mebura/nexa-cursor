<?php

namespace App\Modules\NexaTaxi\Support;

final class ContractPortalDayLegs
{
    /**
     * Zet bij meerdere stops op één dag de vroegste op Heen en de latere op Terug.
     * Voorkomt dat beide als "heen" eindigen (bijv. beide vóór 12:00) en Terug verdwijnt.
     *
     * @param  list<array<string, mixed>>  $legs
     * @return list<array<string, mixed>>
     */
    public static function normalizeHeenTerug(array $legs): array
    {
        $rows = array_values(array_filter($legs, fn ($leg) => is_array($leg)));
        if ($rows === []) {
            return [];
        }

        usort($rows, function (array $a, array $b): int {
            return strcmp((string) ($a['planned_at'] ?? ''), (string) ($b['planned_at'] ?? ''));
        });

        if (count($rows) === 1) {
            $key = (string) ($rows[0]['leg_key'] ?? 'heen');
            $rows[0]['leg_key'] = $key === 'retour' ? 'retour' : 'heen';
            $rows[0]['leg_label'] = $rows[0]['leg_key'] === 'retour' ? 'Terugweg' : 'Heenweg';

            return $rows;
        }

        foreach ($rows as $index => &$row) {
            if ($index === 0) {
                $row['leg_key'] = 'heen';
                $row['leg_label'] = 'Heenweg';
            } else {
                $row['leg_key'] = 'retour';
                $row['leg_label'] = 'Terugweg';
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * Eén heen- en één retourrit per passagier per dag.
     * Dubbele ochtendstops (zelfde route, andere ride) worden samengevoegd.
     *
     * @param  list<array<string, mixed>>  $legs
     * @return list<array<string, mixed>>
     */
    public static function uniqueBySlot(array $legs): array
    {
        $legs = self::normalizeHeenTerug($legs);
        $byKey = [];
        foreach ($legs as $leg) {
            if (! is_array($leg)) {
                continue;
            }
            $key = (string) ($leg['leg_key'] ?? 'heen');
            if (! isset($byKey[$key])) {
                $byKey[$key] = $leg;

                continue;
            }
            $byKey[$key] = self::prefer($byKey[$key], $leg);
        }

        // Heen vóór Terug voor stabiele UI-volgorde.
        $ordered = [];
        if (isset($byKey['heen'])) {
            $ordered[] = $byKey['heen'];
        }
        if (isset($byKey['retour'])) {
            $ordered[] = $byKey['retour'];
        }
        foreach ($byKey as $key => $leg) {
            if ($key === 'heen' || $key === 'retour') {
                continue;
            }
            $ordered[] = $leg;
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private static function prefer(array $current, array $candidate): array
    {
        $currentTerminal = self::isTerminalStatus((string) ($current['status_key'] ?? ''));
        $candidateTerminal = self::isTerminalStatus((string) ($candidate['status_key'] ?? ''));

        // Openstaande/verlopen rit gaat vóór een al afgeronde dubbele slot.
        if ($currentTerminal && ! $candidateTerminal) {
            return $candidate;
        }
        if (! $currentTerminal && $candidateTerminal) {
            return $current;
        }

        $currentRank = self::statusRank((string) ($current['status_key'] ?? ''));
        $candidateRank = self::statusRank((string) ($candidate['status_key'] ?? ''));

        if ($candidateRank > $currentRank) {
            return self::mergeActionMeta($candidate, $current);
        }

        // Behoud actieknoppen / stop-id als de gekozen leg die mist.
        return self::mergeActionMeta($current, $candidate);
    }

    /**
     * @param  array<string, mixed>  $winner
     * @param  array<string, mixed>  $other
     * @return array<string, mixed>
     */
    private static function mergeActionMeta(array $winner, array $other): array
    {
        if (empty($winner['ride_stop_id']) && ! empty($other['ride_stop_id'])) {
            $winner['ride_stop_id'] = $other['ride_stop_id'];
        }
        if (empty($winner['ride_request_id']) && ! empty($other['ride_request_id'])) {
            $winner['ride_request_id'] = $other['ride_request_id'];
        }
        if (empty($winner['ride_status']) && ! empty($other['ride_status'])) {
            $winner['ride_status'] = $other['ride_status'];
        }
        $winner['can_start'] = (bool) ($winner['can_start'] ?? false) || (bool) ($other['can_start'] ?? false);
        $winner['can_complete'] = (bool) ($winner['can_complete'] ?? false) || (bool) ($other['can_complete'] ?? false);
        $winner['can_board'] = (bool) ($winner['can_board'] ?? false) || (bool) ($other['can_board'] ?? false);
        $winner['can_skip'] = (bool) ($winner['can_skip'] ?? false) || (bool) ($other['can_skip'] ?? false);
        if (empty($winner['status_banner']) && ! empty($other['status_banner'])) {
            $winner['status_banner'] = $other['status_banner'];
        }

        return $winner;
    }

    private static function isTerminalStatus(string $statusKey): bool
    {
        return in_array($statusKey, ['completed', 'absent', 'not_taken'], true);
    }

    private static function statusRank(string $statusKey): int
    {
        return match ($statusKey) {
            'completed' => 70,
            'picked_up' => 60,
            'arrived' => 50,
            'en_route' => 40,
            'planned' => 30,
            'expired' => 20,
            'not_taken' => 15,
            'absent' => 10,
            default => 0,
        };
    }
}

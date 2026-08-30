<?php

namespace App\Modules\NexaTaxi\Support;

final class ContractPortalDayLegs
{
    /**
     * Eén heen- en één retourrit per passagier per dag.
     * Dubbele ochtendstops (zelfde route, andere ride) worden samengevoegd.
     *
     * @param  list<array<string, mixed>>  $legs
     * @return list<array<string, mixed>>
     */
    public static function uniqueBySlot(array $legs): array
    {
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

        return array_values($byKey);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $candidate
     * @return array<string, mixed>
     */
    private static function prefer(array $current, array $candidate): array
    {
        $currentRank = self::statusRank((string) ($current['status_key'] ?? ''));
        $candidateRank = self::statusRank((string) ($candidate['status_key'] ?? ''));

        if ($candidateRank > $currentRank) {
            return $candidate;
        }

        return $current;
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
            'absent' => 10,
            default => 0,
        };
    }
}

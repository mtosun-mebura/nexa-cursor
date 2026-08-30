<?php

namespace App\Modules\NexaTaxi\Support;

use Illuminate\Support\Collection;

final class ContractPortalNavigationRoute
{
    private const SKIP_STATUS = ['absent', 'completed', 'skipped', 'none'];

    /**
     * Route for today's remaining pickups: each client is a waypoint, then unique destinations.
     *
     * @param  Collection<int, array<string, mixed>>|list<array<string, mixed>>  $items
     * @return array{leg_key: string|null, leg_label: string|null, stops: list<array<string, mixed>>}
     */
    public static function fromDayItems(Collection|array $items): array
    {
        $rows = Collection::make($items);
        $remaining = [];

        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (($item['status_key'] ?? '') === 'absent') {
                continue;
            }

            $name = trim((string) ($item['name'] ?? ''));
            $legs = $item['legs'] ?? [];
            if (! is_array($legs) || $legs === []) {
                continue;
            }

            foreach ($legs as $leg) {
                if (! is_array($leg)) {
                    continue;
                }
                $status = (string) ($leg['status_key'] ?? '');
                if (in_array($status, self::SKIP_STATUS, true)) {
                    continue;
                }

                $remaining[] = [
                    'name' => $name,
                    'leg_key' => (string) ($leg['leg_key'] ?? ''),
                    'leg_label' => (string) ($leg['leg_label'] ?? ''),
                    'planned_at' => (string) ($leg['planned_at'] ?? ''),
                    'picked_up' => (bool) ($leg['picked_up'] ?? false),
                    'pickup_address' => trim((string) ($leg['pickup_address'] ?? $item['pickup_address'] ?? '')),
                    'pickup_lat' => self::floatOrNull($leg['pickup_lat'] ?? null),
                    'pickup_lng' => self::floatOrNull($leg['pickup_lng'] ?? null),
                    'destination_address' => trim((string) ($leg['destination_address'] ?? $item['destination_address'] ?? '')),
                    'destination_lat' => self::floatOrNull($leg['destination_lat'] ?? null),
                    'destination_lng' => self::floatOrNull($leg['destination_lng'] ?? null),
                ];
            }
        }

        usort($remaining, function (array $a, array $b): int {
            $cmp = strcmp($a['planned_at'], $b['planned_at']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['name'], $b['name']);
        });

        if ($remaining === []) {
            return self::fallbackFromPassengers($rows);
        }

        $waveKey = $remaining[0]['leg_key'];
        $wave = array_values(array_filter(
            $remaining,
            fn (array $row) => $row['leg_key'] === $waveKey
        ));

        $stops = [];
        foreach ($wave as $row) {
            if ($row['picked_up'] || $row['pickup_address'] === '') {
                continue;
            }
            self::pushStop($stops, [
                'kind' => 'pickup',
                'label' => 'Ophalen',
                'name' => $row['name'] !== '' ? $row['name'] : null,
                'address' => $row['pickup_address'],
                'lat' => $row['pickup_lat'],
                'lng' => $row['pickup_lng'],
                'planned_at' => $row['planned_at'] !== '' ? $row['planned_at'] : null,
            ]);
        }

        foreach ($wave as $row) {
            if ($row['destination_address'] === '') {
                continue;
            }
            self::pushStop($stops, [
                'kind' => 'dropoff',
                'label' => 'Afzetten',
                'name' => null,
                'address' => $row['destination_address'],
                'lat' => $row['destination_lat'],
                'lng' => $row['destination_lng'],
                'planned_at' => null,
            ]);
        }

        return [
            'leg_key' => $waveKey !== '' ? $waveKey : null,
            'leg_label' => $wave[0]['leg_label'] !== '' ? $wave[0]['leg_label'] : null,
            'stops' => $stops,
        ];
    }

    /**
     * No planned legs today: still visit each visible client pickup.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{leg_key: string|null, leg_label: string|null, stops: list<array<string, mixed>>}
     */
    private static function fallbackFromPassengers(Collection $rows): array
    {
        $stops = [];
        foreach ($rows as $item) {
            if (! is_array($item) || ($item['status_key'] ?? '') === 'absent') {
                continue;
            }
            $name = trim((string) ($item['name'] ?? ''));
            $address = trim((string) ($item['pickup_address'] ?? ''));
            if ($address === '') {
                continue;
            }
            self::pushStop($stops, [
                'kind' => 'pickup',
                'label' => 'Ophalen',
                'name' => $name !== '' ? $name : null,
                'address' => $address,
                'lat' => self::floatOrNull($item['pickup_lat'] ?? null),
                'lng' => self::floatOrNull($item['pickup_lng'] ?? null),
                'planned_at' => null,
            ]);
        }

        foreach ($rows as $item) {
            if (! is_array($item)) {
                continue;
            }
            $dest = trim((string) ($item['destination_address'] ?? ''));
            if ($dest === '') {
                continue;
            }
            self::pushStop($stops, [
                'kind' => 'dropoff',
                'label' => 'Afzetten',
                'name' => null,
                'address' => $dest,
                'lat' => self::floatOrNull($item['destination_lat'] ?? null),
                'lng' => self::floatOrNull($item['destination_lng'] ?? null),
                'planned_at' => null,
            ]);
        }

        return [
            'leg_key' => null,
            'leg_label' => null,
            'stops' => $stops,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $stops
     * @param  array{
     *     kind: string,
     *     label: string,
     *     name: string|null,
     *     address: string,
     *     lat: float|null,
     *     lng: float|null,
     *     planned_at: string|null
     * }  $stop
     */
    private static function pushStop(array &$stops, array $stop): void
    {
        $normalized = self::normalizeAddress($stop['address']);
        if ($normalized === '') {
            return;
        }

        $last = $stops !== [] ? $stops[array_key_last($stops)] : null;
        if ($last && self::normalizeAddress((string) $last['address']) === $normalized) {
            return;
        }

        $stops[] = $stop;
    }

    private static function normalizeAddress(string $address): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($address)) ?? '';

        return mb_strtolower($collapsed);
    }

    private static function floatOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value)) {
            return null;
        }

        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }
}

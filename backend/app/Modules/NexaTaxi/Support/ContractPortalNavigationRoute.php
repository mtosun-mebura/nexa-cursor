<?php

namespace App\Modules\NexaTaxi\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;

final class ContractPortalNavigationRoute
{
    private const SKIP_STATUS = ['absent', 'completed', 'skipped', 'none'];

    /** Actief onderweg — altijd deze golf. */
    private const IN_PROGRESS = ['en_route', 'arrived', 'picked_up'];

    /** Na laatste ophaalmoment: golf blijft zo lang "live", daarna klaar. */
    private const WAVE_GRACE_MINUTES = 90;

    /** Heenweg zonder tijden eindigt standaard na 12:00 lokale tijd. */
    private const HEEN_FALLBACK_END_HOUR = 12;

    /**
     * Route for today's remaining pickups: each client is a waypoint, then unique destinations.
     *
     * @param  Collection<int, array<string, mixed>>|list<array<string, mixed>>  $items
     * @return array{
     *     leg_key: string|null,
     *     leg_label: string|null,
     *     hub_label: string|null,
     *     hub_address: string|null,
     *     stops: list<array<string, mixed>>
     * }
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
                    'leg_label' => ContractPortalLegLabel::displayLabel(
                        (string) ($leg['leg_key'] ?? ''),
                        (string) ($leg['leg_label'] ?? '')
                    ),
                    'status_key' => $status,
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
            return self::withHub(self::fallbackFromPassengers($rows));
        }

        $wave = self::selectActiveWave($remaining);
        if ($wave === []) {
            return self::withHub([
                'leg_key' => null,
                'leg_label' => null,
                'stops' => [],
            ]);
        }

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
                'leg_label' => $row['leg_label'] !== '' ? $row['leg_label'] : null,
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
                'leg_label' => $row['leg_label'] !== '' ? $row['leg_label'] : null,
            ]);
        }

        $waveKey = (string) ($wave[0]['leg_key'] ?? '');
        $legLabel = ContractPortalLegLabel::displayLabel($waveKey, (string) ($wave[0]['leg_label'] ?? ''));

        return self::withHub([
            'leg_key' => $waveKey !== '' ? $waveKey : null,
            'leg_label' => $legLabel,
            'stops' => $stops,
        ], $wave);
    }

    /**
     * Heenweg: doel = school/bestemming. Terugweg: ophalen vanaf school.
     *
     * @param  array{leg_key: string|null, leg_label: string|null, stops: list<array<string, mixed>>}  $route
     * @param  list<array<string, mixed>>  $wave
     * @return array{
     *     leg_key: string|null,
     *     leg_label: string|null,
     *     hub_label: string|null,
     *     hub_address: string|null,
     *     stops: list<array<string, mixed>>
     * }
     */
    private static function withHub(array $route, array $wave = []): array
    {
        $key = (string) ($route['leg_key'] ?? '');
        $hubLabel = null;
        $hubAddress = null;

        if ($key === 'retour') {
            $hubLabel = 'Ophalen vanaf';
            foreach ($wave as $row) {
                $addr = trim((string) ($row['pickup_address'] ?? ''));
                if ($addr !== '') {
                    $hubAddress = $addr;
                    break;
                }
            }
            if ($hubAddress === null) {
                foreach ($route['stops'] as $stop) {
                    if (($stop['kind'] ?? '') === 'pickup' && ! empty($stop['address'])) {
                        $hubAddress = (string) $stop['address'];
                        break;
                    }
                }
            }
        } else {
            $hubLabel = 'Doel';
            foreach ($wave as $row) {
                $addr = trim((string) ($row['destination_address'] ?? ''));
                if ($addr !== '') {
                    $hubAddress = $addr;
                    break;
                }
            }
            if ($hubAddress === null) {
                foreach ($route['stops'] as $stop) {
                    if (($stop['kind'] ?? '') === 'dropoff' && ! empty($stop['address'])) {
                        $hubAddress = (string) $stop['address'];
                        break;
                    }
                }
            }
        }

        $route['hub_label'] = $hubAddress ? $hubLabel : null;
        $route['hub_address'] = $hubAddress;

        return $route;
    }

    /**
     * Actieve rit op basis van tijdvenster per golf:
     * 1) onderweg, 2) golf waarvan het ophaalvenster nu loopt,
     * 3) eerstvolgende nog niet afgelopen golf (vaak Terugweg ná Heenweg).
     * Afgelopen heenweg (ochtend) komt nooit meer terug als stale fallback.
     *
     * @param  list<array<string, mixed>>  $remaining
     * @return list<array<string, mixed>>
     */
    private static function selectActiveWave(array $remaining): array
    {
        $byKey = [];
        foreach ($remaining as $row) {
            $key = (string) ($row['leg_key'] ?? '');
            if ($key === '') {
                $key = 'heen';
            }
            $byKey[$key][] = $row;
        }

        $order = [];
        foreach (['heen', 'retour'] as $preferred) {
            if (isset($byKey[$preferred])) {
                $order[] = $preferred;
            }
        }
        foreach (array_keys($byKey) as $key) {
            if (! in_array($key, $order, true)) {
                $order[] = $key;
            }
        }

        $now = Carbon::now(ContractTransportTimezone::TIMEZONE);

        foreach ($order as $key) {
            if (self::waveIsInProgress($byKey[$key])) {
                return $byKey[$key];
            }
        }

        $alive = [];
        foreach ($order as $key) {
            $wave = $byKey[$key];
            if (self::waveIsEnded($wave, $key, $now)) {
                continue;
            }
            if (! self::waveHasOpenStops($wave)) {
                continue;
            }
            $alive[$key] = $wave;
        }

        if ($alive === []) {
            return [];
        }

        foreach ($order as $key) {
            if (isset($alive[$key]) && self::waveIsInWindow($alive[$key], $now)) {
                return $alive[$key];
            }
        }

        // Nog geen venster actief: toon de eerstvolgende rit (vaak Terugweg later vandaag).
        $nextKey = null;
        $nextAt = null;
        foreach ($alive as $key => $wave) {
            $start = self::waveEarliestPlanned($wave);
            if ($start === null) {
                if ($nextKey === null) {
                    $nextKey = $key;
                }
                continue;
            }
            if ($nextAt === null || $start->lt($nextAt)) {
                $nextAt = $start;
                $nextKey = $key;
            }
        }

        return $nextKey !== null ? $alive[$nextKey] : [];
    }

    /**
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveIsInProgress(array $wave): bool
    {
        foreach ($wave as $row) {
            if (in_array((string) ($row['status_key'] ?? ''), self::IN_PROGRESS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Golf is afgelopen: alles afgerond/verlopen, of laatste ophaal + grace voorbij.
     *
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveIsEnded(array $wave, string $legKey, Carbon $now): bool
    {
        if (self::waveIsInProgress($wave)) {
            return false;
        }

        $hasActionable = false;
        foreach ($wave as $row) {
            $status = (string) ($row['status_key'] ?? '');
            if ($status === 'expired') {
                continue;
            }
            if ($row['picked_up'] ?? false) {
                continue;
            }
            if (in_array($status, self::SKIP_STATUS, true)) {
                continue;
            }
            $hasActionable = true;
        }

        if (! $hasActionable) {
            return true;
        }

        $latest = self::waveLatestPlanned($wave);
        if ($latest) {
            return $latest->copy()->addMinutes(self::WAVE_GRACE_MINUTES)->lt($now);
        }

        // Geen tijden: heenweg niet de hele dag laten staan.
        if ($legKey === 'heen' || $legKey === '') {
            return (int) $now->format('H') >= self::HEEN_FALLBACK_END_HOUR;
        }

        return false;
    }

    /**
     * Nu binnen ophaalvenster: vanaf vroegste stop tot laatste + grace.
     *
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveIsInWindow(array $wave, Carbon $now): bool
    {
        $earliest = self::waveEarliestPlanned($wave);
        $latest = self::waveLatestPlanned($wave);
        if (! $earliest || ! $latest) {
            return false;
        }

        $start = $earliest->copy()->subMinutes(30);
        $end = $latest->copy()->addMinutes(self::WAVE_GRACE_MINUTES);

        return $now->betweenIncluded($start, $end);
    }

    /**
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveEarliestPlanned(array $wave): ?Carbon
    {
        $best = null;
        foreach ($wave as $row) {
            $planned = self::parsePlannedAt((string) ($row['planned_at'] ?? ''));
            if (! $planned) {
                continue;
            }
            if ($best === null || $planned->lt($best)) {
                $best = $planned;
            }
        }

        return $best;
    }

    /**
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveLatestPlanned(array $wave): ?Carbon
    {
        $best = null;
        foreach ($wave as $row) {
            $planned = self::parsePlannedAt((string) ($row['planned_at'] ?? ''));
            if (! $planned) {
                continue;
            }
            if ($best === null || $planned->gt($best)) {
                $best = $planned;
            }
        }

        return $best;
    }

    /**
     * @param  list<array<string, mixed>>  $wave
     */
    private static function waveHasOpenStops(array $wave): bool
    {
        foreach ($wave as $row) {
            if (! ($row['picked_up'] ?? false) && trim((string) ($row['pickup_address'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function parsePlannedAt(string $raw): ?Carbon
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->timezone(ContractTransportTimezone::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
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
                'leg_label' => null,
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
                'leg_label' => null,
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
     *     planned_at: string|null,
     *     leg_label?: string|null
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

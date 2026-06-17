<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportRouteStop;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TransportRoutePlannerService
{
    private const AVG_SPEED_KMH = 35;

    private const EARLY_DEPARTURE_WARNING = '05:00:00';

    /**
     * @return array{
     *   stops: list<array{
     *     stop_type: string,
     *     transport_passenger_id: int|null,
     *     passenger_name: string|null,
     *     address: string,
     *     lat: float|null,
     *     lng: float|null,
     *     planned_at_time: string,
     *     sequence: int
     *   }>,
     *   warnings: list<string>,
     *   departure_time: string|null
     * }
     */
    public function planRoute(
        TransportGroup $group,
        TransportRouteTemplate $template,
        Collection $activeMembers,
    ): array {
        $pickups = $this->buildPickupStops($activeMembers);
        $warnings = [];

        if ($pickups === []) {
            return ['stops' => [], 'warnings' => ['Geen actieve leden met ophaaladres in deze groep.'], 'departure_time' => null];
        }

        $missingCoords = array_filter($pickups, fn (array $stop) => $stop['lat'] === null || $stop['lng'] === null);
        if ($missingCoords !== []) {
            $warnings[] = count($missingCoords).' stop(s) zonder coördinaten; rijtijden zijn een schatting.';
        }

        $destination = $this->buildDestinationStop($group);
        if ($destination['lat'] === null || $destination['lng'] === null) {
            $warnings[] = 'Eindlocatie heeft geen coördinaten; rijtijden zijn een schatting.';
        }

        $orderedPickups = $this->orderPickups(
            $pickups,
            $template->driver_start_mode,
            $this->resolveStartPoint($template, $pickups)
        );

        $bufferSeconds = max(0, (int) ($template->buffer_seconds ?? 120));
        $arrivalTime = $this->normalizeTime($group->destination_arrival_time ?? '08:00');

        $timedStops = $this->calculateTimesBackward(
            $orderedPickups,
            $destination,
            $arrivalTime,
            $bufferSeconds
        );

        $departureTime = null;
        if ($template->driver_start_mode === 'depot') {
            $startPoint = $this->resolveStartPoint($template, $orderedPickups);
            if ($startPoint !== null && $timedStops !== []) {
                $firstPickup = $timedStops[0];
                $travelSeconds = $this->estimateTravelSeconds(
                    $startPoint['lat'],
                    $startPoint['lng'],
                    $firstPickup['lat'],
                    $firstPickup['lng']
                );
                $departureTime = $this->subtractSecondsFromTime(
                    $firstPickup['planned_at_time'],
                    $travelSeconds
                );
            }
        } elseif ($timedStops !== []) {
            $departureTime = $timedStops[0]['planned_at_time'];
        }

        if ($departureTime !== null && $departureTime < self::EARLY_DEPARTURE_WARNING) {
            $warnings[] = 'Vertrek om '.substr($departureTime, 0, 5).' lijkt vroeg; controleer de route.';
        }

        $sequence = 1;
        $stops = [];
        foreach ($timedStops as $stop) {
            $stops[] = array_merge($stop, ['sequence' => $sequence++]);
        }
        $stops[] = array_merge($destination, [
            'planned_at_time' => $arrivalTime,
            'sequence' => $sequence,
        ]);

        return [
            'stops' => $stops,
            'warnings' => $warnings,
            'departure_time' => $departureTime,
        ];
    }

    /**
     * @param  list<array{
     *     stop_type: string,
     *     transport_passenger_id: int|null,
     *     passenger_name: string|null,
     *     address: string,
     *     lat: float|null,
     *     lng: float|null,
     *     sequence: int
     *   }>  $orderedPickupsWithoutTimes
     * @return array{
     *   stops: list<array{
     *     stop_type: string,
     *     transport_passenger_id: int|null,
     *     passenger_name: string|null,
     *     address: string,
     *     lat: float|null,
     *     lng: float|null,
     *     planned_at_time: string,
     *     sequence: int
     *   }>,
     *   warnings: list<string>,
     *   departure_time: string|null
     * }
     */
    public function recalculateTimesForOrder(
        TransportGroup $group,
        TransportRouteTemplate $template,
        array $orderedPickupsWithoutTimes,
    ): array {
        $destination = $this->buildDestinationStop($group);
        $bufferSeconds = max(0, (int) ($template->buffer_seconds ?? 120));
        $arrivalTime = $this->normalizeTime($group->destination_arrival_time ?? '08:00');
        $warnings = [];

        $timedStops = $this->calculateTimesBackward(
            $orderedPickupsWithoutTimes,
            $destination,
            $arrivalTime,
            $bufferSeconds
        );

        $departureTime = null;
        if ($template->driver_start_mode === 'depot') {
            $startPoint = $this->resolveStartPoint($template, $orderedPickupsWithoutTimes);
            if ($startPoint !== null && $timedStops !== []) {
                $firstPickup = $timedStops[0];
                $travelSeconds = $this->estimateTravelSeconds(
                    $startPoint['lat'],
                    $startPoint['lng'],
                    $firstPickup['lat'],
                    $firstPickup['lng']
                );
                $departureTime = $this->subtractSecondsFromTime(
                    $firstPickup['planned_at_time'],
                    $travelSeconds
                );
            }
        } elseif ($timedStops !== []) {
            $departureTime = $timedStops[0]['planned_at_time'];
        }

        if ($departureTime !== null && $departureTime < self::EARLY_DEPARTURE_WARNING) {
            $warnings[] = 'Vertrek om '.substr($departureTime, 0, 5).' lijkt vroeg; controleer de route.';
        }

        $sequence = 1;
        $stops = [];
        foreach ($timedStops as $stop) {
            $stops[] = array_merge($stop, ['sequence' => $sequence++]);
        }
        $stops[] = array_merge($destination, [
            'planned_at_time' => $arrivalTime,
            'sequence' => $sequence,
        ]);

        return [
            'stops' => $stops,
            'warnings' => $warnings,
            'departure_time' => $departureTime,
        ];
    }

    /**
     * @param  Collection<int, TransportGroupMember>  $activeMembers
     * @return list<array{
     *   stop_type: string,
     *   transport_passenger_id: int,
     *   passenger_name: string,
     *   address: string,
     *   lat: float|null,
     *   lng: float|null
     * }>
     */
    private function buildPickupStops(Collection $activeMembers): array
    {
        $stops = [];

        foreach ($activeMembers as $member) {
            /** @var TransportPassenger|null $passenger */
            $passenger = $member->passenger;
            if (! $passenger || ! $passenger->pickup_address) {
                continue;
            }

            $stops[] = [
                'stop_type' => 'pickup',
                'transport_passenger_id' => (int) $passenger->id,
                'passenger_name' => $passenger->full_name,
                'address' => $passenger->pickup_address,
                'lat' => $passenger->pickup_lat !== null ? (float) $passenger->pickup_lat : null,
                'lng' => $passenger->pickup_lng !== null ? (float) $passenger->pickup_lng : null,
            ];
        }

        return $stops;
    }

    /**
     * @return array{
     *   stop_type: string,
     *   transport_passenger_id: null,
     *   passenger_name: null,
     *   address: string,
     *   lat: float|null,
     *   lng: float|null
     * }
     */
    private function buildDestinationStop(TransportGroup $group): array
    {
        return [
            'stop_type' => 'destination',
            'transport_passenger_id' => null,
            'passenger_name' => null,
            'address' => (string) $group->destination_address,
            'lat' => $group->destination_lat !== null ? (float) $group->destination_lat : null,
            'lng' => $group->destination_lng !== null ? (float) $group->destination_lng : null,
        ];
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $pickups
     * @return array{lat: float|null, lng: float|null, address: string|null}|null
     */
    private function resolveStartPoint(TransportRouteTemplate $template, array $pickups): ?array
    {
        if ($template->driver_start_mode === 'first_stop') {
            return null;
        }

        return [
            'lat' => $template->driver_start_lat !== null ? (float) $template->driver_start_lat : null,
            'lng' => $template->driver_start_lng !== null ? (float) $template->driver_start_lng : null,
            'address' => $template->driver_start_address,
        ];
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $pickups
     * @param  array{lat: float|null, lng: float|null, address: string|null}|null  $startPoint
     * @return list<array{lat: float|null, lng: float|null, ...}>
     */
    private function orderPickups(array $pickups, string $driverStartMode, ?array $startPoint): array
    {
        if ($pickups === []) {
            return [];
        }

        if ($driverStartMode === 'first_stop') {
            return $this->nearestNeighborFromBestStart($pickups, null);
        }

        $seedLat = $startPoint['lat'] ?? null;
        $seedLng = $startPoint['lng'] ?? null;

        if ($seedLat === null || $seedLng === null) {
            return $this->nearestNeighborFromBestStart($pickups, null);
        }

        return $this->nearestNeighborChain($pickups, $seedLat, $seedLng);
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $pickups
     * @return list<array{lat: float|null, lng: float|null, ...}>
     */
    private function nearestNeighborFromBestStart(array $pickups, ?float $seedLat, ?float $seedLng = null): array
    {
        if (count($pickups) <= 1) {
            return $pickups;
        }

        $bestOrder = $pickups;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($pickups as $candidate) {
            $startLat = $seedLat ?? $candidate['lat'];
            $startLng = $seedLng ?? $candidate['lng'];
            if ($startLat === null || $startLng === null) {
                continue;
            }

            $order = $this->nearestNeighborChain($pickups, $startLat, $startLng);
            $distance = $this->totalPathDistance($order, $startLat, $startLng);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestOrder = $order;
            }
        }

        return $bestOrder;
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $pickups
     * @return list<array{lat: float|null, lng: float|null, ...}>
     */
    private function nearestNeighborChain(array $pickups, float $startLat, float $startLng): array
    {
        $remaining = $pickups;
        $ordered = [];
        $currentLat = $startLat;
        $currentLng = $startLng;

        while ($remaining !== []) {
            $nearestIndex = 0;
            $nearestDistance = PHP_FLOAT_MAX;

            foreach ($remaining as $index => $stop) {
                $distance = $this->estimateTravelSeconds(
                    $currentLat,
                    $currentLng,
                    $stop['lat'],
                    $stop['lng']
                );
                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $next = $remaining[$nearestIndex];
            unset($remaining[$nearestIndex]);
            $remaining = array_values($remaining);
            $ordered[] = $next;

            if ($next['lat'] !== null && $next['lng'] !== null) {
                $currentLat = $next['lat'];
                $currentLng = $next['lng'];
            }
        }

        return $ordered;
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $ordered
     */
    private function totalPathDistance(array $ordered, float $startLat, float $startLng): float
    {
        $total = 0.0;
        $currentLat = $startLat;
        $currentLng = $startLng;

        foreach ($ordered as $stop) {
            $total += $this->estimateTravelSeconds(
                $currentLat,
                $currentLng,
                $stop['lat'],
                $stop['lng']
            );
            if ($stop['lat'] !== null && $stop['lng'] !== null) {
                $currentLat = $stop['lat'];
                $currentLng = $stop['lng'];
            }
        }

        return $total;
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $orderedPickups
     * @param  array{lat: float|null, lng: float|null, ...}  $destination
     * @return list<array{planned_at_time: string, ...}>
     */
    private function calculateTimesBackward(
        array $orderedPickups,
        array $destination,
        string $arrivalTime,
        int $bufferSeconds,
    ): array {
        if ($orderedPickups === []) {
            return [];
        }

        $currentTime = $arrivalTime;
        $reversed = array_reverse($orderedPickups);
        $timedReversed = [];

        $nextLat = $destination['lat'];
        $nextLng = $destination['lng'];

        foreach ($reversed as $stop) {
            $travelSeconds = $this->estimateTravelSeconds(
                $stop['lat'],
                $stop['lng'],
                $nextLat,
                $nextLng
            );
            $currentTime = $this->subtractSecondsFromTime(
                $currentTime,
                $travelSeconds + $bufferSeconds
            );
            $timedReversed[] = array_merge($stop, ['planned_at_time' => $currentTime]);
            $nextLat = $stop['lat'];
            $nextLng = $stop['lng'];
        }

        return array_reverse($timedReversed);
    }

    public function estimateTravelSecondsBetween(?float $fromLat, ?float $fromLng, ?float $toLat, ?float $toLng): int
    {
        return $this->estimateTravelSeconds($fromLat, $fromLng, $toLat, $toLng);
    }

    private function estimateTravelSeconds(?float $fromLat, ?float $fromLng, ?float $toLat, ?float $toLng): int
    {
        if ($fromLat === null || $fromLng === null || $toLat === null || $toLng === null) {
            return 300;
        }

        $distanceKm = $this->haversineKm($fromLat, $fromLng, $toLat, $toLng);
        $hours = $distanceKm / self::AVG_SPEED_KMH;

        return max(60, (int) round($hours * 3600));
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function normalizeTime(string $time): string
    {
        $parsed = Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time);

        return $parsed->format('H:i:s');
    }

    private function subtractSecondsFromTime(string $time, int $seconds): string
    {
        $parsed = Carbon::createFromFormat('H:i:s', $this->normalizeTime($time));

        return $parsed->subSeconds($seconds)->format('H:i:s');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TransportRouteStop>  $pickupStops
     */
    public function estimateDepartureTimeForTemplate(
        TransportRouteTemplate $template,
        $pickupStops,
        ?TransportRouteStop $firstPickup,
    ): ?string {
        if (! $firstPickup) {
            return null;
        }

        $firstTime = $this->normalizeTime((string) $firstPickup->planned_at_time);

        if ($template->driver_start_mode === TransportRouteTemplate::DRIVER_START_FIRST_STOP) {
            return $firstTime;
        }

        if ($template->driver_start_lat !== null && $template->driver_start_lng !== null) {
            $travelSeconds = $this->estimateTravelSecondsBetween(
                (float) $template->driver_start_lat,
                (float) $template->driver_start_lng,
                $firstPickup->lat !== null ? (float) $firstPickup->lat : null,
                $firstPickup->lng !== null ? (float) $firstPickup->lng : null,
            );

            return $this->subtractSecondsFromTime($firstTime, $travelSeconds);
        }

        return $firstTime;
    }
}

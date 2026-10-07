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
            $destination,
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
     * @param  list<array{lat: float|null, lng: float|null, transport_passenger_id?: int|null, ...}>  $pickups
     * @param  array{lat: float|null, lng: float|null, ...}  $destination
     * @param  array{lat: float|null, lng: float|null, address: string|null}|null  $startPoint
     * @return list<array{lat: float|null, lng: float|null, ...}>
     */
    private function orderPickups(
        array $pickups,
        array $destination,
        string $driverStartMode,
        ?array $startPoint,
    ): array {
        if ($pickups === []) {
            return [];
        }

        if (count($pickups) === 1) {
            return $pickups;
        }

        $lastPickup = $this->pickupClosestToDestination($pickups, $destination);
        $others = array_values(array_filter(
            $pickups,
            fn (array $stop) => (int) ($stop['transport_passenger_id'] ?? 0)
                !== (int) ($lastPickup['transport_passenger_id'] ?? 0)
        ));

        if ($others === []) {
            return [$lastPickup];
        }

        $seedLat = $startPoint['lat'] ?? null;
        $seedLng = $startPoint['lng'] ?? null;
        $hasDepotStart = $driverStartMode !== 'first_stop'
            && $seedLat !== null
            && $seedLng !== null;

        if ($hasDepotStart) {
            return $this->orderPickupsFromDepotEndingAt($others, $lastPickup, $seedLat, $seedLng);
        }

        return $this->orderPickupsBackwardFromDestination($others, $lastPickup);
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $pickups
     * @param  array{lat: float|null, lng: float|null, ...}  $destination
     * @return array{lat: float|null, lng: float|null, ...}
     */
    private function pickupClosestToDestination(array $pickups, array $destination): array
    {
        $closest = $pickups[0];
        $closestDistance = PHP_FLOAT_MAX;

        foreach ($pickups as $pickup) {
            $distance = $this->estimateTravelSeconds(
                $pickup['lat'],
                $pickup['lng'],
                $destination['lat'],
                $destination['lng']
            );
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $pickup;
            }
        }

        return $closest;
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $others
     * @return list<array{lat: float|null, lng: float|null, ...>>
     */
    private function orderPickupsBackwardFromDestination(array $others, array $lastPickup): array
    {
        $chain = [$lastPickup];
        $remaining = $others;
        $currentLat = $lastPickup['lat'];
        $currentLng = $lastPickup['lng'];

        while ($remaining !== []) {
            $nearestIndex = $this->indexOfNearestStop($remaining, $currentLat, $currentLng);
            $next = $remaining[$nearestIndex];
            unset($remaining[$nearestIndex]);
            $remaining = array_values($remaining);
            $chain[] = $next;

            if ($next['lat'] !== null && $next['lng'] !== null) {
                $currentLat = $next['lat'];
                $currentLng = $next['lng'];
            }
        }

        return array_reverse($chain);
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $others
     * @return list<array{lat: float|null, lng: float|null, ...>>
     */
    private function orderPickupsFromDepotEndingAt(
        array $others,
        array $lastPickup,
        float $depotLat,
        float $depotLng,
    ): array {
        $firstIndex = $this->indexOfNearestStop($others, $depotLat, $depotLng);
        $ordered = [$others[$firstIndex]];
        $remaining = $others;
        unset($remaining[$firstIndex]);
        $remaining = array_values($remaining);

        $currentLat = $ordered[0]['lat'] ?? $depotLat;
        $currentLng = $ordered[0]['lng'] ?? $depotLng;

        while ($remaining !== []) {
            $nearestIndex = $this->indexOfNearestStop($remaining, $currentLat, $currentLng);
            $next = $remaining[$nearestIndex];
            unset($remaining[$nearestIndex]);
            $remaining = array_values($remaining);
            $ordered[] = $next;

            if ($next['lat'] !== null && $next['lng'] !== null) {
                $currentLat = $next['lat'];
                $currentLng = $next['lng'];
            }
        }

        $ordered[] = $lastPickup;

        return $ordered;
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, ...}>  $stops
     */
    private function indexOfNearestStop(array $stops, ?float $fromLat, ?float $fromLng): int
    {
        $nearestIndex = 0;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($stops as $index => $stop) {
            $distance = $this->estimateTravelSeconds(
                $fromLat,
                $fromLng,
                $stop['lat'],
                $stop['lng']
            );
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }

        return $nearestIndex;
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

    /**
     * Terugweg: ophalen op school (return_pickup_time), rit start na boardingvertraging, daarna afzetten thuis.
     *
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
    public function planReturnRoute(
        TransportGroup $group,
        TransportRouteTemplate $template,
        Collection $activeMembers,
        ?TransportRouteTemplate $outboundTemplate = null,
    ): array {
        $homes = $this->buildPickupStops($activeMembers);
        $warnings = [];

        if ($homes === []) {
            return ['stops' => [], 'warnings' => ['Geen actieve leden met ophaaladres in deze groep.'], 'departure_time' => null];
        }

        $school = $this->buildDestinationStop($group);
        if ($school['lat'] === null || $school['lng'] === null) {
            $warnings[] = 'Schooladres heeft geen coördinaten; rijtijden zijn een schatting.';
        }

        $pickupTime = $this->normalizeTime($group->return_pickup_time ?? '15:00');
        $boardingDelayMinutes = max(0, min(120, (int) ($group->return_boarding_delay_minutes ?? TransportGroup::DEFAULT_RETURN_BOARDING_DELAY_MINUTES)));
        $departSchoolTime = $this->addSecondsToTime($pickupTime, $boardingDelayMinutes * 60);
        $bufferSeconds = max(0, (int) ($template->buffer_seconds ?? 120));

        $orderedHomes = $this->orderReturnDropoffs($homes, $school, $outboundTemplate);

        $sequence = 1;
        $stops = [];
        foreach ($orderedHomes as $home) {
            $stops[] = array_merge($home, [
                'stop_type' => TransportRouteStop::STOP_TYPE_PICKUP,
                'address' => $school['address'],
                'lat' => $school['lat'],
                'lng' => $school['lng'],
                'planned_at_time' => $pickupTime,
                'sequence' => $sequence++,
            ]);
        }

        $currentTime = $departSchoolTime;
        $fromLat = $school['lat'];
        $fromLng = $school['lng'];
        $homeCount = count($orderedHomes);

        foreach ($orderedHomes as $index => $home) {
            $travelSeconds = $this->estimateTravelSeconds(
                $fromLat,
                $fromLng,
                $home['lat'],
                $home['lng']
            );
            $currentTime = $this->addSecondsToTime($currentTime, $travelSeconds + ($index === 0 ? 0 : $bufferSeconds));
            $isLast = $index === $homeCount - 1;
            $stops[] = array_merge($home, [
                'stop_type' => $isLast
                    ? TransportRouteStop::STOP_TYPE_DESTINATION
                    : TransportRouteStop::STOP_TYPE_DROPOFF,
                'planned_at_time' => $currentTime,
                'sequence' => $sequence++,
            ]);
            $fromLat = $home['lat'];
            $fromLng = $home['lng'];
        }

        if ($boardingDelayMinutes > 0) {
            $warnings[] = 'Rit vertrekt '.substr($departSchoolTime, 0, 5)
                .' (ophaal '.substr($pickupTime, 0, 5).' + '.$boardingDelayMinutes.' min instaptijd).';
        }

        return [
            'stops' => $stops,
            'warnings' => $warnings,
            'departure_time' => $pickupTime,
        ];
    }

    /**
     * @param  list<array{lat: float|null, lng: float|null, transport_passenger_id?: int|null, ...}>  $homes
     * @param  array{lat: float|null, lng: float|null, ...}  $school
     * @return list<array{lat: float|null, lng: float|null, ...}>
     */
    private function orderReturnDropoffs(
        array $homes,
        array $school,
        ?TransportRouteTemplate $outboundTemplate,
    ): array {
        if (count($homes) <= 1) {
            return $homes;
        }

        if ($outboundTemplate) {
            $outboundOrder = $outboundTemplate->stops
                ->where('stop_type', TransportRouteStop::STOP_TYPE_PICKUP)
                ->sortBy('sequence')
                ->pluck('transport_passenger_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if ($outboundOrder !== []) {
                $byId = [];
                foreach ($homes as $home) {
                    $byId[(int) ($home['transport_passenger_id'] ?? 0)] = $home;
                }
                $ordered = [];
                foreach (array_reverse($outboundOrder) as $passengerId) {
                    if (isset($byId[$passengerId])) {
                        $ordered[] = $byId[$passengerId];
                        unset($byId[$passengerId]);
                    }
                }
                foreach ($byId as $home) {
                    $ordered[] = $home;
                }
                if ($ordered !== []) {
                    return $ordered;
                }
            }
        }

        // Nearest-neighbour vanaf school.
        $remaining = $homes;
        $ordered = [];
        $lat = $school['lat'];
        $lng = $school['lng'];
        while ($remaining !== []) {
            $nearestIndex = $this->indexOfNearestStop($remaining, $lat, $lng);
            $next = $remaining[$nearestIndex];
            unset($remaining[$nearestIndex]);
            $remaining = array_values($remaining);
            $ordered[] = $next;
            if ($next['lat'] !== null && $next['lng'] !== null) {
                $lat = $next['lat'];
                $lng = $next['lng'];
            }
        }

        return $ordered;
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

    private function addSecondsToTime(string $time, int $seconds): string
    {
        $parsed = Carbon::createFromFormat('H:i:s', $this->normalizeTime($time));

        return $parsed->addSeconds($seconds)->format('H:i:s');
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

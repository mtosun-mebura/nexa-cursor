<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Bestemming van alle passagiers op een contractrit.
 * Automatisch pas nadat de chauffeur een paar minuten op dezelfde bestemming stilstaat.
 */
class ContractDestinationArrivalService
{
    public const RADIUS_METERS = 250;

    public const DWELL_MINUTES = 3;

    /** Locatie ouder dan dit is niet meer “live” voor de contractouder. */
    public const POSITION_FRESH_MINUTES = 3;

    /**
     * Laatste gps van de contractant, zodat de ouder de taxi kan volgen.
     */
    public function forgetProgress(string $conn, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        Cache::forget($this->dwellKey($conn, $userId));
        Cache::forget($this->positionKey($conn, $userId));
    }

    /**
     * Live positie van de ritsimulator, zodat de contractouder de taxi ziet rijden.
     */
    public function rememberCustomerSimPosition(string $conn, int $customerId, float $lat, float $lng): void
    {
        if ($customerId <= 0) {
            return;
        }

        $now = Carbon::now(ContractTransportTimezone::TIMEZONE);
        Cache::put($this->customerSimKey($conn, $customerId), [
            'lat' => $lat,
            'lng' => $lng,
            'at' => $now->toIso8601String(),
        ], $now->copy()->addMinutes(15));
    }

    /**
     * @return array{lat: float, lng: float, updated_at: string}|null
     */
    public function freshCustomerSimPosition(string $conn, int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        return $this->freshCachedPosition($this->customerSimKey($conn, $customerId));
    }

    public function forgetCustomerSimPosition(string $conn, int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }

        Cache::forget($this->customerSimKey($conn, $customerId));
    }

    public function rememberDriverPosition(string $conn, int $userId, float $lat, float $lng): void
    {
        if ($userId <= 0) {
            return;
        }

        $now = Carbon::now(ContractTransportTimezone::TIMEZONE);
        Cache::put($this->positionKey($conn, $userId), [
            'lat' => $lat,
            'lng' => $lng,
            'at' => $now->toIso8601String(),
        ], $now->copy()->addMinutes(15));
    }

    /**
     * @return array{lat: float, lng: float, updated_at: string}|null
     */
    public function freshDriverPosition(string $conn, int $driverId): ?array
    {
        if ($driverId <= 0) {
            return null;
        }

        return $this->freshCachedPosition($this->positionKey($conn, $driverId));
    }

    /**
     * @return array{lat: float, lng: float, updated_at: string}|null
     */
    private function freshCachedPosition(string $key): ?array
    {
        $state = Cache::get($key);
        if (! is_array($state) || ! isset($state['lat'], $state['lng'], $state['at'])) {
            return null;
        }

        $at = Carbon::parse((string) $state['at'])->timezone(ContractTransportTimezone::TIMEZONE);
        if ($at->lt(Carbon::now(ContractTransportTimezone::TIMEZONE)->subMinutes(self::POSITION_FRESH_MINUTES))) {
            return null;
        }

        return [
            'lat' => (float) $state['lat'],
            'lng' => (float) $state['lng'],
            'updated_at' => $at->toIso8601String(),
        ];
    }

    /**
     * @param  list<int>  $passengerIds
     * @return int Aantal bestemmingsstops dat op afgerond is gezet
     */
    public function observe(string $conn, int $userId, array $passengerIds, float $lat, float $lng): int
    {
        $groups = $this->openDestinationGroups($conn, $passengerIds);
        $match = $this->closestGroup($groups, $lat, $lng);
        $cacheKey = $this->dwellKey($conn, $userId);

        if ($match === null) {
            Cache::forget($cacheKey);

            return 0;
        }

        $now = Carbon::now(ContractTransportTimezone::TIMEZONE);
        $state = Cache::get($cacheKey);
        $since = is_array($state) && ($state['key'] ?? '') === $match['key']
            ? Carbon::parse((string) $state['since'])->timezone(ContractTransportTimezone::TIMEZONE)
            : null;

        if ($since === null) {
            Cache::put($cacheKey, [
                'key' => $match['key'],
                'since' => $now->toIso8601String(),
            ], $now->copy()->addHours(8));

            return 0;
        }

        if ($since->gt($now->copy()->subMinutes(self::DWELL_MINUTES))) {
            return 0;
        }

        $updated = $this->completeGroup($conn, $match);
        Cache::forget($cacheKey);

        return $updated;
    }

    /**
     * Handmatig, alleen als de chauffeur bij een open bestemming staat.
     * Zonder locatie of te ver van elke bestemming wordt er niets gezet.
     *
     * @param  list<int>  $passengerIds
     * @return array{updated: int, reason: 'none'|'no_location'|'too_far'|null}
     */
    public function markAll(string $conn, array $passengerIds, ?float $lat = null, ?float $lng = null): array
    {
        $groups = $this->openDestinationGroups($conn, $passengerIds);
        if ($groups === []) {
            return ['updated' => 0, 'reason' => 'none'];
        }

        if ($lat === null || $lng === null) {
            return ['updated' => 0, 'reason' => 'no_location'];
        }

        $match = $this->closestGroup($groups, $lat, $lng);
        if ($match === null) {
            return ['updated' => 0, 'reason' => 'too_far'];
        }

        return [
            'updated' => $this->completeGroup($conn, $match),
            'reason' => null,
        ];
    }

    /**
     * @param  list<int>  $passengerIds
     * @return list<array{key: string, lat: float, lng: float, stop_ids: list<int>}>
     */
    private function openDestinationGroups(string $conn, array $passengerIds): array
    {
        $passengerIds = array_values(array_unique(array_filter(array_map('intval', $passengerIds))));
        if ($passengerIds === []) {
            return [];
        }

        $tz = ContractTransportTimezone::TIMEZONE;
        $wallStart = Carbon::now($tz)->startOfDay();
        $start = ContractTransportTimezone::naiveUtcForWallClockQuery($wallStart);
        $end = ContractTransportTimezone::naiveUtcForWallClockQuery($wallStart->copy()->endOfDay());

        $pickupRideIds = RideStop::on($conn)
            ->whereIn('transport_passenger_id', $passengerIds)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->whereBetween('planned_at', [$start, $end])
            ->pluck('ride_request_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($pickupRideIds === []) {
            return [];
        }

        $rideIds = RideRequest::on($conn)
            ->whereIn('id', $pickupRideIds)
            ->whereIn('ride_type', [
                RideRequest::RIDE_TYPE_CONTRACT_GROUP,
                RideRequest::RIDE_TYPE_CONTRACT_INDIVIDUAL,
            ])
            ->whereIn('status', [
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_ASSIGNED,
            ])
            ->pluck('id')
            ->all();

        if ($rideIds === []) {
            return [];
        }

        $skippedPassengerIds = RideStop::on($conn)
            ->whereIn('ride_request_id', $rideIds)
            ->where('stop_type', RideStop::STOP_TYPE_PICKUP)
            ->where('status', RideStop::STATUS_SKIPPED)
            ->pluck('transport_passenger_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $stops = RideStop::on($conn)
            ->whereIn('ride_request_id', $rideIds)
            ->where('stop_type', RideStop::STOP_TYPE_DESTINATION)
            ->whereNotIn('status', [RideStop::STATUS_COMPLETED])
            ->get();

        $groups = [];
        foreach ($stops as $stop) {
            $passengerId = (int) ($stop->transport_passenger_id ?? 0);
            if ($passengerId > 0 && in_array($passengerId, $skippedPassengerIds, true)) {
                continue;
            }
            $stopLat = $stop->lat !== null ? (float) $stop->lat : null;
            $stopLng = $stop->lng !== null ? (float) $stop->lng : null;
            $key = ($stopLat !== null && $stopLng !== null)
                ? sprintf('%.4f,%.4f', $stopLat, $stopLng)
                : 'ride:'.(int) $stop->ride_request_id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'key' => $key,
                    'lat' => $stopLat ?? 0.0,
                    'lng' => $stopLng ?? 0.0,
                    'has_coords' => $stopLat !== null && $stopLng !== null,
                    'stop_ids' => [],
                ];
            }
            $groups[$key]['stop_ids'][] = (int) $stop->id;
        }

        return array_values($groups);
    }

    /**
     * @param  list<array{key: string, lat: float, lng: float, has_coords?: bool, stop_ids: list<int>}>  $groups
     * @return array{key: string, lat: float, lng: float, has_coords?: bool, stop_ids: list<int>}|null
     */
    private function closestGroup(array $groups, float $lat, float $lng): ?array
    {
        $best = null;
        $bestMeters = self::RADIUS_METERS;
        foreach ($groups as $group) {
            if (empty($group['has_coords'])) {
                continue;
            }
            $meters = $this->distanceMeters($lat, $lng, (float) $group['lat'], (float) $group['lng']);
            if ($meters <= $bestMeters) {
                $best = $group;
                $bestMeters = $meters;
            }
        }

        return $best;
    }

    /**
     * @param  array{stop_ids: list<int>}  $group
     */
    private function completeGroup(string $conn, array $group): int
    {
        $ids = array_values(array_filter(array_map('intval', $group['stop_ids'] ?? [])));
        if ($ids === []) {
            return 0;
        }

        return RideStop::on($conn)
            ->whereIn('id', $ids)
            ->where('stop_type', RideStop::STOP_TYPE_DESTINATION)
            ->whereNotIn('status', [RideStop::STATUS_COMPLETED])
            ->update([
                'status' => RideStop::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
    }

    private function dwellKey(string $conn, int $userId): string
    {
        return 'contract-dest-dwell:'.$conn.':'.$userId;
    }

    private function positionKey(string $conn, int $userId): string
    {
        return 'contract-vehicle-loc:'.$conn.':'.$userId;
    }

    private function customerSimKey(string $conn, int $customerId): string
    {
        return 'contract-sim-loc:'.$conn.':'.$customerId;
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * 6371000 * asin(min(1.0, sqrt($a)));
    }
}

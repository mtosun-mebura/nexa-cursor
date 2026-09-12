<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideGpsPoint;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Support\TaxiRideTrackSchema;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class RideTrackService
{
    public const MIN_POINT_METERS = 12.0;

    public const MAX_STORED_POINTS = 1500;

    public function __construct(
        protected TaxiGpsRoadPathService $roadPath
    ) {}

    public function ensureReady(string $connection): void
    {
        TaxiRideTrackSchema::ensure($connection);
    }

    public function markTripStarted(string $connection, RideRequest $ride): void
    {
        $this->ensureReady($connection);
        if (! Schema::connection($connection)->hasColumn('ride_requests', 'trip_started_at')) {
            return;
        }
        if ($ride->trip_started_at) {
            return;
        }
        $ride->trip_started_at = now();
        $ride->save();
    }

    public function appendPoint(
        string $connection,
        int $companyId,
        int $driverId,
        float $lat,
        float $lng,
        ?int $rideId = null
    ): void {
        if (! $this->isValidCoord($lat, $lng)) {
            return;
        }

        $this->ensureReady($connection);
        $ride = $rideId
            ? RideRequest::on($connection)->whereKey($rideId)->first()
            : $this->activeRideForDriver($connection, $driverId);
        if (! $ride || (int) $ride->driver_id !== $driverId) {
            return;
        }
        if ($ride->status !== RideRequest::STATUS_ASSIGNED) {
            return;
        }

        $last = RideGpsPoint::on($connection)
            ->where('ride_request_id', $ride->id)
            ->orderByDesc('id')
            ->first();
        if ($last && $this->haversineMeters((float) $last->lat, (float) $last->lng, $lat, $lng) < self::MIN_POINT_METERS) {
            return;
        }

        RideGpsPoint::on($connection)->create([
            'company_id' => (int) ($ride->company_id ?: $companyId),
            'ride_request_id' => $ride->id,
            'driver_id' => $driverId,
            'lat' => round($lat, 7),
            'lng' => round($lng, 7),
            'recorded_at' => now(),
        ]);

        $count = RideGpsPoint::on($connection)->where('ride_request_id', $ride->id)->count();
        if ($count > self::MAX_STORED_POINTS + 80) {
            $this->trimOldestPoints($connection, (int) $ride->id);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $clientPoints
     */
    public function finalizeRide(string $connection, RideRequest $ride, array $clientPoints = []): RideRequest
    {
        $this->ensureReady($connection);
        $this->ingestClientPoints($connection, $ride, $clientPoints);

        $points = RideGpsPoint::on($connection)
            ->where('ride_request_id', $ride->id)
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();

        $coords = [];
        foreach ($points as $point) {
            $coords[] = [(float) $point->lat, (float) $point->lng];
        }
        $coords = $this->withEndpoints($ride, $coords);

        $sampled = $this->downsample($coords);
        if (count($sampled) >= 2) {
            $sampled = $this->downsample($this->roadPath->snapDrivenPath($sampled, $this->canSnapRemotely()));
        }
        $distance = $this->pathDistanceMeters($sampled);
        $startedAt = $ride->trip_started_at ?: ($points->first()?->recorded_at);
        $completedAt = now();
        $duration = 0;
        if ($startedAt) {
            $duration = max(0, (int) Carbon::parse($startedAt)->diffInSeconds($completedAt, false));
        }

        $updates = [];
        if (Schema::connection($connection)->hasColumn('ride_requests', 'trip_completed_at')) {
            $updates['trip_completed_at'] = $completedAt;
        }
        if (Schema::connection($connection)->hasColumn('ride_requests', 'track_polyline') && count($sampled) >= 2) {
            $updates['track_polyline'] = $this->roadPath->encodePolyline($sampled);
        }
        if (Schema::connection($connection)->hasColumn('ride_requests', 'actual_distance_meters') && $distance > 0) {
            $updates['actual_distance_meters'] = (int) round($distance);
        }
        if (Schema::connection($connection)->hasColumn('ride_requests', 'actual_duration_seconds') && $duration > 0) {
            $updates['actual_duration_seconds'] = $duration;
        }
        if ($updates !== []) {
            $ride->update($updates);
        }

        RideGpsPoint::on($connection)->where('ride_request_id', $ride->id)->delete();

        return $ride->fresh();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function completedTracksForMap(string $connection, int $companyId, int $hours = 36, int $limit = 40): array
    {
        $this->ensureReady($connection);
        if (! Schema::connection($connection)->hasColumn('ride_requests', 'track_polyline')) {
            return [];
        }

        $since = now()->subHours(max(1, $hours));
        $rides = RideRequest::on($connection)
            ->where('company_id', $companyId)
            ->where('status', RideRequest::STATUS_COMPLETED)
            ->where(function ($q) use ($since) {
                $q->where('trip_completed_at', '>=', $since)
                    ->orWhere(function ($q2) use ($since) {
                        $q2->whereNull('trip_completed_at')->where('updated_at', '>=', $since);
                    });
            })
            ->orderByDesc('trip_completed_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $driverIds = $rides->pluck('driver_id')->filter()->unique()->values();
        $drivers = User::query()
            ->whereIn('id', $driverIds)
            ->get(['id', 'first_name', 'last_name'])
            ->keyBy('id');

        $out = [];
        foreach ($rides as $ride) {
            $payload = $this->mapPayload($connection, $ride, $drivers->get((int) $ride->driver_id));
            if (($payload['path'] ?? []) === [] && $payload['pickup'] === null) {
                continue;
            }
            $out[] = $payload;
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapPayload(string $connection, RideRequest $ride, ?User $driver = null): array
    {
        $this->ensureReady($connection);
        $path = [];
        $coords = [];
        $polyline = (string) ($ride->track_polyline ?? '');
        if ($polyline !== '') {
            $coords = $this->roadPath->decodePolyline($polyline);
        }
        if ($coords === []) {
            $coords = $this->fallbackRouteCoords($ride);
        }
        if (count($coords) >= 2 && $this->roadPath->pathLooksStraight($coords)) {
            $coords = $this->roadPath->snapDrivenPath($coords, $this->canSnapRemotely());
        }
        foreach ($coords as $point) {
            $path[] = ['lat' => $point[0], 'lng' => $point[1]];
        }

        $driver ??= $ride->driver_id ? User::query()->find((int) $ride->driver_id) : null;
        $driverName = $driver
            ? trim(($driver->first_name ?? '').' '.($driver->last_name ?? ''))
            : '';

        $actualMeters = $ride->actual_distance_meters !== null ? (int) $ride->actual_distance_meters : null;
        $plannedMeters = $ride->distance_meters !== null ? (int) $ride->distance_meters : null;
        $actualSeconds = $ride->actual_duration_seconds !== null ? (int) $ride->actual_duration_seconds : null;
        $plannedSeconds = $ride->duration_seconds !== null ? (int) $ride->duration_seconds : null;

        return [
            'id' => (int) $ride->id,
            'status' => (string) $ride->status,
            'driver_name' => $driverName !== '' ? $driverName : null,
            'customer_name' => trim((string) ($ride->customer_name ?? '')) ?: null,
            'pickup_address' => (string) $ride->pickup_address,
            'dropoff_address' => (string) $ride->dropoff_address,
            'pickup' => $this->coordPair($ride->pickup_lat, $ride->pickup_lng),
            'dropoff' => $this->coordPair($ride->dropoff_lat, $ride->dropoff_lng),
            'path' => $path,
            'has_recorded_track' => $polyline !== '',
            'distance_meters' => $actualMeters ?: $plannedMeters,
            'duration_seconds' => $actualSeconds ?: $plannedSeconds,
            'planned_distance_meters' => $plannedMeters,
            'planned_duration_seconds' => $plannedSeconds,
            'actual_distance_meters' => $actualMeters,
            'actual_duration_seconds' => $actualSeconds,
            'distance_label' => $this->formatKm($actualMeters ?: $plannedMeters),
            'duration_label' => $this->formatDuration($actualSeconds ?: $plannedSeconds),
            'planned_distance_label' => $this->formatKm($plannedMeters),
            'planned_duration_label' => $this->formatDuration($plannedSeconds),
            'started_at' => $ride->trip_started_at?->format('H:i'),
            'completed_at' => $ride->trip_completed_at?->format('H:i'),
            'detail_url' => $this->rideShowUrl((int) $ride->id),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $clientPoints
     */
    public function bufferClientTrack(string $connection, RideRequest $ride, array $clientPoints): void
    {
        $this->ensureReady($connection);
        $this->ingestClientPoints($connection, $ride, $clientPoints);
    }

    /**
     * @param  list<array<string, mixed>>  $clientPoints
     */
    private function ingestClientPoints(string $connection, RideRequest $ride, array $clientPoints): void
    {
        if ($clientPoints === [] || ! TaxiRideTrackSchema::pointsTableExists($connection)) {
            return;
        }

        $last = RideGpsPoint::on($connection)
            ->where('ride_request_id', $ride->id)
            ->orderByDesc('id')
            ->first();
        $lastLat = $last ? (float) $last->lat : null;
        $lastLng = $last ? (float) $last->lng : null;

        foreach ($clientPoints as $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $lat = isset($raw['lat']) ? (float) $raw['lat'] : null;
            $lng = isset($raw['lng']) ? (float) $raw['lng'] : null;
            if (! $this->isValidCoord($lat, $lng)) {
                continue;
            }
            if ($lastLat !== null && $this->haversineMeters($lastLat, $lastLng, $lat, $lng) < self::MIN_POINT_METERS) {
                continue;
            }
            $recorded = now();
            if (isset($raw['t']) && is_numeric($raw['t'])) {
                $ms = (int) $raw['t'];
                if ($ms > 1_000_000_000_000) {
                    $recorded = Carbon::createFromTimestampMs($ms);
                } elseif ($ms > 1_000_000_000) {
                    $recorded = Carbon::createFromTimestamp($ms);
                }
            }
            RideGpsPoint::on($connection)->create([
                'company_id' => (int) $ride->company_id,
                'ride_request_id' => $ride->id,
                'driver_id' => $ride->driver_id,
                'lat' => round($lat, 7),
                'lng' => round($lng, 7),
                'recorded_at' => $recorded,
            ]);
            $lastLat = $lat;
            $lastLng = $lng;
        }
    }

    private function activeRideForDriver(string $connection, int $driverId): ?RideRequest
    {
        return RideRequest::on($connection)
            ->where('driver_id', $driverId)
            ->where('status', RideRequest::STATUS_ASSIGNED)
            ->orderByDesc('id')
            ->first();
    }

    private function canSnapRemotely(): bool
    {
        return ! app()->runningUnitTests();
    }

    /**
     * @return list<array{0: float, 1: float}>
     */
    private function fallbackRouteCoords(RideRequest $ride): array
    {
        $coords = [];
        $pickup = $this->coordPair($ride->pickup_lat, $ride->pickup_lng);
        $dropoff = $this->coordPair($ride->dropoff_lat, $ride->dropoff_lng);
        if ($pickup) {
            $coords[] = [$pickup['lat'], $pickup['lng']];
        }
        if ($dropoff) {
            $coords[] = [$dropoff['lat'], $dropoff['lng']];
        }

        return $coords;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     * @return list<array{0: float, 1: float}>
     */
    private function downsample(array $points): array
    {
        $count = count($points);
        if ($count <= self::MAX_STORED_POINTS) {
            return $points;
        }
        $step = $count / self::MAX_STORED_POINTS;
        $out = [];
        for ($i = 0; $i < $count; $i += $step) {
            $out[] = $points[(int) floor($i)];
        }
        $last = $points[$count - 1];
        if ($out === [] || $out[count($out) - 1] !== $last) {
            $out[] = $last;
        }

        return $out;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $points
     */
    private function pathDistanceMeters(array $points): float
    {
        $sum = 0.0;
        for ($i = 1, $n = count($points); $i < $n; $i++) {
            $sum += $this->haversineMeters($points[$i - 1][0], $points[$i - 1][1], $points[$i][0], $points[$i][1]);
        }

        return $sum;
    }

    /**
     * @param  list<array{0: float, 1: float}>  $coords
     * @return list<array{0: float, 1: float}>
     */
    private function withEndpoints(RideRequest $ride, array $coords): array
    {
        $pickup = $this->coordPair($ride->pickup_lat, $ride->pickup_lng);
        $dropoff = $this->coordPair($ride->dropoff_lat, $ride->dropoff_lng);
        if ($pickup) {
            $start = [$pickup['lat'], $pickup['lng']];
            if ($coords === [] || $this->haversineMeters($start[0], $start[1], $coords[0][0], $coords[0][1]) >= self::MIN_POINT_METERS) {
                array_unshift($coords, $start);
            }
        }
        if ($dropoff) {
            $end = [$dropoff['lat'], $dropoff['lng']];
            $last = $coords !== [] ? $coords[count($coords) - 1] : null;
            if ($last === null || $this->haversineMeters($last[0], $last[1], $end[0], $end[1]) >= self::MIN_POINT_METERS) {
                $coords[] = $end;
            }
        }

        return $coords;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    private function isValidCoord(?float $lat, ?float $lng): bool
    {
        return $lat !== null && $lng !== null
            && $lat >= -90 && $lat <= 90
            && $lng >= -180 && $lng <= 180
            && ! ($lat == 0.0 && $lng == 0.0);
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function coordPair(mixed $lat, mixed $lng): ?array
    {
        if ($lat === null || $lng === null) {
            return null;
        }
        $latF = (float) $lat;
        $lngF = (float) $lng;
        if (! $this->isValidCoord($latF, $lngF)) {
            return null;
        }

        return ['lat' => $latF, 'lng' => $lngF];
    }

    private function formatKm(?int $meters): ?string
    {
        if ($meters === null || $meters <= 0) {
            return null;
        }

        return number_format($meters / 1000, 1, ',', '.').' km';
    }

    private function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null || $seconds <= 0) {
            return null;
        }
        $minutes = (int) round($seconds / 60);
        if ($minutes < 60) {
            return $minutes.' min';
        }
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        return $rest > 0 ? $hours.' u '.$rest.' min' : $hours.' u';
    }

    private function trimOldestPoints(string $connection, int $rideId): void
    {
        $keepIds = RideGpsPoint::on($connection)
            ->where('ride_request_id', $rideId)
            ->orderByDesc('id')
            ->limit(self::MAX_STORED_POINTS)
            ->pluck('id');
        RideGpsPoint::on($connection)
            ->where('ride_request_id', $rideId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    private function rideShowUrl(int $rideId): ?string
    {
        try {
            return route('admin.taxi.ride_requests.show', $rideId);
        } catch (\Throwable) {
            return null;
        }
    }
}

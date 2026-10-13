<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Marktplaats: Uber-achtige ophaal-PIN.
 * Klant ziet de code; chauffeur voert die in; pas dan wordt de rit actief.
 */
class MarketplacePickupConfirmationService
{
    public const PICKUP_RADIUS_METERS = 250;

    public const DROPOFF_RADIUS_METERS = 400;

    public const GPS_MAX_AGE_SECONDS = 180;

    public const DRIVER_DENIED_ALERT_PREFIX = 'taxi_driver_pickup_denied_alert:';

    /**
     * Zorg dat er een 4-cijferige PIN op de rit staat (zichtbaar voor de klant).
     */
    public function ensurePin(string $conn, RideRequest $ride): RideRequest
    {
        $ride = $ride->fresh() ?? $ride;
        if (! $this->appliesTo($ride) || ! $this->hasPayloadColumn($conn)) {
            return $ride;
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $pin = preg_replace('/\D+/', '', (string) data_get($payload, 'pickup_confirmation.pin', ''));
        $status = (string) data_get($payload, 'pickup_confirmation.status', '');

        if (strlen($pin) === 4 && in_array($status, ['pending', 'confirmed'], true)) {
            return $ride;
        }

        if ($status === 'confirmed' && strlen($pin) === 4) {
            return $ride;
        }

        $payload['pickup_confirmation'] = [
            'method' => 'pin',
            'status' => $status === 'confirmed' ? 'confirmed' : 'pending',
            'pin' => $this->generatePin(),
            'created_at' => now()->toIso8601String(),
        ];
        $ride->booking_payload = $payload;
        $ride->save();

        return $ride->fresh() ?? $ride;
    }

    /**
     * Chauffeur start met PIN: controleer code, noteer GPS, start rit.
     *
     * @return array{ride: RideRequest, warning: ?string}
     */
    public function startWithPin(string $conn, RideRequest $ride, User $driver, string $pin): array
    {
        $ride = $this->ensurePin($conn, $ride);
        if (! $this->appliesTo($ride)) {
            throw ValidationException::withMessages([
                'pin' => ['Deze rit gebruikt geen ophaalcode.'],
            ]);
        }

        if ($ride->status !== RideRequest::STATUS_ACCEPTED) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet worden gestart.'],
            ]);
        }

        $expected = preg_replace('/\D+/', '', (string) data_get($ride->booking_payload, 'pickup_confirmation.pin', ''));
        $given = preg_replace('/\D+/', '', $pin);
        if (strlen($expected) !== 4 || $given !== $expected) {
            throw ValidationException::withMessages([
                'pin' => ['Ophaalcode onjuist. Vraag de code aan de klant en probeer opnieuw.'],
            ]);
        }

        $fix = $this->driverFix($conn, (int) $driver->id);
        $class = $this->classify(
            $fix['fresh'],
            $ride->pickup_lat !== null ? (float) $ride->pickup_lat : null,
            $ride->pickup_lng !== null ? (float) $ride->pickup_lng : null,
            $fix['lat'],
            $fix['lng'],
            self::PICKUP_RADIUS_METERS
        );

        $warning = match ($class['result']) {
            'away' => 'Je staat ongeveer '.(int) $class['distance_meters'].' m van het ophaalpunt. De rit start wel (code klopte).',
            'unknown' => 'Je locatie is niet bruikbaar. De rit start wel (code klopte).',
            default => null,
        };

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload['pickup_confirmation'] = [
            'method' => 'pin',
            'status' => 'confirmed',
            'pin' => $expected,
            'confirmed_at' => now()->toIso8601String(),
            'created_at' => data_get($payload, 'pickup_confirmation.created_at') ?: now()->toIso8601String(),
        ];
        $payload['pickup_location_check'] = [
            'result' => $class['result'],
            'distance_meters' => $class['distance_meters'],
            'warning' => $warning,
        ];
        if (! $ride->driver_id) {
            $ride->driver_id = (int) $driver->id;
        }
        $ride->booking_payload = $payload;
        $ride->save();

        $started = app(RideClaimService::class)->startRide($conn, $driver, (int) $ride->id);

        return ['ride' => $started->fresh() ?? $started, 'warning' => $warning];
    }

    public function noteDropoff(string $conn, RideRequest $ride, User $driver): RideRequest
    {
        $ride = $ride->fresh() ?? $ride;
        if (! $this->appliesTo($ride) || ! $this->hasPayloadColumn($conn)) {
            return $ride;
        }

        $fix = $this->driverFix($conn, (int) $driver->id);
        $class = $this->classify(
            $fix['fresh'],
            $ride->dropoff_lat !== null ? (float) $ride->dropoff_lat : null,
            $ride->dropoff_lng !== null ? (float) $ride->dropoff_lng : null,
            $fix['lat'],
            $fix['lng'],
            self::DROPOFF_RADIUS_METERS
        );
        $away = $class['result'] === 'away';
        $warning = $away
            ? 'Je staat ongeveer '.(int) $class['distance_meters'].' m van de bestemming. De rit is wel afgerond. De uitbetaling gaat naar controle.'
            : null;

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload['dropoff_location_check'] = [
            'away' => $away,
            'result' => $class['result'],
            'distance_meters' => $class['distance_meters'],
            'warning' => $warning,
        ];
        $ride->booking_payload = $payload;
        $ride->save();

        return $ride->fresh() ?? $ride;
    }

    /**
     * Legacy Ja/Nee-endpoint: niet meer de primaire flow.
     */
    public function answer(string $conn, RideRequest $ride, string $answer): RideRequest
    {
        throw ValidationException::withMessages([
            'ride' => ['Gebruik de ophaalcode. De chauffeur vraagt de code aan jou om de rit te starten.'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function customerPayload(RideRequest $ride): ?array
    {
        if (! $this->appliesTo($ride)) {
            return null;
        }

        $status = (string) data_get($ride->booking_payload, 'pickup_confirmation.status', '');
        $pin = preg_replace('/\D+/', '', (string) data_get($ride->booking_payload, 'pickup_confirmation.pin', ''));
        if (strlen($pin) !== 4) {
            return null;
        }

        $showPin = in_array($ride->status, [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
        ], true) && $status !== 'confirmed';

        // Na start mag de klant de code nog kort zien tot afronden (handig bij verwarring).
        if ($status === 'confirmed' && $ride->status === RideRequest::STATUS_ASSIGNED) {
            $showPin = true;
        }

        if (! $showPin && $status !== 'confirmed') {
            return null;
        }

        return [
            'method' => 'pin',
            'status' => $status ?: 'pending',
            'pin' => $pin,
            'prompt' => 'Geef deze ophaalcode aan de chauffeur om de rit te starten.',
            'can_answer' => false,
        ];
    }

    /**
     * Chauffeur ziet géén PIN — alleen of er een code nodig is.
     *
     * @return array{method: string, status: string, requires_pin: bool}|null
     */
    public function driverPayload(RideRequest $ride): ?array
    {
        if (! $this->appliesTo($ride)) {
            return null;
        }

        $status = (string) data_get($ride->booking_payload, 'pickup_confirmation.status', 'pending');
        if ($status === '') {
            $status = 'pending';
        }

        return [
            'method' => 'pin',
            'status' => $status,
            'requires_pin' => $ride->status === RideRequest::STATUS_ACCEPTED && $status !== 'confirmed',
            'can_reask' => false,
            'awaiting_customer' => false,
        ];
    }

    /**
     * @return array{result: string, distance_meters: ?int}
     */
    public function classify(
        bool $gpsFresh,
        ?float $targetLat,
        ?float $targetLng,
        ?float $driverLat,
        ?float $driverLng,
        int $radiusMeters
    ): array {
        if (! $gpsFresh || $targetLat === null || $targetLng === null || $driverLat === null || $driverLng === null) {
            return ['result' => 'unknown', 'distance_meters' => null];
        }

        $meters = (int) round($this->haversineMeters($driverLat, $driverLng, $targetLat, $targetLng));

        return [
            'result' => $meters <= $radiusMeters ? 'near' : 'away',
            'distance_meters' => $meters,
        ];
    }

    public function appliesTo(RideRequest $ride): bool
    {
        if ($ride->isContractRide()) {
            return false;
        }

        return $ride->isNexaSuiteBooking() || $ride->canStartWithoutPickupProposal();
    }

    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{lat: ?float, lng: ?float, fresh: bool}
     */
    private function driverFix(string $conn, int $driverId): array
    {
        $empty = ['lat' => null, 'lng' => null, 'fresh' => false];
        if ($driverId <= 0 || ! Schema::connection($conn)->hasTable('driver_availability')) {
            return $empty;
        }
        if (! Schema::connection($conn)->hasColumn('driver_availability', 'lat')) {
            return $empty;
        }

        $row = DriverAvailability::on($conn)->whereKey($driverId)->first();
        if ($row === null || $row->lat === null || $row->lng === null) {
            return $empty;
        }

        $updated = $row->location_updated_at;
        $fresh = $updated !== null && $updated->greaterThan(now()->subSeconds(self::GPS_MAX_AGE_SECONDS));

        return [
            'lat' => (float) $row->lat,
            'lng' => (float) $row->lng,
            'fresh' => $fresh,
        ];
    }

    private function hasPayloadColumn(string $conn): bool
    {
        return Schema::connection($conn)->hasColumn('ride_requests', 'booking_payload');
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * 6371000 * asin(min(1.0, sqrt($a)));
    }
}

<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Models\RideRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Marktplaats: de chauffeur mag altijd starten. GPS is een waarschuwing.
 * De klant bevestigt het ophalen; uitbetaling volgt die bevestiging plus de bestaande risico's.
 */
class MarketplacePickupConfirmationService
{
    public const PROMPT = 'De chauffeur zegt dat je bent opgehaald. Klopt dat?';

    public const PICKUP_RADIUS_METERS = 250;

    public const DROPOFF_RADIUS_METERS = 400;

    public const GPS_MAX_AGE_SECONDS = 180;

    /**
     * @return array{ride: RideRequest, warning: ?string}
     */
    public function noteStart(string $conn, RideRequest $ride, User $driver): array
    {
        $ride = $ride->fresh() ?? $ride;
        if (! $this->appliesTo($ride) || ! $this->hasPayloadColumn($conn)) {
            return ['ride' => $ride, 'warning' => null];
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
            'away' => 'Je staat ongeveer '.(int) $class['distance_meters'].' m van het ophaalpunt. De rit start wel. De klant moet bevestigen dat die is opgehaald. Zonder die bevestiging gaat de uitbetaling naar controle.',
            'unknown' => 'Je locatie is niet bruikbaar (ontbreekt of ouder dan 3 minuten). De rit start wel en wordt gemarkeerd als locatie onbekend. Zonder bevestiging van de klant gaat de uitbetaling naar controle.',
            default => null,
        };

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $existing = (string) data_get($payload, 'pickup_confirmation.status', '');
        if (! in_array($existing, ['confirmed', 'denied'], true)) {
            $payload['pickup_confirmation'] = [
                'status' => 'pending',
                'prompt' => self::PROMPT,
                'asked_at' => now()->toIso8601String(),
            ];
        }
        $payload['pickup_location_check'] = [
            'result' => $class['result'],
            'distance_meters' => $class['distance_meters'],
            'warning' => $warning,
        ];
        $ride->booking_payload = $payload;
        $ride->save();

        return ['ride' => $ride->fresh() ?? $ride, 'warning' => $warning];
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

    public function answer(string $conn, RideRequest $ride, string $answer): RideRequest
    {
        $ride = $ride->fresh() ?? $ride;
        if (! $this->appliesTo($ride)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit vraagt geen ophaalbevestiging.'],
            ]);
        }
        if (! $this->hasPayloadColumn($conn)) {
            throw ValidationException::withMessages([
                'ride' => ['Ophaalbevestiging is nu niet beschikbaar.'],
            ]);
        }

        $status = $answer === 'yes' ? 'confirmed' : 'denied';
        $settlement = (string) ($ride->settlement_status ?? '');
        if (in_array($settlement, [
            RideRequest::SETTLEMENT_SETTLED,
            RideRequest::SETTLEMENT_REJECTED,
        ], true)) {
            throw ValidationException::withMessages([
                'ride' => ['Deze rit kan niet meer bevestigd worden.'],
            ]);
        }

        $payload = is_array($ride->booking_payload) ? $ride->booking_payload : [];
        $payload['pickup_confirmation'] = [
            'status' => $status,
            'prompt' => self::PROMPT,
            'answered_at' => now()->toIso8601String(),
        ];
        $ride->booking_payload = $payload;
        $ride->save();
        $ride = $ride->fresh() ?? $ride;

        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            return $ride;
        }

        return app(RideSettlementEligibilityService::class)->evaluateAfterCompletion($conn, $ride);
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
        if (! in_array($status, ['pending', 'confirmed', 'denied'], true)) {
            return null;
        }

        $settlement = (string) ($ride->settlement_status ?? '');
        $closed = in_array($settlement, [
            RideRequest::SETTLEMENT_SETTLED,
            RideRequest::SETTLEMENT_REJECTED,
        ], true);
        $openRide = in_array($ride->status, [
            RideRequest::STATUS_ASSIGNED,
            RideRequest::STATUS_COMPLETED,
        ], true);

        return [
            'status' => $status,
            'prompt' => self::PROMPT,
            'can_answer' => $status === 'pending' && $openRide && ! $closed,
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

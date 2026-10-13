<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\TaxiRideRating;
use App\Modules\NexaTaxi\Support\TaxiDriverProfileSchema;
use Illuminate\Validation\ValidationException;

class TaxiRideRatingService
{
    public function __construct(
        protected TaxiDriverProfileMediaService $profiles
    ) {}

    public function findForRide(string $conn, int $rideId): ?TaxiRideRating
    {
        TaxiDriverProfileSchema::ensure($conn);

        return TaxiRideRating::on($conn)->where('ride_request_id', $rideId)->first();
    }

    /**
     * @return array{stars: int, comment: string|null, rated: bool}
     */
    public function payloadForRide(string $conn, RideRequest $ride): array
    {
        $row = $this->findForRide($conn, (int) $ride->id);
        if (! $row) {
            return [
                'rated' => false,
                'stars' => null,
                'comment' => null,
                'can_rate' => $ride->status === RideRequest::STATUS_COMPLETED && (int) ($ride->driver_id ?? 0) > 0,
            ];
        }

        return [
            'rated' => true,
            'stars' => (int) $row->stars,
            'comment' => $row->comment,
            'can_rate' => false,
        ];
    }

    /**
     * @return array{stars: int, comment: string|null, rated: bool, can_rate: bool}
     */
    public function rate(string $conn, RideRequest $ride, int $stars, ?string $comment): array
    {
        TaxiDriverProfileSchema::ensure($conn);

        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'stars' => ['Je kunt pas beoordelen nadat de rit is afgerond.'],
            ]);
        }

        $driverId = (int) ($ride->driver_id ?? 0);
        if ($driverId <= 0) {
            throw ValidationException::withMessages([
                'stars' => ['Geen chauffeur gekoppeld aan deze rit.'],
            ]);
        }

        if ($stars < 1 || $stars > 5) {
            throw ValidationException::withMessages([
                'stars' => ['Kies 1 tot 5 sterren.'],
            ]);
        }

        $existing = $this->findForRide($conn, (int) $ride->id);
        if ($existing) {
            throw ValidationException::withMessages([
                'stars' => ['Deze rit is al beoordeeld.'],
            ]);
        }

        $comment = $comment !== null ? trim($comment) : null;
        if ($comment === '') {
            $comment = null;
        }
        if ($comment !== null && mb_strlen($comment) > 1000) {
            throw ValidationException::withMessages([
                'comment' => ['Opmerking mag maximaal 1000 tekens zijn.'],
            ]);
        }

        $row = new TaxiRideRating([
            'ride_request_id' => (int) $ride->id,
            'driver_id' => $driverId,
            'stars' => $stars,
            'comment' => $comment,
        ]);
        $row->setConnection($conn);
        $row->save();

        $this->profiles->applyRating($driverId, $stars);

        return $this->payloadForRide($conn, $ride);
    }

    /**
     * @return list<array{id: int, stars: int, comment: ?string, created_at: ?string, pickup_address: ?string, dropoff_address: ?string, customer_name: ?string}>
     */
    public function listForDriver(string $conn, int $driverId): array
    {
        TaxiDriverProfileSchema::ensure($conn);
        if ($driverId <= 0) {
            return [];
        }

        $rows = TaxiRideRating::on($conn)
            ->where('driver_id', $driverId)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $rides = RideRequest::on($conn)
            ->whereIn('id', $rows->pluck('ride_request_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function (TaxiRideRating $row) use ($rides) {
            $ride = $rides->get($row->ride_request_id);

            return [
                'id' => (int) $row->id,
                'stars' => (int) $row->stars,
                'comment' => $row->comment !== null ? (string) $row->comment : null,
                'created_at' => $row->created_at?->toIso8601String(),
                'pickup_address' => $ride?->pickup_address,
                'dropoff_address' => $ride?->dropoff_address,
                'customer_name' => $ride?->customer_name,
            ];
        })->values()->all();
    }
}

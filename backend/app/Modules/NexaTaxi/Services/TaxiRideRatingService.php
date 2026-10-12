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
}

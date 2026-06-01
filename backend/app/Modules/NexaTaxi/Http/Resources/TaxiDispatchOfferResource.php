<?php

namespace App\Modules\NexaTaxi\Http\Resources;

use App\Modules\NexaTaxi\Models\RideDispatchOffer;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Http\Request;

class TaxiDispatchOfferResource
{
    public static function fromOffer(RideDispatchOffer $offer, ?RideRequest $ride = null): array
    {
        $ride ??= $offer->relationLoaded('rideRequest') ? $offer->rideRequest : null;

        $secondsRemaining = $offer->expires_at
            ? max(0, (int) now()->diffInSeconds($offer->expires_at, false))
            : 0;

        $waitingSinceAt = null;
        $secondsWaiting = 0;
        $isWaiting = false;
        if ($ride && ! $ride->driver_id) {
            $conn = $offer->getConnectionName();
            $waitingSinceAt = $ride->created_at;
            if ($waitingSinceAt) {
                $secondsWaiting = max(0, (int) $waitingSinceAt->diffInSeconds(now(), false));
            }
            $companyId = (int) ($ride->company_id ?: $offer->company_id);
            $offerTtlSeconds = app(TaxiDispatchSettingsService::class)->offerTtlSeconds($companyId);

            $hadNoResponse = RideDispatchOffer::on($conn)
                ->where('ride_request_id', $ride->id)
                ->whereIn('status', [
                    RideDispatchOffer::STATUS_EXPIRED,
                    RideDispatchOffer::STATUS_DECLINED,
                ])
                ->exists();

            // Blijft wachten zolang de rit openstaat en de acceptatietijd minstens één keer is verstreken
            // (ook na vernieuwd aanbod — anders verdwijnt "verlopen" door updateOrCreate).
            $isWaiting = $hadNoResponse
                || $secondsWaiting >= $offerTtlSeconds
                || $secondsRemaining <= 0;
        }

        $urgency = $isWaiting
            ? 'waiting'
            : ($secondsRemaining > 0 && $secondsRemaining <= 60 ? 'urgent' : 'normal');

        return [
            'id' => $offer->id,
            'status' => $offer->status,
            'expires_at' => $offer->expires_at?->toIso8601String(),
            'offered_at' => $offer->offered_at?->toIso8601String(),
            'seconds_remaining' => $secondsRemaining,
            'seconds_waiting' => $secondsWaiting,
            'waiting_since_at' => $waitingSinceAt?->toIso8601String(),
            'is_waiting' => $isWaiting,
            'urgency' => $urgency,
            'ride' => $ride ? self::rideSummary($ride) : null,
            'actions' => [
                'accept' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/accept"),
                'decline' => url("/api/taxi/v1/driver/dispatch/offers/{$offer->id}/decline"),
            ],
        ];
    }

    public static function rideSummary(RideRequest $ride): array
    {
        $payments = app(TaxiRidePaymentService::class);

        return [
            'id' => $ride->id,
            'status' => $ride->status,
            'created_at' => $ride->created_at?->toIso8601String(),
            'waiting_since_at' => $ride->created_at?->toIso8601String(),
            'pickup_address' => $ride->pickup_address,
            'dropoff_address' => $ride->dropoff_address,
            'pickup_at' => $ride->pickup_at?->toIso8601String(),
            'quoted_price' => $ride->quoted_price !== null ? (float) $ride->quoted_price : null,
            'passengers' => (int) $ride->passengers,
            'customer_name' => $ride->customer_name,
            'customer_phone' => $ride->customer_phone,
            'distance_km' => $ride->distance_meters ? round($ride->distance_meters / 1000, 1) : null,
            'payment' => $payments->paymentSummaryForRide($ride),
            'invoice' => app(TaxiRideInvoiceService::class)->driverInvoicePayload($ride),
            'actions' => [
                'start' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/start"),
                'release' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/release"),
                'complete' => url("/api/taxi/v1/driver/dispatch/rides/{$ride->id}/complete"),
            ],
        ];
    }
}

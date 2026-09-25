<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideCancellationService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TaxiBookingCancelController extends Controller
{
    public function show(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRideCancellationService $cancellation
    ): View {
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is ongeldig of verlopen.');
        }

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = RideRequest::on($conn)->findOrFail($ride);

        $canCancel = $cancellation->isCancellableByCustomer($rideModel);
        $alreadyCancelled = $rideModel->status === RideRequest::STATUS_CANCELLED;
        $pickupAt = $rideModel->pickup_at
            ? ContractTransportTimezone::asAmsterdamWall($rideModel->pickup_at)?->format('d-m-Y H:i')
            : '—';

        $confirmUrl = null;
        if ($canCancel) {
            $confirmUrl = URL::temporarySignedRoute(
                'nexataxi.booking.cancel.confirm',
                now()->addHours(2),
                ['ride' => (int) $rideModel->id]
            );
        }

        return view('taxi::booking.cancel', [
            'ride' => $rideModel,
            'canCancel' => $canCancel,
            'alreadyCancelled' => $alreadyCancelled,
            'pickupAt' => $pickupAt,
            'wasPaid' => in_array($rideModel->payment_status, [
                RideRequest::PAYMENT_STATUS_PAID,
                RideRequest::PAYMENT_STATUS_REFUNDED,
                RideRequest::PAYMENT_STATUS_REFUND_PENDING,
            ], true),
            'confirmUrl' => $confirmUrl,
        ]);
    }

    public function confirm(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRideCancellationService $cancellation
    ): View {
        if (! $request->hasValidSignature()) {
            abort(403, 'Deze link is ongeldig of verlopen.');
        }

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = RideRequest::on($conn)->findOrFail($ride);

        try {
            $result = $cancellation->cancelByCustomer($conn, $rideModel);
        } catch (ValidationException $e) {
            return view('taxi::booking.cancel-result', [
                'success' => false,
                'message' => collect($e->errors())->flatten()->first()
                    ?: 'Deze rit kan niet meer worden geannuleerd.',
                'ride' => $rideModel->fresh() ?? $rideModel,
                'refunded' => false,
            ]);
        }

        $message = 'Uw rit is geannuleerd.';
        if ($result['refunded']) {
            $message .= ' Het vooraf betaalde bedrag wordt teruggestort op de rekening waarmee u betaald heeft.';
        } elseif ($result['refund_error']) {
            $message .= ' De terugbetaling kon niet automatisch worden afgerond. Neem contact op met de taxi.';
        }

        return view('taxi::booking.cancel-result', [
            'success' => true,
            'message' => $message,
            'ride' => $result['ride'],
            'refunded' => $result['refunded'],
        ]);
    }
}

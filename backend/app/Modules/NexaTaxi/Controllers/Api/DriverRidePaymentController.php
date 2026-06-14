<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DriverRidePaymentController extends Controller
{
    public function show(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRidePaymentService $payments
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = $this->findDriverRide($conn, $request, $ride);

        $openPayment = RidePayment::on($conn)
            ->where('ride_request_id', $rideModel->id)
            ->whereIn('channel', [RidePayment::CHANNEL_DRIVER, RidePayment::CHANNEL_BOOKING])
            ->where('status', RidePayment::STATUS_OPEN)
            ->orderByDesc('id')
            ->first();

        if ($openPayment) {
            $openPayment = $payments->syncRidePaymentFromMollie($conn, $openPayment);
            $rideModel = $rideModel->fresh();
        }

        return response()->json([
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($rideModel),
                'payment' => $payments->paymentSummaryForRide($rideModel),
                'open_payment' => $openPayment ? $this->openPaymentPayload($openPayment, $payments) : null,
            ],
        ]);
    }

    public function store(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRidePaymentService $payments
    ): JsonResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
        ]);

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = $this->findDriverRide($conn, $request, $ride);

        if ($rideModel->payment_status === RideRequest::PAYMENT_STATUS_PAID) {
            throw ValidationException::withMessages([
                'payment' => ['Deze rit is al betaald.'],
            ]);
        }

        try {
            $result = $payments->createDriverPayment($conn, $rideModel, (float) $data['amount']);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::warning('Chauffeur-betaling aanmaken mislukt', [
                'ride_id' => $rideModel->id,
                'driver_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Betaling kon niet worden gestart. Probeer opnieuw of kies contant.',
            ], 422);
        }

        return response()->json([
            'message' => 'Betaalverzoek aangemaakt.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($rideModel->fresh()),
                'open_payment' => $this->openPaymentPayload($result['payment'], $payments),
            ],
        ]);
    }

    public function cash(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRidePaymentService $payments
    ): JsonResponse {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:99999.99'],
        ]);

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = $this->findDriverRide($conn, $request, $ride);

        $amount = isset($data['amount']) ? (float) $data['amount'] : null;

        try {
            $freshRide = $payments->markDriverCashPaid($conn, $rideModel, $amount);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'message' => 'Contante betaling geregistreerd.',
            'data' => [
                'ride' => TaxiDispatchOfferResource::rideSummary($freshRide),
            ],
        ]);
    }

    protected function findDriverRide(string $conn, Request $request, int $rideId): RideRequest
    {
        $ride = RideRequest::on($conn)
            ->whereKey($rideId)
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', [
                RideRequest::STATUS_ASSIGNED,
            ])
            ->first();

        if (! $ride) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        return $ride;
    }

    /**
     * @return array<string, mixed>
     */
    protected function openPaymentPayload(RidePayment $payment, TaxiRidePaymentService $payments): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status,
            'amount' => (float) $payment->amount,
            'checkout_url' => $payment->checkout_url,
            'qr_url' => $payment->checkout_url
                ? $payments->qrImageUrl($payment->checkout_url)
                : null,
        ];
    }
}

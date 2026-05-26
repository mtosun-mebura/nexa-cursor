<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Http\Resources\TaxiDispatchOfferResource;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverRideInvoiceController extends Controller
{
    public function show(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRideInvoiceService $invoices
    ): JsonResponse {
        $rideModel = $this->findDriverRide($moduleDb->getModuleConnectionName('taxi'), $request, $ride);

        return response()->json([
            'data' => $invoices->driverInvoicePayload($rideModel),
        ]);
    }

    public function send(
        Request $request,
        int $ride,
        ModuleDatabaseService $moduleDb,
        TaxiRideInvoiceService $invoices
    ): JsonResponse {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'invoice_number' => ['nullable', 'string', 'max:64'],
        ]);

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideModel = $this->findDriverRide($conn, $request, $ride);

        try {
            $invoice = $invoices->sendInvoiceToCustomer(
                $conn,
                $rideModel,
                $data['email'],
                $data['invoice_number'] ?? null
            );
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $freshRide = $rideModel->fresh();

        return response()->json([
            'message' => 'Factuur verstuurd naar '.$data['email'].'.',
            'data' => [
                'invoice' => $invoices->driverInvoicePayload($freshRide),
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
                RideRequest::STATUS_ACCEPTED,
                RideRequest::STATUS_COMPLETED,
            ])
            ->first();

        if (! $ride) {
            throw ValidationException::withMessages([
                'ride' => ['Rit niet gevonden.'],
            ]);
        }

        return $ride;
    }
}

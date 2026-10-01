<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\CustomerMarketplaceBookingService;
use App\Modules\NexaTaxi\Services\CustomerRideLiveStatusService;
use App\Modules\NexaTaxi\Services\TaxiCustomerRideCancelledMailer;
use App\Modules\NexaTaxi\Services\TaxiPortalDataService;
use App\Modules\NexaTaxi\Services\TaxiRideCancellationService;
use App\Modules\NexaTaxi\Services\TaxiRideInvoiceService;
use App\Services\InvoicePdfService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CustomerRideController extends Controller
{
    public function quote(Request $request, CustomerMarketplaceBookingService $booking): JsonResponse
    {
        $data = $request->validate([
            'distance_meters' => 'required|integer|min:0',
            'duration_seconds' => 'required|integer|min:0',
            'passengers' => 'required|integer|min:1|max:20',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'pickup_at' => 'nullable|date',
            'return_trip' => 'nullable|boolean',
            'marketplace_radius_km' => 'nullable|numeric|min:1|max:100',
            'baggage' => 'nullable|array',
            'baggage.*' => 'integer|min:0|max:20',
            'special_baggage' => 'nullable|array',
            'special_baggage.*' => 'integer|min:0|max:20',
        ]);

        try {
            $result = $booking->quote($data);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            ...$result,
        ]);
    }

    public function book(Request $request, CustomerMarketplaceBookingService $booking): JsonResponse
    {
        $data = $request->validate([
            'distance_meters' => 'required|integer|min:50',
            'duration_seconds' => 'required|integer|min:30',
            'passengers' => 'required|integer|min:1|max:20',
            'pickup_address' => 'required|string|max:500',
            'dropoff_address' => 'required|string|max:500',
            'pickup_at' => 'required|date',
            'pickup_lat' => 'required|numeric',
            'pickup_lng' => 'required|numeric',
            'dropoff_lat' => 'nullable|numeric',
            'dropoff_lng' => 'nullable|numeric',
            'remarks' => 'nullable|string|max:2000',
            'baggage' => 'nullable|array',
            'baggage.*' => 'integer|min:0|max:20',
            'special_baggage' => 'nullable|array',
            'special_baggage.*' => 'integer|min:0|max:20',
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:50',
            'selected_offer_id' => 'nullable|string|max:120',
            'payment_method' => 'nullable|string|in:booking',
            'return_trip' => 'nullable|boolean',
            'marketplace_radius_km' => 'nullable|numeric|min:1|max:100',
            'return_url' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        if ($user) {
            if (trim((string) ($data['email'] ?? '')) === '' && $user->email) {
                $data['email'] = $user->email;
            }
            if (trim((string) ($data['phone'] ?? '')) === '' && $user->phone) {
                $data['phone'] = $user->phone;
            }
            if (trim((string) ($data['first_name'] ?? '')) === '' && $user->first_name) {
                $data['first_name'] = $user->first_name;
            }
            if (trim((string) ($data['last_name'] ?? '')) === '' && $user->last_name) {
                $data['last_name'] = $user->last_name;
            }
        }

        try {
            $result = $booking->book($data, $user);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
                'errors' => $e->errors(),
            ], 422);
        }

        $ride = $result['ride'];
        $paymentRequired = ! empty($result['payment_required']);

        return response()->json([
            'success' => true,
            'message' => $paymentRequired
                ? 'Je wordt doorgestuurd naar de betaling.'
                : 'Je rit staat op de marktplaats. We zoeken een taxi in de buurt.',
            'ride_request_id' => (int) $ride->id,
            'track_token' => $result['track_token'],
            'offer' => $result['offer'],
            'marketplace' => $result['marketplace'],
            'payment_required' => $paymentRequired,
            'checkout_url' => $result['checkout_url'] ?? null,
            'payment' => $result['payment'] ?? null,
            'live' => app(CustomerRideLiveStatusService::class)->livePayload($ride),
        ], 201);
    }

    public function live(
        Request $request,
        CustomerRideLiveStatusService $live,
        TaxiRideCancellationService $cancellation,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $data = $request->validate([
            'token' => 'required|string|min:24|max:64',
        ]);

        $ride = $live->findByTrackToken($data['token']);
        if (! $ride) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        try {
            $conn = $moduleDb->getModuleConnectionName('taxi');
            $ride = $live->syncPendingBookingPayment($ride);
            $ride = $cancellation->rememberDecisionPrompt($conn, $ride);
            $result = $cancellation->cancelIfDue($conn, $ride);
            if ($result) {
                $ride = $result['ride'];
            }
        } catch (\Throwable) {
            // Live-status blijft beschikbaar als auto-annuleren mislukt.
        }

        return response()->json([
            'success' => true,
            'ride' => $live->livePayload($ride),
        ]);
    }

    public function cancelByToken(
        Request $request,
        CustomerRideLiveStatusService $live,
        TaxiRideCancellationService $cancellation,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $data = $request->validate([
            'token' => 'required|string|min:24|max:64',
        ]);

        $ride = $live->findByTrackToken($data['token']);
        if (! $ride) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->cancelRideResponse($moduleDb->getModuleConnectionName('taxi'), $ride, $cancellation, $live);
    }

    public function waitByToken(
        Request $request,
        CustomerRideLiveStatusService $live,
        TaxiRideCancellationService $cancellation,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $data = $request->validate([
            'token' => 'required|string|min:24|max:64',
        ]);

        $ride = $live->findByTrackToken($data['token']);
        if (! $ride) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->waitRideResponse($moduleDb->getModuleConnectionName('taxi'), $ride, $cancellation, $live);
    }

    public function wait(
        Request $request,
        int $ride,
        TaxiPortalDataService $portal,
        TaxiRideCancellationService $cancellation,
        CustomerRideLiveStatusService $live,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $model = RideRequest::on($conn)->find($ride);
        if (! $model || ! $portal->customerOwnsRide($request->user(), $model)) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->waitRideResponse($conn, $model, $cancellation, $live);
    }

    public function payByToken(
        Request $request,
        CustomerRideLiveStatusService $live,
        CustomerMarketplaceBookingService $booking
    ): JsonResponse {
        $data = $request->validate([
            'token' => 'required|string|min:24|max:64',
            'return_url' => 'nullable|string|max:2000',
        ]);

        $ride = $live->findByTrackToken($data['token']);
        if (! $ride) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->payRideResponse($ride, $booking, $live, $data['return_url'] ?? null);
    }

    public function pay(
        Request $request,
        int $ride,
        TaxiPortalDataService $portal,
        CustomerMarketplaceBookingService $booking,
        CustomerRideLiveStatusService $live,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $model = RideRequest::on($conn)->find($ride);
        if (! $model || ! $portal->customerOwnsRide($request->user(), $model)) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        $data = $request->validate([
            'return_url' => 'nullable|string|max:2000',
        ]);

        return $this->payRideResponse($model, $booking, $live, $data['return_url'] ?? null);
    }

    public function cancel(
        Request $request,
        int $ride,
        TaxiPortalDataService $portal,
        TaxiRideCancellationService $cancellation,
        CustomerRideLiveStatusService $live,
        ModuleDatabaseService $moduleDb
    ): JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $model = RideRequest::on($conn)->find($ride);
        if (! $model || ! $portal->customerOwnsRide($request->user(), $model)) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->cancelRideResponse($conn, $model, $cancellation, $live);
    }

    public function invoiceByToken(
        Request $request,
        CustomerRideLiveStatusService $live,
        TaxiRideInvoiceService $invoices,
        InvoicePdfService $pdf,
        ModuleDatabaseService $moduleDb
    ): Response|JsonResponse {
        $data = $request->validate([
            'token' => 'required|string|min:24|max:64',
        ]);

        $ride = $live->findByTrackToken($data['token']);
        if (! $ride) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->invoicePdfResponse($moduleDb->getModuleConnectionName('taxi'), $ride, $invoices, $pdf);
    }

    public function invoice(
        Request $request,
        int $ride,
        TaxiPortalDataService $portal,
        TaxiRideInvoiceService $invoices,
        InvoicePdfService $pdf,
        ModuleDatabaseService $moduleDb
    ): Response|JsonResponse {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $model = RideRequest::on($conn)->find($ride);
        if (! $model || ! $portal->customerOwnsRide($request->user(), $model)) {
            return response()->json(['message' => 'Rit niet gevonden.'], 404);
        }

        return $this->invoicePdfResponse($conn, $model, $invoices, $pdf);
    }

    public function index(Request $request, TaxiPortalDataService $portal): JsonResponse
    {
        return response()->json([
            'rides' => $portal->listRidesForCustomer($request->user()),
        ]);
    }

    public function show(Request $request, int $ride, TaxiPortalDataService $portal, CustomerRideLiveStatusService $live): JsonResponse
    {
        try {
            $detail = $portal->rideDetailPayload($request->user(), $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Rit niet gevonden.',
            ], 404);
        }

        $conn = app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
        $model = RideRequest::on($conn)->find($ride);
        $livePayload = $model ? $live->livePayload($model) : null;

        $trackToken = null;
        if ($model && Schema::connection($conn)->hasColumn('ride_requests', 'customer_track_token')) {
            $trackToken = $model->customer_track_token;
            if (! $trackToken) {
                $trackToken = $live->issueTrackToken($model);
            }
        }

        return response()->json([
            'ride' => $detail,
            'live' => $livePayload,
            'track_token' => $trackToken,
        ]);
    }

    protected function cancelRideResponse(
        string $conn,
        RideRequest $ride,
        TaxiRideCancellationService $cancellation,
        CustomerRideLiveStatusService $live
    ): JsonResponse {
        try {
            $result = $cancellation->cancelByCustomer($conn, $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Annuleren mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        $days = app(TaxiCustomerRideCancelledMailer::class)->refundBusinessDays();
        $message = 'Je rit is geannuleerd.';
        if ($result['refunded']) {
            $message .= ' Het betaalde bedrag wordt teruggestort (doorgaans binnen '.$days.' werkdagen).';
        } elseif ($result['refund_error']) {
            $message .= ' De terugbetaling kon niet automatisch worden afgerond. Neem contact op met de taxi.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'refunded' => (bool) $result['refunded'],
            'refund_error' => $result['refund_error'],
            'refund_business_days' => $days,
            'ride' => $live->livePayload($result['ride']),
        ]);
    }

    protected function waitRideResponse(
        string $conn,
        RideRequest $ride,
        TaxiRideCancellationService $cancellation,
        CustomerRideLiveStatusService $live
    ): JsonResponse {
        try {
            $result = $cancellation->chooseToWait($conn, $ride);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Keuze opslaan mislukt.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'We blijven zoeken naar een taxi.',
            'ride' => $live->livePayload($result['ride']),
        ]);
    }

    protected function payRideResponse(
        RideRequest $ride,
        CustomerMarketplaceBookingService $booking,
        CustomerRideLiveStatusService $live,
        ?string $returnUrl
    ): JsonResponse {
        try {
            $result = $booking->retryBookingPayment($ride, $returnUrl);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?: 'Betaling starten mislukt.',
                'errors' => $e->errors(),
                'ride' => $live->livePayload($ride->fresh() ?? $ride),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Je wordt doorgestuurd naar de betaling.',
            'checkout_url' => $result['checkout_url'],
            'track_token' => $result['track_token'],
            'ride' => $live->livePayload($result['ride']),
        ]);
    }

    protected function invoicePdfResponse(
        string $conn,
        RideRequest $ride,
        TaxiRideInvoiceService $invoices,
        InvoicePdfService $pdf
    ): Response|JsonResponse {
        if ($ride->status !== RideRequest::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Een factuur is alleen beschikbaar na een succesvol afgeronde rit.',
            ], 422);
        }

        try {
            $invoice = $invoices->ensureInvoiceForCustomerPortal($conn, $ride, generatePdf: true);
            $result = $pdf->generateAndStore($invoice->fresh());
            $bytes = $result['bytes'];
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?: 'Factuur kon niet worden gemaakt.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable) {
            return response()->json(['message' => 'PDF kon niet worden gemaakt.'], 500);
        }

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

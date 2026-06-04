<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiPortalDataService;
use App\Services\InvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TaxiPortalApiController extends Controller
{
    public function __construct(
        protected TaxiPortalDataService $portalData
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $chartPeriod = (string) $request->query('chart_period', 'month');

        return response()->json([
            'success' => true,
            'data' => $this->portalData->dashboardPayload($this->portalUser(), $chartPeriod),
        ]);
    }

    public function rides(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->portalData->listRidesForCustomer($this->portalUser()),
        ]);
    }

    public function showRide(int $ride): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->portalData->rideDetailPayload($this->portalUser(), $ride),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->portalData->listInvoicesForCustomer($this->portalUser()),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->portalData->profilePayload($this->portalUser()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => [
                'required',
                'string',
                'max:50',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $digits = preg_replace('/\D+/', '', (string) $value);
                    if ($digits === null || strlen($digits) < 8) {
                        $fail('Telefoonnummer is ongeldig.');
                    }
                },
            ],
        ], [
            'first_name.required' => 'Voornaam is verplicht.',
            'last_name.required' => 'Achternaam is verplicht.',
            'phone.required' => 'Telefoon is verplicht.',
        ]);

        $user = $this->portalData->updateProfile($this->portalUser(), $validated);

        return response()->json([
            'success' => true,
            'message' => 'Gegevens opgeslagen.',
            'data' => $this->portalData->profilePayload($user),
        ]);
    }

    public function downloadInvoicePdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        if (! $this->portalData->customerOwnsInvoice($this->portalUser(), $invoice)) {
            abort(403, 'Geen toegang tot deze factuur.');
        }

        try {
            $result = $pdfService->generateAndStore($invoice->fresh());
            $bytes = $result['bytes'];
        } catch (\Throwable $e) {
            abort(500, 'PDF kon niet worden gemaakt.');
        }

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    protected function portalUser(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}

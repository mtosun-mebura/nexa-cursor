<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\Payout\PayoutIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TenantPayoutIdentityController extends Controller
{
    public function __construct(
        private readonly PayoutIdentityService $payouts
    ) {}

    public function show(Request $request): JsonResponse
    {
        $company = $this->requireTenantCompany($request);
        $identity = $this->payouts->forCompany($company) ?? $this->payouts->bootstrapCompanyIdentity($company, $request->user(), $request);

        return response()->json([
            'data' => $this->appPayload($identity),
        ]);
    }

    public function updateBankAccount(Request $request): JsonResponse
    {
        $company = $this->requireTenantCompany($request);

        $data = $request->validate([
            'iban' => ['required', 'string', 'max:42'],
            'password' => ['nullable', 'string'],
            'confirmation_code' => ['nullable', 'string', 'max:12'],
        ]);

        try {
            $identity = $this->payouts->setCompanyBankAccount(
                $company,
                $request->user(),
                $data['iban'],
                $data['password'] ?? null,
                $request
            );
        } catch (ValidationException $e) {
            throw $e;
        }

        return response()->json([
            'message' => $identity->hasPendingDestinationChange()
                ? 'Wijziging aangevraagd. Het nieuwe rekeningnummer wordt pas over '.(int) config('nexa_payout.destination_change_cooling_off_hours', 48).' uur gebruikt. App toont alleen *** + laatste 4 cijfers.'
                : 'Bankrekening geregistreerd. App toont alleen *** + laatste 4 cijfers.',
            'data' => $this->appPayload($identity),
        ]);
    }

    private function requireTenantCompany(Request $request): Company
    {
        $user = $request->user();
        if (! $user || ! $user->company_id) {
            abort(403, 'Geen tenant gekoppeld.');
        }

        if (! $user->isTenantAdmin() && ! $user->hasRole('super-admin')) {
            abort(403, 'Alleen company-admin of marketplace mag payout-gegevens beheren.');
        }

        $company = Company::query()->find($user->company_id);
        if (! $company) {
            abort(404, 'Tenant niet gevonden.');
        }

        return $company;
    }

    /**
     * @return array<string, mixed>
     */
    private function appPayload($identity): array
    {
        $payload = $this->payouts->publicPayload($identity);

        return [
            'masked_bank_account' => $payload['masked_destination'],
            'pending_masked_bank_account' => $payload['pending_destination_change']
                ? ($identity->pending_masked_destination ?: null)
                : null,
            'capability_status' => $payload['capability_status'],
            'can_receive_settlement' => $payload['can_receive_settlement'],
            'destination_change_cooling' => $payload['destination_change_cooling'],
            'destination_change_eligible_at' => $payload['destination_change_eligible_at'],
            'bank_account_required' => ! filled($payload['masked_destination']),
        ];
    }
}

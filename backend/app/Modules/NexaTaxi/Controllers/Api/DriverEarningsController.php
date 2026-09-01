<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsService;
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DriverEarningsController extends Controller
{
    public function show(
        Request $request,
        TaxiDriverEarningsAccessService $access,
        TaxiDriverEarningsService $earnings
    ): JsonResponse {
        $user = $request->user();
        $companyId = (int) $request->attributes->get('taxi_company_id', $user->company_id);
        $perms = $access->permissionsFor($user, $companyId);

        if (! $perms['view']) {
            return response()->json([
                'message' => 'Je hebt geen toegang tot inkomsten. Vraag je beheerder om de rol chauffeur-inkomsten.',
                'error' => 'earnings_forbidden',
            ], 403);
        }

        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $tz = ContractTransportTimezone::TIMEZONE;
        $today = Carbon::now($tz)->toDateString();
        $date = $data['date'] ?? $today;

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date, $tz);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => ['Ongeldige datum.'],
            ]);
        }

        if ($parsed->toDateString() > $today) {
            $date = $today;
        }

        $payload = $earnings->forDriverDay(
            $companyId,
            (int) $user->id,
            $date,
            $perms['view_month']
        );

        return response()->json([
            'data' => $payload,
            'permissions' => [
                'earnings_view' => true,
                'earnings_view_month' => $perms['view_month'],
            ],
        ]);
    }
}

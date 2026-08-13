<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiDriverAccountStatus;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    public function login(Request $request, TaxiDriverEligibilityService $eligibility, ModuleDatabaseService $moduleDb, TaxiDriverEarningsAccessService $earningsAccess): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Onjuiste inloggegevens.'],
            ]);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'message' => 'E-mailadres is nog niet geverifieerd.',
            ], 403);
        }

        $companyId = (int) $user->company_id;
        if ($companyId <= 0 || ! $eligibility->isChauffeurForCompany($user, $companyId)) {
            return response()->json([
                'message' => 'Dit account heeft geen chauffeur-toegang.',
            ], 403);
        }

        if (! TaxiDriverAccountStatus::isActive($user)) {
            return TaxiDriverAccountStatus::inactiveResponse();
        }

        $user->tokens()->where('name', 'taxi-driver')->delete();

        $expiryDays = (int) config('taxi-dispatch.token_expiry_days', 14);
        $token = $user->createToken(
            'taxi-driver',
            ['taxi:driver'],
            now()->addDays($expiryDays)
        );

        $isOnline = $this->driverIsOnline($moduleDb, (int) $user->id);
        $earningsPerms = $earningsAccess->permissionsFor($user, $companyId);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->driverUserPayload($user, $companyId, true, $isOnline),
            'permissions' => [
                'earnings_view' => $earningsPerms['view'],
                'earnings_view_month' => $earningsPerms['view_month'],
            ],
            'meta' => [
                'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Uitgelogd.']);
    }

    public function me(Request $request, ModuleDatabaseService $moduleDb, TaxiDriverEarningsAccessService $earningsAccess): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->attributes->get('taxi_company_id', $user->company_id);

        $accountActive = TaxiDriverAccountStatus::isActive($user);

        $isOnline = $this->driverIsOnline($moduleDb, (int) $user->id);
        $earningsPerms = $earningsAccess->permissionsFor($user, $companyId);

        return response()->json([
            'user' => $this->driverUserPayload($user, $companyId, $accountActive, $isOnline),
            'permissions' => [
                'earnings_view' => $earningsPerms['view'],
                'earnings_view_month' => $earningsPerms['view_month'],
            ],
            'meta' => [
                'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function driverUserPayload(User $user, int $companyId, bool $accountActive, bool $isOnline): array
    {
        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));
        $fullName = trim($firstName.' '.$lastName);
        $companyName = null;
        if ($companyId > 0) {
            if ((int) ($user->company_id ?? 0) === $companyId) {
                $company = $user->company;
                $companyName = $company ? (trim((string) ($company->name ?? '')) ?: null) : null;
            }
            if ($companyName === null) {
                $raw = \App\Models\Company::query()->whereKey($companyId)->value('name');
                $companyName = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
            }
        }

        return [
            'id' => $user->id,
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'name' => $fullName !== '' ? $fullName : (string) ($user->email ?? ''),
            'email' => $user->email,
            'phone' => trim((string) ($user->phone ?? '')) ?: null,
            'company_id' => $companyId,
            'company_name' => $companyName,
            'is_account_active' => $accountActive,
            'is_online' => $isOnline,
        ];
    }

    private function driverIsOnline(ModuleDatabaseService $moduleDb, int $driverId): bool
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return false;
        }

        $availability = DriverAvailability::on($conn)
            ->where('driver_id', $driverId)
            ->first();

        return $availability && $availability->is_online;
    }
}

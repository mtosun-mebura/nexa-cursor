<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Services\DriverScheduleService;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiDriverEarningsAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Support\PwaAccent;
use App\Modules\NexaTaxi\Support\RideAlertTone;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiDriverAccountStatus;
use App\Services\CompanyEntitlementService;
use App\Services\ModuleDatabaseService;
use App\Services\PlatformBilling\TenantBillingAccessService;
use App\Support\TenantPackageCapability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverAuthController extends Controller
{
    public function login(Request $request, TaxiDriverEligibilityService $eligibility, ModuleDatabaseService $moduleDb, TaxiDriverEarningsAccessService $earningsAccess): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();
        $firstLogin = app(TaxiAppFirstLoginService::class);
        if ($user && $firstLogin->needsFirstLogin($user) && $firstLogin->userMayUseChannel($user, TaxiAppFirstLoginService::CHANNEL_DRIVER)) {
            return response()->json([
                'message' => TaxiAppFirstLoginService::FIRST_LOGIN_REQUIRED_MESSAGE,
                'error' => 'first_login_required',
            ], 403);
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Onjuiste e-mail of wachtwoord.',
            ], 401);
        }

        return $this->driverSessionResponse($user, $eligibility, $moduleDb, $earningsAccess);
    }

    public function requestLoginCode(Request $request, TaxiAppFirstLoginService $firstLogin): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
        ]);

        $result = $firstLogin->requestCode(
            $data['email'],
            TaxiAppFirstLoginService::CHANNEL_DRIVER,
            (string) $request->ip()
        );

        return $this->firstLoginJson($result);
    }

    public function verifyLoginCode(
        Request $request,
        TaxiAppFirstLoginService $firstLogin,
        TaxiDriverEligibilityService $eligibility,
        ModuleDatabaseService $moduleDb,
        TaxiDriverEarningsAccessService $earningsAccess
    ): JsonResponse {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'required|string|min:8|max:255',
        ], [
            'email.required' => 'Vul je e-mailadres in.',
            'code.required' => 'Vul de code uit je e-mail in.',
            'password.required' => 'Kies een wachtwoord.',
            'password.min' => 'Kies een wachtwoord van minimaal 8 tekens.',
        ]);

        $result = $firstLogin->verifyAndSetPassword(
            $data['email'],
            $data['code'],
            $data['password'],
            TaxiAppFirstLoginService::CHANNEL_DRIVER,
            (string) $request->ip()
        );

        if (! $result['ok'] || ! ($result['user'] ?? null) instanceof User) {
            return $this->firstLoginJson($result);
        }

        return $this->driverSessionResponse($result['user'], $eligibility, $moduleDb, $earningsAccess);
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

        $availability = $this->driverAvailability($moduleDb, (int) $user->id);
        $isOnline = $availability && $availability->is_online;
        $earningsPerms = $earningsAccess->permissionsFor($user, $companyId);

        return response()->json([
            'user' => $this->driverUserPayload($user, $companyId, $accountActive, $isOnline, $availability),
            'permissions' => [
                'earnings_view' => $earningsPerms['view'],
                'earnings_view_month' => $earningsPerms['view_month'],
            ],
            'meta' => [
                'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
            ],
        ]);
    }

    public function updateAccent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'accent' => ['required', 'string', 'in:'.implode(',', PwaAccent::KEYS)],
        ]);

        $accent = PwaAccent::saveFor($request->user(), $data['accent']);

        return response()->json([
            'pwa_accent' => $accent,
        ]);
    }

    public function updateRideAlertTone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tone' => ['required', 'string', 'in:'.implode(',', RideAlertTone::KEYS)],
        ]);

        $tone = RideAlertTone::saveFor($request->user(), $data['tone']);

        return response()->json([
            'ride_alert_tone' => $tone,
        ]);
    }

    /**
     * @param  array{ok: bool, status: int, message: string, retry_after?: int}  $result
     */
    private function firstLoginJson(array $result): JsonResponse
    {
        $payload = ['message' => $result['message']];
        if (isset($result['retry_after'])) {
            $payload['retry_after'] = $result['retry_after'];
        }

        return response()->json($payload, $result['status']);
    }

    private function driverSessionResponse(
        User $user,
        TaxiDriverEligibilityService $eligibility,
        ModuleDatabaseService $moduleDb,
        TaxiDriverEarningsAccessService $earningsAccess
    ): JsonResponse {
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

        $company = Company::query()->find($companyId);
        $entitlements = app(CompanyEntitlementService::class);
        if (! $entitlements->allows($company, TenantPackageCapability::DRIVER_APP)) {
            return $entitlements->jsonDenied($company, TenantPackageCapability::DRIVER_APP);
        }

        $billingAccess = app(TenantBillingAccessService::class);
        if ($billingAccess->isFullyBlocked($company)) {
            return response()->json(['message' => $billingAccess->fullBlockMessage()], 403);
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

        $availability = $this->driverAvailability($moduleDb, (int) $user->id);
        $isOnline = $availability && $availability->is_online;
        $earningsPerms = $earningsAccess->permissionsFor($user, $companyId);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->driverUserPayload($user, $companyId, true, $isOnline, $availability),
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
    private function driverUserPayload(User $user, int $companyId, bool $accountActive, bool $isOnline, ?DriverAvailability $availability = null): array
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
                $raw = Company::query()->whereKey($companyId)->value('name');
                $companyName = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
            }
        }

        $vehicleId = $availability && $availability->vehicle_id ? (int) $availability->vehicle_id : null;
        $vehicleLocked = false;
        try {
            $conn = app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
            $lockedVehicleId = app(DriverScheduleService::class)->resolveLockedVehicleId($conn, $companyId, (int) $user->id);
            if ($lockedVehicleId) {
                $vehicleId = $lockedVehicleId;
                $vehicleLocked = true;
            }
        } catch (\Throwable) {
            // Agenda/planning-lock is optioneel bij ontbrekende taxi-tabel.
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
            'vehicle_id' => $vehicleId,
            'vehicle_locked' => $vehicleLocked,
            'pwa_accent' => PwaAccent::fromUser($user),
            'ride_alert_tone' => RideAlertTone::fromUser($user),
            'can_handle_contract_rides' => app(TaxiDriverEligibilityService::class)->canUseContractRideFilter($user),
        ];
    }

    private function driverAvailability(ModuleDatabaseService $moduleDb, int $driverId): ?DriverAvailability
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        if (! TaxiDispatchSchema::driverAvailabilityExists($conn)) {
            return null;
        }

        TaxiDispatchSchema::ensureVehicleIdColumn($conn);

        return DriverAvailability::on($conn)
            ->where('driver_id', $driverId)
            ->first();
    }
}

<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Models\DriverAvailability;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Modules\NexaTaxi\Support\TaxiDispatchSchema;
use App\Modules\NexaTaxi\Support\TaxiDriverAccountStatus;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    public function login(Request $request, TaxiDriverEligibilityService $eligibility, ModuleDatabaseService $moduleDb): JsonResponse
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

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name.' '.$user->last_name),
                'email' => $user->email,
                'company_id' => $companyId,
                'is_account_active' => true,
                'is_online' => $isOnline,
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

    public function me(Request $request, ModuleDatabaseService $moduleDb): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->attributes->get('taxi_company_id', $user->company_id);

        $accountActive = TaxiDriverAccountStatus::isActive($user);

        $isOnline = $this->driverIsOnline($moduleDb, (int) $user->id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name.' '.$user->last_name),
                'email' => $user->email,
                'company_id' => $companyId,
                'is_account_active' => $accountActive,
                'is_online' => $isOnline,
            ],
            'meta' => [
                'poll_interval_ms' => (int) config('taxi-dispatch.inbox_poll_interval_ms', 3000),
            ],
        ]);
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

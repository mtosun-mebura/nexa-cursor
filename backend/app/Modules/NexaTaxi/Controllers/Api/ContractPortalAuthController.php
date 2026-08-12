<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ContractPortalAuthController extends Controller
{
    public function login(
        Request $request,
        ModuleDatabaseService $moduleDb,
        TaxiContractPortalAccessService $access
    ): JsonResponse {
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
            return response()->json(['message' => 'E-mailadres is nog niet geverifieerd.'], 403);
        }

        $conn = $moduleDb->getModuleConnectionName('taxi');
        app(TaxiContractvervoerSchemaService::class)->ensureContractPortalTables($conn);

        $context = $access->resolveContext($conn, $user);
        if ($context === null) {
            return response()->json([
                'message' => 'Dit account heeft geen toegang tot het contractportaal.',
            ], 403);
        }

        $user->tokens()->where('name', 'taxi-contract')->delete();

        $expiryDays = (int) config('taxi-dispatch.token_expiry_days', 14);
        $token = $user->createToken(
            'taxi-contract',
            ['taxi:contract'],
            now()->addDays($expiryDays)
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'user' => $this->userPayload($user, $context),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Uitgelogd.']);
    }

    public function me(Request $request, TaxiContractPortalAccessService $access): JsonResponse
    {
        $user = $request->user();
        $context = $request->attributes->get('taxi_contract_context');

        return response()->json([
            'user' => $this->userPayload($user, $context),
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function userPayload(User $user, array $context): array
    {
        $role = (string) ($context['portal_role'] ?? '');
        $roleLabel = $role === TransportCustomerPortalUser::ROLE_CONTRACTANT
            ? 'Contractant'
            : 'Contractouder';

        return [
            'id' => $user->id,
            'name' => trim($user->first_name.' '.$user->last_name) ?: $user->email,
            'email' => $user->email,
            'company_id' => (int) ($context['company_id'] ?? 0),
            'transport_customer_id' => (int) ($context['transport_customer_id'] ?? 0),
            'portal_role' => $role,
            'portal_role_label' => $roleLabel,
            'is_contractant' => $role === TransportCustomerPortalUser::ROLE_CONTRACTANT,
        ];
    }
}

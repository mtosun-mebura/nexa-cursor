<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Modules\NexaTaxi\Support\PwaAccent;
use App\Services\CompanyEntitlementService;
use App\Services\ModuleDatabaseService;
use App\Services\PlatformBilling\TenantBillingAccessService;
use App\Support\TenantPackageCapability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $firstLogin = app(TaxiAppFirstLoginService::class);
        if ($user && $firstLogin->needsFirstLogin($user) && $firstLogin->userMayUseChannel($user, TaxiAppFirstLoginService::CHANNEL_CONTRACT)) {
            return response()->json([
                'message' => 'Dit account is nog niet geactiveerd. Vraag een inlogcode aan om zelf een wachtwoord te kiezen.',
                'error' => 'first_login_required',
            ], 403);
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Onjuiste e-mail of wachtwoord.',
            ], 401);
        }

        return $this->contractSessionResponse($user, $moduleDb, $access);
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
            TaxiAppFirstLoginService::CHANNEL_CONTRACT,
            (string) $request->ip()
        );

        return $this->firstLoginJson($result);
    }

    public function verifyLoginCode(
        Request $request,
        TaxiAppFirstLoginService $firstLogin,
        ModuleDatabaseService $moduleDb,
        TaxiContractPortalAccessService $access
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
            TaxiAppFirstLoginService::CHANNEL_CONTRACT,
            (string) $request->ip()
        );

        if (! $result['ok'] || ! ($result['user'] ?? null) instanceof User) {
            return $this->firstLoginJson($result);
        }

        return $this->contractSessionResponse($result['user'], $moduleDb, $access);
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

    private function contractSessionResponse(
        User $user,
        ModuleDatabaseService $moduleDb,
        TaxiContractPortalAccessService $access
    ): JsonResponse {
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

        $company = Company::query()->find($context['company_id'] ?? null);
        $entitlements = app(CompanyEntitlementService::class);
        if (! $entitlements->allows($company, TenantPackageCapability::CONTRACT_PORTAL)) {
            return $entitlements->jsonDenied($company, TenantPackageCapability::CONTRACT_PORTAL);
        }

        $billingAccess = app(TenantBillingAccessService::class);
        if ($billingAccess->isFullyBlocked($company)) {
            return response()->json(['message' => $billingAccess->fullBlockMessage()], 403);
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
            'first_name' => trim((string) ($user->first_name ?? '')) ?: null,
            'last_name' => trim((string) ($user->last_name ?? '')) ?: null,
            'name' => trim($user->first_name.' '.$user->last_name) ?: $user->email,
            'email' => $user->email,
            'phone' => trim((string) ($user->phone ?? '')) ?: null,
            'company_id' => (int) ($context['company_id'] ?? 0),
            'company_name' => $this->resolveCompanyName((int) ($context['company_id'] ?? 0), $user),
            'transport_customer_id' => (int) ($context['transport_customer_id'] ?? 0),
            'portal_role' => $role,
            'portal_role_label' => $roleLabel,
            'is_contractant' => $role === TransportCustomerPortalUser::ROLE_CONTRACTANT,
            'pwa_accent' => PwaAccent::fromUser($user),
        ];
    }

    private function resolveCompanyName(int $companyId, User $user): ?string
    {
        if ($companyId <= 0) {
            return null;
        }
        if ((int) ($user->company_id ?? 0) === $companyId && $user->company) {
            $name = trim((string) ($user->company->name ?? ''));

            return $name !== '' ? $name : null;
        }
        $raw = \App\Models\Company::query()->whereKey($companyId)->value('name');

        return is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
    }
}

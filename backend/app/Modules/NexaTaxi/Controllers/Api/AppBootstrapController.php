<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiAppCapabilitiesService;
use App\Modules\NexaTaxi\Services\TaxiAppFirstLoginService;
use App\Modules\NexaTaxi\Services\TaxiContractPortalAccessService;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\MarketplaceCompanyRegistrationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Geïntegreerde app-bootstrap: login → rollen/modi → juiste schermen.
 */
class AppBootstrapController extends Controller
{
    public function capabilities(Request $request, TaxiAppCapabilitiesService $capabilities): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Niet ingelogd.'], 401);
        }

        return response()->json([
            'data' => $capabilities->forUser($user),
        ]);
    }

    public function login(
        Request $request,
        TaxiAppCapabilitiesService $capabilities,
        TaxiDriverEligibilityService $drivers,
        TaxiContractPortalAccessService $contractAccess,
        ModuleDatabaseService $moduleDb,
        TaxiAppFirstLoginService $firstLogin,
    ): JsonResponse {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->whereRaw('LOWER(email) = ?', [strtolower(trim($data['email']))])->first();

        if ($user && $firstLogin->needsFirstLogin($user)) {
            return response()->json([
                'message' => TaxiAppFirstLoginService::FIRST_LOGIN_REQUIRED_MESSAGE,
                'error' => 'first_login_required',
            ], 403);
        }

        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            return response()->json(['message' => 'Onjuiste e-mail of wachtwoord.'], 401);
        }

        return $this->sessionPayload($user, $capabilities, $drivers, $contractAccess, $moduleDb);
    }

    public function registerMarketplace(
        Request $request,
        MarketplaceCompanyRegistrationService $registration,
        TaxiAppFirstLoginService $firstLogin,
    ): JsonResponse {
        $validated = $request->validate([
            'company_name' => 'required|string|max:180',
            'email' => 'required|email|max:180',
            'phone' => 'required|string|min:8|max:40',
            'city' => 'required|string|min:2|max:120',
            'contact_first_name' => 'nullable|string|max:80',
            'contact_last_name' => 'nullable|string|max:80',
        ], [
            'company_name.required' => 'Vul de bedrijfsnaam in.',
            'email.required' => 'Vul een e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'phone.required' => 'Vul een telefoonnummer in.',
            'phone.min' => 'Vul een geldig telefoonnummer in.',
            'city.required' => 'Vul de plaats in waar het bedrijf zich bevindt.',
            'city.min' => 'Plaats moet minimaal 2 tekens bevatten.',
        ]);

        $ip = (string) $request->ip();

        try {
            // Geen admin-webcode: app stuurt zelf een chauffeur-app-code.
            $registration->register($validated, $ip, false);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Registratie mislukt. Probeer het later opnieuw.',
            ], 500);
        }

        $email = strtolower(trim($validated['email']));
        $code = $firstLogin->requestCode($email, TaxiAppFirstLoginService::CHANNEL_DRIVER, $ip);

        return response()->json([
            'message' => ($code['ok'] ?? false)
                ? ('Bedrijf aangemaakt. '.$code['message'])
                : ('Bedrijf aangemaakt, maar de code kon niet worden verstuurd: '.($code['message'] ?? '')),
            'email' => $email,
            'channel' => TaxiAppFirstLoginService::CHANNEL_DRIVER,
            'next' => 'verify_code',
            'retry_after' => $code['retry_after'] ?? null,
        ], ($code['ok'] ?? false) ? 201 : 422);
    }

    public function requestLoginCode(Request $request, TaxiAppFirstLoginService $firstLogin): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Vul je e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
        ]);

        $email = strtolower(trim($data['email']));
        $ip = (string) $request->ip();

        $driverResult = $firstLogin->requestCode($email, TaxiAppFirstLoginService::CHANNEL_DRIVER, $ip);
        if ($driverResult['ok'] ?? false) {
            return response()->json([
                'message' => $driverResult['message'],
                'channel' => TaxiAppFirstLoginService::CHANNEL_DRIVER,
                'retry_after' => $driverResult['retry_after'] ?? null,
            ], $driverResult['status']);
        }

        $contractResult = $firstLogin->requestCode($email, TaxiAppFirstLoginService::CHANNEL_CONTRACT, $ip);
        if ($contractResult['ok'] ?? false) {
            return response()->json([
                'message' => $contractResult['message'],
                'channel' => TaxiAppFirstLoginService::CHANNEL_CONTRACT,
                'retry_after' => $contractResult['retry_after'] ?? null,
            ], $contractResult['status']);
        }

        $status = max((int) ($driverResult['status'] ?? 422), (int) ($contractResult['status'] ?? 422));

        return response()->json([
            'message' => $contractResult['message'] ?? $driverResult['message'] ?? 'Code aanvragen mislukt.',
            'retry_after' => $contractResult['retry_after'] ?? $driverResult['retry_after'] ?? null,
        ], $status >= 400 ? $status : 422);
    }

    public function verifyLoginCode(
        Request $request,
        TaxiAppCapabilitiesService $capabilities,
        TaxiDriverEligibilityService $drivers,
        TaxiContractPortalAccessService $contractAccess,
        ModuleDatabaseService $moduleDb,
        TaxiAppFirstLoginService $firstLogin,
    ): JsonResponse {
        $data = $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => 'nullable|string|min:8|max:255',
            'skip_password' => 'nullable|boolean',
            'channel' => 'nullable|string|in:driver,contract',
        ]);

        $email = strtolower(trim($data['email']));
        $ip = (string) $request->ip();
        $skipPassword = $request->boolean('skip_password')
            || trim((string) ($data['password'] ?? '')) === '';
        $channels = array_values(array_filter([
            $data['channel'] ?? null,
            TaxiAppFirstLoginService::CHANNEL_DRIVER,
            TaxiAppFirstLoginService::CHANNEL_CONTRACT,
        ]));
        $channels = array_values(array_unique($channels));

        $lastFail = null;
        foreach ($channels as $channel) {
            if ($skipPassword) {
                $result = $firstLogin->verifyAndLoginWithCode($email, $data['code'], $channel, $ip);
            } else {
                $result = $firstLogin->verifyAndSetPassword(
                    $email,
                    $data['code'],
                    (string) $data['password'],
                    $channel,
                    $ip
                );
            }

            if (($result['ok'] ?? false) && isset($result['user'])) {
                return $this->sessionPayload($result['user'], $capabilities, $drivers, $contractAccess, $moduleDb);
            }
            $lastFail = $result;
        }

        return response()->json([
            'message' => $lastFail['message'] ?? 'Code of e-mailadres is onjuist.',
            'code' => $lastFail['code'] ?? null,
        ], (int) ($lastFail['status'] ?? 422));
    }

    private function sessionPayload(
        User $user,
        TaxiAppCapabilitiesService $capabilities,
        TaxiDriverEligibilityService $drivers,
        TaxiContractPortalAccessService $contractAccess,
        ModuleDatabaseService $moduleDb,
    ): JsonResponse {
        $caps = $capabilities->forUser($user);
        $tokens = [];

        if ($caps['modes']['chauffeur']) {
            $user->tokens()->where('name', 'taxi-driver')->delete();
            $token = $user->createToken('taxi-driver', ['taxi:driver']);
            $tokens['driver'] = [
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            ];
        }

        if ($caps['modes']['contract']) {
            try {
                $conn = $moduleDb->getModuleConnectionName('taxi');
                if ($contractAccess->resolveContext($conn, $user)) {
                    $user->tokens()->where('name', 'taxi-contract')->delete();
                    $token = $user->createToken('taxi-contract', ['taxi:contract']);
                    $tokens['contract'] = [
                        'token' => $token->plainTextToken,
                        'token_type' => 'Bearer',
                        'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
                    ];
                }
            } catch (\Throwable) {
                // Contract-token is optioneel als schema ontbreekt.
            }
        }

        if ($tokens === [] && $caps['screens'] === []) {
            return response()->json([
                'message' => 'Dit account heeft geen toegang tot de chauffeur- of contract-app.',
            ], 403);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->email,
            ],
            'capabilities' => $caps,
            'tokens' => $tokens,
        ]);
    }
}

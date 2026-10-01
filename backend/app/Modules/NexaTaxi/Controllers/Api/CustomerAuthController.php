<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\NexaTaxi\Support\PwaAccent;
use App\Services\PublicRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::query()->where('email', strtolower(trim($data['email'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Onjuiste e-mail of wachtwoord.'], 401);
        }

        if (! $user->hasRole(PublicRegistrationService::ROLE_CUSTOMER)) {
            return response()->json([
                'message' => 'Dit account is geen klantaccount. Kies een andere rol in de app.',
            ], 403);
        }

        if ($user->is_active === false) {
            return response()->json(['message' => 'Dit account is gedeactiveerd.'], 403);
        }

        return $this->sessionResponse($user);
    }

    public function register(Request $request, PublicRegistrationService $registration): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|max:50',
        ]);

        $email = $registration->normalizeEmail($data['email']);
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Er bestaat al een account met dit e-mailadres. Log in of herstel je wachtwoord.'],
            ]);
        }

        $result = $registration->register([
            'email' => $email,
            'password' => $data['password'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'account_type' => PublicRegistrationService::ACCOUNT_CUSTOMER,
        ]);

        /** @var User $user */
        $user = $result['user'];
        if (! empty($data['phone'])) {
            $user->phone = (string) $data['phone'];
            $user->save();
        }
        // App-registratie: e-mail + wachtwoord volstaan voor direct gebruik.
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        return $this->sessionResponse($user->fresh() ?? $user, 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Uitgelogd.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|max:50',
        ]);

        $user->first_name = $data['first_name'];
        $user->last_name = $data['last_name'];
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        $user->save();

        return response()->json([
            'user' => $this->userPayload($user->fresh() ?? $user),
            'message' => 'Profiel opgeslagen.',
        ]);
    }

    public function updateAccent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'accent' => ['required', 'string', 'in:'.implode(',', PwaAccent::KEYS)],
        ]);

        return response()->json([
            'pwa_accent' => PwaAccent::saveFor($request->user(), $data['accent']),
        ]);
    }

    private function sessionResponse(User $user, int $status = 200): JsonResponse
    {
        $user->tokens()->where('name', 'taxi-customer-app')->delete();
        $token = $user->createToken('taxi-customer-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
            'role' => 'customer',
        ], $status);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'email' => (string) $user->email,
            'first_name' => (string) ($user->first_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
            'pwa_accent' => PwaAccent::fromUser($user),
        ];
    }
}

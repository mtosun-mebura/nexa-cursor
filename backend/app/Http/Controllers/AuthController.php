<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\EmailVerificationLinkService;
use App\Services\PublicRegistrationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, PublicRegistrationService $registration, EmailVerificationLinkService $verification)
    {
        // Never trust privilege fields from the client.
        $request->request->remove('role');
        $request->request->remove('roles');
        $request->request->remove('company_id');
        $request->request->remove('is_active');
        $request->request->remove('email_verified_at');
        $request->request->remove('permissions');

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
            'first_name' => 'nullable|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'account_type' => 'nullable|string|in:customer,driver,company,klant,chauffeur,taxi_company,business',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $registration->register([
                'email' => (string) $request->input('email'),
                'password' => (string) $request->input('password'),
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'last_name' => $request->input('last_name'),
                'account_type' => $request->input('account_type', PublicRegistrationService::ACCOUNT_CUSTOMER),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        /** @var User $user */
        $user = $result['user'];
        $verification->send($user);

        // No Sanctum token until e-mail is verified (login enforces the same).
        return response()->json([
            'message' => 'Account aangemaakt. Bevestig je e-mailadres om in te loggen.',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email_verified_at' => $user->email_verified_at,
                'account_type' => $result['account_type'],
                'role' => $result['role'],
            ],
            'requires_email_verification' => true,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = app(PublicRegistrationService::class)->normalizeEmail((string) $request->input('email'));

        if (! Auth::attempt(['email' => $email, 'password' => $request->input('password')])) {
            return response()->json([
                'message' => 'Ongeldige inloggegevens.',
            ], 401);
        }

        $user = User::where('email', $email)->firstOrFail();

        if (! $user->email_verified_at) {
            Auth::logout();

            return response()->json([
                'message' => 'Je e-mailadres is nog niet geverifieerd. Controleer je inbox voor de verificatielink of vraag een nieuwe aan via de beheerder.',
                'error' => 'email_not_verified',
            ], 403);
        }

        if ($user->hasRole(PublicRegistrationService::ROLE_DRIVER_PENDING)) {
            Auth::logout();

            return response()->json([
                'message' => 'Je chauffeuraccount is nog in behandeling. Je kunt nog niet inloggen als chauffeur.',
                'error' => 'driver_pending',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Do not claim success until 2FA is implemented.
        return response()->json([
            'message' => 'Two-factor verification is not configured.',
            'error' => 'two_factor_unavailable',
        ], 501);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Always return the same message to avoid account enumeration.
        Password::sendResetLink([
            'email' => app(PublicRegistrationService::class)->normalizeEmail((string) $request->input('email')),
        ]);

        return response()->json([
            'message' => 'Als dit e-mailadres bij ons bekend is, ontvang je een resetlink.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $status = Password::reset(
            [
                'email' => app(PublicRegistrationService::class)->normalizeEmail((string) $request->input('email')),
                'password' => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $request->input('token'),
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successfully',
            ]);
        }

        return response()->json([
            'message' => 'Unable to reset password',
        ], 400);
    }
}

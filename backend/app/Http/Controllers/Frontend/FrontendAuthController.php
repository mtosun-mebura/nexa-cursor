<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoginCode;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiCustomerLoginCodeService;
use App\Modules\NexaTaxi\Services\TaxiRideCustomerLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\PermissionRegistrar;

class FrontendAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::guard('web')->check()) {
            $user = $request->user();
            if ($user && $user->password_must_be_set) {
                return redirect()->route('frontend.set-password', [
                    'intended' => $this->defaultPostLoginUrl($user, $request),
                ]);
            }

            $intended = $this->sanitizeFrontendIntended(
                $request->query('intended') ?: $request->session()->get('url.intended')
            );
            if ($intended) {
                return redirect()->to($intended);
            }

            return redirect()->to($this->defaultPostLoginUrl($user, $request));
        }

        $intended = $request->query('intended');
        if ($intended && is_string($intended)) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';
            if ($path !== '' && ! Str::startsWith($path, '/admin')) {
                session(['url.intended' => $intended]);
            }
        }

        return view('frontend.pages.login', [
            'intendedUrl' => $this->sanitizeFrontendIntended(
                $request->query('intended') ?: $request->session()->get('url.intended')
            ),
            'codeLoginMode' => $request->boolean('code_login'),
            'prefillEmail' => (string) $request->query('email', ''),
        ]);
    }

    public function login(Request $request)
    {
        $intendedUrl = $request->input('intended') ?: $request->session()->get('url.intended');

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Onjuiste inloggegevens.'])->withInput($request->only('email'));
        }

        if ($user->password_must_be_set) {
            return back()->withErrors([
                'email' => 'U heeft nog geen wachtwoord ingesteld. Vraag een eenmalige inlogcode aan of log in met uw code.',
            ])->withInput($request->only('email'));
        }

        if (! $user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Je e-mailadres is nog niet geverifieerd. Controleer je inbox voor de verificatielink of vraag een inlogcode aan.',
            ])->withInput($request->only('email'));
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $this->linkTaxiRidesAfterLogin($user);

        return $this->redirectAfterAuthenticated($user, $request, $intendedUrl);
    }

    public function loginWithCode(Request $request)
    {
        $intendedUrl = $request->input('intended') ?: $request->session()->get('url.intended');

        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:'.TaxiCustomerLoginCodeService::CODE_LENGTH,
        ]);

        $email = trim((string) $request->input('email'));
        $code = trim((string) $request->input('code'));

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            return back()->withErrors(['email' => 'Onjuiste inloggegevens.'])->withInput($request->only('email'));
        }

        $record = CustomerLoginCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $record || ! Hash::check($code, $record->code_hash)) {
            return back()->withErrors(['code' => 'De code is ongeldig of verlopen.'])->withInput($request->only('email'));
        }

        $record->update(['consumed_at' => now()]);
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
        $this->linkTaxiRidesAfterLogin($user);

        if ($user->password_must_be_set) {
            $request->session()->put('must_set_password', 1);
            $intended = $this->sanitizeFrontendIntended($intendedUrl) ?: $this->defaultPostLoginUrl($user, $request);

            return redirect()->route('frontend.set-password', [
                'intended' => $intended,
            ]);
        }

        $request->session()->forget('must_set_password');

        return $this->redirectAfterAuthenticated($user, $request, $intendedUrl);
    }

    public function requestLoginCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'intended' => 'nullable|string|max:2000',
        ]);

        $email = trim((string) $request->input('email'));
        $user = User::query()->where('email', $email)->first();

        $codeSent = false;
        if ($user && ($user->password_must_be_set || $this->userHasKlantRole($user))) {
            $companyId = $user->company_id ? (int) $user->company_id : null;
            if (! $companyId && app()->bound('resolved_tenant_id')) {
                $resolved = (int) app('resolved_tenant_id');
                if ($resolved > 0) {
                    $companyId = $resolved;
                }
            }

            $intended = $this->sanitizeFrontendIntended($request->input('intended'))
                ?: $this->defaultPostLoginUrl($user, $request);
            $loginUrl = route('login', [
                'code_login' => 1,
                'email' => $user->email,
                'intended' => $intended,
            ]);
            $codeSent = app(TaxiCustomerLoginCodeService::class)->issueAndSend(
                $user,
                $companyId,
                $loginUrl
            );
        }

        $codeLength = TaxiCustomerLoginCodeService::CODE_LENGTH;
        $flash = $codeSent
            ? "Er is een code van {$codeLength} cijfers naar uw e-mailadres gestuurd."
            : "Als dit e-mailadres bij ons bekend is, ontvangt u binnen enkele minuten een code van {$codeLength} cijfers.";

        if ($user && ($user->password_must_be_set || $this->userHasKlantRole($user)) && ! $codeSent) {
            $flash = 'Uw account is bekend, maar de e-mail met inlogcode kon niet worden verstuurd. Controleer spam of neem contact op met de taxi. Op de server moet SMTP zijn ingesteld (Admin → Instellingen → E-mail).';
        }

        return redirect()
            ->route('login', [
                'code_login' => 1,
                'email' => $email,
                'intended' => $request->input('intended'),
            ])
            ->with($codeSent ? 'success' : 'warning', $flash);
    }

    public function showSetPasswordForm(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $request->session()->get('must_set_password') && ! $user->password_must_be_set) {
            $intended = $this->sanitizeFrontendIntended($request->query('intended'));

            return redirect()->to($intended ?: $this->defaultPostLoginUrl($user, $request));
        }

        return view('frontend.pages.set-password', [
            'intendedUrl' => $this->sanitizeFrontendIntended($request->query('intended'))
                ?: $this->defaultPostLoginUrl($user, $request),
        ]);
    }

    public function setPassword(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $request->session()->get('must_set_password') && ! $user->password_must_be_set) {
            return redirect()->to($this->defaultPostLoginUrl($user, $request));
        }

        $request->validate([
            'password' => ['required', Password::min(8)->mixedCase()->numbers()->symbols()],
            'password_confirmation' => 'required|same:password',
        ], [
            'password.required' => 'Nieuw wachtwoord is verplicht.',
            'password.min' => 'Wachtwoord moet minimaal 8 karakters bevatten.',
            'password.mixed' => 'Wachtwoord moet hoofdletters en kleine letters bevatten.',
            'password.numbers' => 'Wachtwoord moet minimaal één cijfer bevatten.',
            'password.symbols' => 'Wachtwoord moet minimaal één speciaal karakter bevatten.',
            'password_confirmation.required' => 'Herhaal wachtwoord is verplicht.',
            'password_confirmation.same' => 'Wachtwoord bevestiging komt niet overeen.',
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'password_must_be_set' => false,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $request->session()->forget('must_set_password');
        $this->linkTaxiRidesAfterLogin($user);

        return $this->redirectAfterAuthenticated(
            $user,
            $request,
            $request->input('intended') ?: $request->query('intended')
        );
    }

    private function linkTaxiRidesAfterLogin(User $user): void
    {
        if (! $this->userHasKlantRole($user) && ! $user->password_must_be_set) {
            return;
        }

        try {
            app(TaxiRideCustomerLinkService::class)->linkOrphanRidesForUser($user);
        } catch (\Throwable) {
            // Koppelen mag inloggen niet blokkeren
        }
    }

    private function redirectAfterAuthenticated(User $user, Request $request, mixed $intendedUrl): \Illuminate\Http\RedirectResponse
    {
        $intended = $this->sanitizeFrontendIntended($intendedUrl);
        if ($intended) {
            session()->forget('url.intended');

            return redirect()->to($intended);
        }

        return redirect()->to($this->defaultPostLoginUrl($user, $request));
    }

    private function userHasKlantRole(User $user): bool
    {
        if (in_array('klant', $user->webRoleNames(), true)) {
            return true;
        }

        $companyId = $user->company_id ? (int) $user->company_id : null;
        if (! $companyId && app()->bound('resolved_tenant_id')) {
            $resolved = (int) app('resolved_tenant_id');
            if ($resolved > 0) {
                $companyId = $resolved;
            }
        }

        if ($companyId <= 0) {
            return false;
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($companyId);

        try {
            return $user->hasRole('klant');
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }

    private function defaultPostLoginUrl(User $user, Request $request): string
    {
        $intended = $this->sanitizeFrontendIntended(
            $request->query('intended') ?: $request->session()->get('url.intended')
        );
        if ($intended) {
            return $intended;
        }

        if ($user->password_must_be_set || $this->userHasKlantRole($user)) {
            return route('taxi.portal.dashboard');
        }

        return route('dashboard');
    }

    private function sanitizeFrontendIntended(mixed $url): ?string
    {
        if (! $url || ! is_string($url)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        if ($path === '' || Str::startsWith($path, '/admin')) {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return $path.($query ? '?'.$query : '');
    }
}

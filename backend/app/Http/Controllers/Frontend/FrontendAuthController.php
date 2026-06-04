<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CustomerLoginCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FrontendAuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::guard('web')->check()) {
            $intended = $this->sanitizeFrontendIntended(
                $request->query('intended') ?: $request->session()->get('url.intended')
            );
            if ($intended) {
                return redirect()->to($intended);
            }

            return redirect()->route('dashboard');
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

        if (! $user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Je e-mailadres is nog niet geverifieerd. Controleer je inbox voor de verificatielink of vraag een nieuwe aan via de beheerder.',
            ])->withInput($request->only('email'));
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $intended = $this->sanitizeFrontendIntended($intendedUrl);
        if ($intended) {
            session()->forget('url.intended');

            return redirect()->to($intended);
        }

        return redirect()->route('dashboard');
    }

    public function loginWithCode(Request $request)
    {
        $intendedUrl = $request->input('intended') ?: $request->session()->get('url.intended');

        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
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
            return back()->withErrors(['email' => 'De code is ongeldig of verlopen.'])->withInput($request->only('email'));
        }

        $record->update(['consumed_at' => now()]);
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
        $request->session()->put('must_set_password', 1);

        $intended = $this->sanitizeFrontendIntended($intendedUrl);
        $next = route('frontend.set-password').($intended ? ('?intended='.urlencode($intended)) : '');

        return redirect()->to($next);
    }

    public function showSetPasswordForm(Request $request)
    {
        if (! $request->session()->get('must_set_password')) {
            return redirect()->route('dashboard');
        }

        return view('frontend.pages.set-password', [
            'intendedUrl' => $this->sanitizeFrontendIntended($request->query('intended')),
        ]);
    }

    public function setPassword(Request $request)
    {
        if (! $request->session()->get('must_set_password')) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
        ])->save();

        $request->session()->forget('must_set_password');

        $intended = $this->sanitizeFrontendIntended($request->input('intended') ?: $request->query('intended'));
        if ($intended) {
            return redirect()->to($intended);
        }

        return redirect()->route('dashboard');
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

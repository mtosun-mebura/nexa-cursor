<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EnvService;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function __construct()
    {
        // Middleware is now handled in routes/web.php
    }

    public function showLoginForm(Request $request)
    {
        // Only redirect to dashboard if user is authenticated AND has admin role
        if (Auth::guard('web')->check()) {
            try {
                if (Auth::user()->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
                    return redirect()->route('admin.dashboard');
                }
            } catch (QueryException) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        // Bewaar intended URL uit query (bijv. bij directe redirect naar login met ?intended=...)
        $intended = $request->query('intended');
        if ($intended && is_string($intended)) {
            $path = parse_url($intended, PHP_URL_PATH);
            if ($path && \Illuminate\Support\Str::startsWith($path, '/admin')) {
                session(['url.intended' => $intended]);
            }
        }

        // Always show login form for non-authenticated users or users without admin role
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        // Intended voor redirect: POST-parameter heeft voorrang (komt uit hidden field op loginpagina), anders session
        $intendedUrl = $request->input('intended') ?: $request->session()->get('url.intended');

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');
        $withInput = array_merge($request->only('email'), array_filter(['intended' => $intendedUrl]));

        $dbSetupHint = 'De database is niet volledig ingesteld (tabellen ontbreken). Ga naar de map backend en voer uit: php artisan migrate — daarna eventueel php artisan db:seed voor rollen en een eerste beheerder.';

        try {
            $user = User::where('email', $credentials['email'])->first();
        } catch (QueryException $e) {
            return back()->withErrors(['email' => $dbSetupHint])->withInput($withInput);
        }

        if (! $user) {
            return back()->withErrors(['email' => 'Gebruiker niet gevonden.'])->withInput($withInput);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors(['email' => 'Wachtwoord is incorrect.'])->withInput($withInput);
        }

        if (! $user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Je e-mailadres is nog niet geverifieerd. Controleer je inbox voor de verificatielink of vraag een nieuwe aan via de beheerder.',
            ])->withInput($withInput);
        }

        try {
            $hasAdminRole = $user->hasAnyRole(['super-admin', 'company-admin', 'staff']);
            $isCandidate = $user->hasRole('candidate');
        } catch (QueryException $e) {
            return back()->withErrors(['email' => $dbSetupHint])->withInput($withInput);
        }

        if (! $hasAdminRole) {
            return back()->withErrors([
                'email' => 'Je hebt geen toegang tot het admin panel. Gebruik de frontend login voor kandidaat toegang.',
            ])->withInput($withInput);
        }

        if ($isCandidate) {
            return back()->withErrors([
                'email' => 'Kandidaten kunnen niet inloggen in het admin panel. Gebruik de frontend login.',
            ])->withInput($withInput);
        }

        // Check if this is the first login (no previous login recorded)
        $isFirstLogin = ! $request->session()->has('has_logged_in_before');

        // Manual login
        // Use Laravel's built-in "remember me" mechanism (secure persistent login cookie)
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        // Mark that user has logged in before
        $request->session()->put('has_logged_in_before', true);

        $path = $intendedUrl ? parse_url($intendedUrl, PHP_URL_PATH) : '';
        $isUtilityPath = $path && preg_match('#/admin/(chat|notifications)/unread-count#', $path);

        // Preview-URL: stuur door naar bewerkpagina (inclusief module-parameter) zodat je soepel verder kunt waar je was.
        if ($path && preg_match('#^/admin/website-pages/(\d+)/preview$#', $path, $m)) {
            $pageId = $m[1];
            $query = parse_url($intendedUrl, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            if (empty($params['module'])) {
                $page = \App\Models\WebsitePage::find($pageId);
                if ($page && ! empty($page->module_name)) {
                    $params['module'] = $page->module_name;
                    $query = http_build_query($params);
                }
            }
            $path = '/admin/website-pages/'.$pageId.'/edit';
            $intendedUrl = $path.($query ? '?'.$query : '');
        }

        // Edit-URL zonder module: voeg module toe uit de website-pagina zodat de juiste module-context behouden blijft.
        if ($path && preg_match('#^/admin/website-pages/(\d+)/edit$#', $path, $m)) {
            $query = parse_url($intendedUrl, PHP_URL_QUERY);
            parse_str($query ?? '', $params);
            if (empty($params['module'])) {
                $page = \App\Models\WebsitePage::find($m[1]);
                if ($page && ! empty($page->module_name)) {
                    $params['module'] = $page->module_name;
                    $query = http_build_query($params);
                    $intendedUrl = $path.'?'.$query;
                }
            }
        }

        // Intended URL heeft voorrang: na sessie-verlopen of directe link met ?intended= ga daarheen.
        // Gebruik relatief pad zodat de browser op dezelfde origin blijft en sessiecookies meeneemt.
        if ($intendedUrl && is_string($intendedUrl) && $path && \Illuminate\Support\Str::startsWith($path, '/admin') && ! $isUtilityPath) {
            session()->forget('url.intended');
            $query = parse_url($intendedUrl, PHP_URL_QUERY);
            $target = $path.($query ? '?'.$query : '');

            return redirect()->to($target);
        }

        // Eerste login of utility-URL: naar dashboard
        if ($isFirstLogin || $isUtilityPath) {
            session()->forget('url.intended');

            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Display the password reset link request form.
     */
    public function showLinkRequestForm()
    {
        return view('admin.auth.forgot-password');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        // Apply mail settings dynamically (same as AdminUserController)
        $envService = app(EnvService::class);
        $this->applyMailSettings($envService);

        // Use Laravel's password reset with custom callback
        $status = Password::broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) use ($envService) {
                // Check if user has admin role
                if (! $user->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
                    throw new \Exception('Dit e-mailadres heeft geen toegang tot het admin panel.');
                }

                // Customize the reset URL to point to admin password reset
                $resetUrl = route('admin.password.reset', ['token' => $token, 'email' => $user->email]);

                // Get mail settings
                $fromAddress = $envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
                $fromName = $envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));
                $smtpUsername = $envService->get('MAIL_USERNAME', '');

                // Send custom email
                \Illuminate\Support\Facades\Mail::send('emails.password-reset', [
                    'user' => $user,
                    'resetUrl' => $resetUrl,
                ], function ($message) use ($user, $fromAddress, $fromName, $smtpUsername) {
                    $message->to($user->email, $user->first_name.' '.$user->last_name)
                        ->subject('Wachtwoord resetten - Nexa Skillmatching')
                        ->from($fromAddress, $fromName);

                    // Add Sender header if SMTP username is available
                    if (! empty($smtpUsername)) {
                        try {
                            $symfonyMessage = $message->getSymfonyMessage();
                            $symfonyMessage->getHeaders()->remove('Sender');
                            $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                        } catch (\Exception $e) {
                            \Log::warning('Could not set Sender header', [
                                'error' => $e->getMessage(),
                                'smtp_username' => $smtpUsername,
                            ]);
                        }
                    }
                });
            }
        );

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('status', 'We hebben je een wachtwoord reset link gestuurd!');
        }

        return back()->withErrors(['email' => 'We kunnen geen gebruiker vinden met dit e-mailadres of dit e-mailadres heeft geen toegang tot het admin panel.']);
    }

    /**
     * Display the password reset form for the given token.
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('admin.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email')),
        ]);
    }

    /**
     * Reset the user's password.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);

        // Apply mail settings dynamically
        $envService = app(EnvService::class);
        $this->applyMailSettings($envService);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordResetEvent($user));
            }
        );

        // If the password was successfully reset, redirect to password changed page
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('admin.password.changed');
        }

        // If there's an error, redirect back with error message
        return back()->withErrors(['email' => [__($status)]]);
    }

    /**
     * Display the password changed confirmation page.
     */
    public function showPasswordChanged()
    {
        return view('admin.auth.password-changed');
    }

    /**
     * Apply mail settings dynamically (same as AdminUserController)
     */
    protected function applyMailSettings(EnvService $envService)
    {
        $mailer = $envService->get('MAIL_MAILER', 'log');
        $host = $envService->get('MAIL_HOST', '');
        $port = $envService->get('MAIL_PORT', '587');
        $username = $envService->get('MAIL_USERNAME', '');
        $password = $envService->get('MAIL_PASSWORD', '');
        $encryption = $envService->get('MAIL_ENCRYPTION', 'tls');
        $fromAddress = $envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
        $fromName = $envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' ? null : $encryption);

            if (! empty($username) && ! empty($password)) {
                Config::set('mail.mailers.smtp.auth_mode', null);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Config;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Str;
use App\Models\User;
use App\Services\EnvService;

class AdminAuthController extends Controller
{
    public function __construct()
    {
        // Middleware is now handled in routes/web.php
    }
    public function showLoginForm()
    {
        // Only redirect to dashboard if user is authenticated AND has admin role
        if (Auth::guard('web')->check() && Auth::user()->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
            return redirect()->route('admin.dashboard');
        }
        
        // Always show login form for non-authenticated users or users without admin role
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'remember' => 'nullable|boolean',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');
        
        // Debug: Check if user exists
        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return back()->withErrors([
                'email' => 'Gebruiker niet gevonden.',
            ]);
        }
        
        // Debug: Check password
        if (!Hash::check($credentials['password'], $user->password)) {
            return back()->withErrors([
                'email' => 'Wachtwoord is incorrect.',
            ]);
        }
        
        // Check if email is verified
        if (!$user->email_verified_at) {
            return back()->withErrors([
                'email' => 'Je e-mailadres is nog niet geverifieerd. Controleer je inbox voor de verificatielink of vraag een nieuwe aan via de beheerder.',
            ])->withInput($request->only('email'));
        }
        
        // Debug: Check role - allow all admin roles
        if (!$user->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
            return back()->withErrors([
                'email' => 'Je hebt geen toegang tot het admin panel.',
            ]);
        }
        
        // Check if this is the first login (no previous login recorded)
        $isFirstLogin = !$request->session()->has('has_logged_in_before');
        
        // Manual login
        // Use Laravel's built-in "remember me" mechanism (secure persistent login cookie)
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        
        // Mark that user has logged in before
        $request->session()->put('has_logged_in_before', true);
        
        // If first login, always redirect to dashboard
        if ($isFirstLogin) {
            return redirect()->route('admin.dashboard');
        }
        
        return redirect()->intended(route('admin.dashboard'));
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
                if (!$user->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
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
                    $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                            ->subject('Wachtwoord resetten - Nexa Skillmatching')
                            ->from($fromAddress, $fromName);
                    
                    // Add Sender header if SMTP username is available
                    if (!empty($smtpUsername)) {
                        try {
                            $symfonyMessage = $message->getSymfonyMessage();
                            $symfonyMessage->getHeaders()->remove('Sender');
                            $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                        } catch (\Exception $e) {
                            \Log::warning('Could not set Sender header', [
                                'error' => $e->getMessage(),
                                'smtp_username' => $smtpUsername
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
            'email' => $request->query('email', old('email'))
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
                    'password' => Hash::make($password)
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
            
            if (!empty($username) && !empty($password)) {
                Config::set('mail.mailers.smtp.auth_mode', null);
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
        
        // Debug: Check role - allow all admin roles
        if (!$user->hasAnyRole(['super-admin', 'company-admin', 'staff'])) {
            return back()->withErrors([
                'email' => 'Je hebt geen toegang tot het admin panel.',
            ]);
        }
        
        // Manual login
        // Use Laravel's built-in "remember me" mechanism (secure persistent login cookie)
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        
        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login');
    }
}

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
        if (Auth::guard('web')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        // Skip CSRF verification for debugging
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
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
        Auth::guard('web')->login($user);
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

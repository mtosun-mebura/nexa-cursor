<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TenantOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminForcePasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->must_change_password) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                'regex:'.TenantOnboardingService::PASSWORD_REGEX,
            ],
        ], [
            'password.required' => 'Nieuw wachtwoord is verplicht.',
            'password.min' => 'Wachtwoord moet minimaal 8 karakters lang zijn.',
            'password.confirmed' => 'De wachtwoorden komen niet overeen.',
            'password.regex' => 'Wachtwoord moet minimaal 1 kleine letter, 1 hoofdletter en 1 cijfer bevatten.',
        ]);

        if (Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Kies een ander wachtwoord dan het tijdelijke wachtwoord uit de e-mail.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'welcome_handleiding_pending' => true,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('admin.handleiding.index', ['saved' => 1])
            ->with('success', 'Wachtwoord opgeslagen. Welkom bij NEXA.');
    }
}

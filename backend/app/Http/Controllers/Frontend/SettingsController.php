<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        return view('frontend.pages.settings');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.required' => 'Huidig wachtwoord is verplicht',
            'password.required' => 'Nieuw wachtwoord is verplicht',
            'password.confirmed' => 'Wachtwoord bevestiging komt niet overeen',
            'password.min' => 'Wachtwoord moet minimaal 8 karakters bevatten',
            'password.mixed' => 'Wachtwoord moet hoofdletters en kleine letters bevatten',
            'password.numbers' => 'Wachtwoord moet minimaal één cijfer bevatten',
            'password.symbols' => 'Wachtwoord moet minimaal één speciaal karakter bevatten',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Huidig wachtwoord is onjuist'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wachtwoord succesvol gewijzigd!'
        ]);
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . Auth::id(),
        ], [
            'email.required' => 'E-mailadres is verplicht',
            'email.email' => 'E-mailadres moet een geldig e-mailadres zijn',
            'email.unique' => 'Dit e-mailadres is al in gebruik',
        ]);

        $user = Auth::user();
        $user->update([
            'email' => $request->email
        ]);

        return response()->json([
            'success' => true,
            'message' => 'E-mailadres succesvol gewijzigd!'
        ]);
    }
}

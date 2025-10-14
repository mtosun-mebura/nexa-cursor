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

    public function updateJobPreferences(Request $request)
    {
        try {
            $request->validate([
                'preferred_location' => 'nullable|string|max:255',
                'max_distance' => 'nullable|integer|min:0|max:1000',
                'contract_type' => 'nullable|string|max:255',
                'work_hours' => 'nullable|string|max:255',
                'min_salary' => 'nullable|integer|min:0',
            ], [
                'preferred_location.max' => 'Locatie mag maximaal 255 karakters bevatten',
                'max_distance.integer' => 'Afstand moet een getal zijn',
                'max_distance.min' => 'Afstand moet minimaal 0 zijn',
                'max_distance.max' => 'Afstand mag maximaal 1000 km zijn',
                'contract_type.max' => 'Contract type mag maximaal 255 karakters bevatten',
                'work_hours.max' => 'Werkuren mag maximaal 255 karakters bevatten',
                'min_salary.integer' => 'Salaris moet een geheel getal zijn',
                'min_salary.min' => 'Salaris moet minimaal 0 zijn',
            ]);

            $user = Auth::user();
            
            // Update user preferences
            $user->update([
                'preferred_location' => $request->preferred_location,
                'max_distance' => $request->max_distance,
                'contract_type' => $request->contract_type,
                'work_hours' => $request->work_hours,
                'min_salary' => $request->min_salary,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Job voorkeuren succesvol opgeslagen!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $errorMessages[] = implode(', ', $messages);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validatiefout: ' . implode('; ', $errorMessages)
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Job preferences update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van de voorkeuren.'
            ], 500);
        }
    }

    public function updateNotificationPreferences(Request $request)
    {
        try {
            // No validation needed for checkboxes - they're either present or not

            $user = Auth::user();
            
            $user->update([
                'email_notifications' => $request->has('email_notifications'),
                'sms_notifications' => $request->has('sms_notifications'),
                'push_notifications' => $request->has('push_notifications'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notificatie instellingen succesvol opgeslagen!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $errorMessages[] = implode(', ', $messages);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validatiefout: ' . implode('; ', $errorMessages)
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Notification preferences update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van de notificatie instellingen.'
            ], 500);
        }
    }

    public function updatePrivacyPreferences(Request $request)
    {
        try {
            // No validation needed for checkboxes - they're either present or not

            $user = Auth::user();
            
            $user->update([
                'profile_visible' => $request->has('profile_visible'),
                'cv_downloadable' => $request->has('cv_downloadable'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Privacy instellingen succesvol opgeslagen!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $errorMessages = [];
            foreach ($errors as $field => $messages) {
                $errorMessages[] = implode(', ', $messages);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validatiefout: ' . implode('; ', $errorMessages)
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Privacy preferences update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het opslaan van de privacy instellingen.'
            ], 500);
        }
    }



    public function exportData(Request $request)
    {
        $user = Auth::user();
        
        // Collect all user data
        $userData = [
            'personal_info' => [
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'date_of_birth' => $user->date_of_birth,
                'location' => $user->location,
                'bio' => $user->bio,
            ],
            'job_preferences' => [
                'preferred_location' => $user->preferred_location,
                'max_distance' => $user->max_distance,
                'contract_type' => $user->contract_type,
                'work_hours' => $user->work_hours,
                'min_salary' => $user->min_salary,
            ],
            'notification_preferences' => [
                'email_notifications' => $user->email_notifications,
                'sms_notifications' => $user->sms_notifications,
                'push_notifications' => $user->push_notifications,
            ],
            'privacy_preferences' => [
                'profile_visible' => $user->profile_visible,
                'cv_downloadable' => $user->cv_downloadable,
            ],
            'skills' => $user->skills->map(function($skill) {
                return [
                    'name' => $skill->name,
                    'level' => $skill->level,
                ];
            }),
            'experiences' => $user->experiences->map(function($experience) {
                return [
                    'company' => $experience->company,
                    'position' => $experience->position,
                    'start_date' => $experience->start_date,
                    'end_date' => $experience->end_date,
                    'description' => $experience->description,
                ];
            }),
            'favorites' => $user->favoriteVacancies->map(function($vacancy) {
                return [
                    'title' => $vacancy->title,
                    'company' => $vacancy->company->name,
                    'location' => $vacancy->location,
                ];
            }),
            'export_date' => now()->toISOString(),
        ];

        return response()->json($userData);
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();
        
        try {
            // Log the deletion for audit purposes
            \Log::info('User account deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'deleted_at' => now(),
            ]);

            // Delete user and all related data (cascade should handle this)
            $user->delete();

            // Logout the user
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Account succesvol verwijderd'
            ]);

        } catch (\Exception $e) {
            \Log::error('Account deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het verwijderen van je account.'
            ], 500);
        }
    }
}

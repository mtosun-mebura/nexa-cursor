<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load(['skills', 'experiences', 'cvFiles']);
        
        // Calculate profile completeness percentage
        $profileCompleteness = $this->calculateProfileCompleteness($user);
        
        return view('frontend.pages.profile', compact('user', 'profileCompleteness'));
    }
    
    private function calculateProfileCompleteness($user)
    {
        $totalFields = 0;
        $completedFields = 0;
        
        // Basic profile fields (40% of total)
        $basicFields = [
            'first_name' => !empty($user->first_name),
            'last_name' => !empty($user->last_name),
            'email' => !empty($user->email),
            'phone' => !empty($user->phone),
            'location' => !empty($user->location),
            'bio' => !empty($user->bio),
            'date_of_birth' => !empty($user->date_of_birth),
            'photo_blob' => !empty($user->photo_blob)
        ];
        
        foreach ($basicFields as $field => $isCompleted) {
            $totalFields++;
            if ($isCompleted) $completedFields++;
        }
        
        // Skills (30% of total)
        $hasTechnicalSkills = $user->skills()->where('type', 'technical')->count() > 0;
        $hasSoftSkills = $user->skills()->where('type', 'soft')->count() > 0;
        
        $totalFields += 2;
        if ($hasTechnicalSkills) $completedFields++;
        if ($hasSoftSkills) $completedFields++;
        
        // Work experience (30% of total)
        $hasExperience = $user->experiences()->count() > 0;
        
        $totalFields++;
        if ($hasExperience) $completedFields++;
        
        return round(($completedFields / $totalFields) * 100);
    }

    public function update(Request $request)
    {
        // Debug logging
        \Log::info('Profile update request', [
            'date_of_birth' => $request->date_of_birth,
            'all_data' => $request->all()
        ]);
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'date_of_birth' => 'nullable|string',
        ], [
            'first_name.required' => 'Voornaam is verplicht',
            'first_name.max' => 'Voornaam mag maximaal 255 karakters bevatten',
            'last_name.required' => 'Achternaam is verplicht',
            'last_name.max' => 'Achternaam mag maximaal 255 karakters bevatten',
            'email.required' => 'E-mailadres is verplicht',
            'email.email' => 'E-mailadres moet een geldig e-mailadres zijn',
            'email.unique' => 'Dit e-mailadres is al in gebruik',
            'phone.max' => 'Telefoonnummer mag maximaal 20 karakters bevatten',
            'location.max' => 'Locatie mag maximaal 255 karakters bevatten',
            'bio.max' => 'Bio mag maximaal 1000 karakters bevatten'
        ]);

        $user = Auth::user();
        // Convert date format from dd-mm-yyyy to yyyy-mm-dd
        $dateOfBirth = null;
        if ($request->date_of_birth && !empty(trim($request->date_of_birth))) {
            try {
                // Try to parse the date in dd-mm-yyyy format first
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $request->date_of_birth)) {
                    $dateOfBirth = \Carbon\Carbon::createFromFormat('d-m-Y', $request->date_of_birth)->format('Y-m-d');
                } else {
                    // If format doesn't match, try to parse with Carbon's flexible parsing
                    $parsedDate = \Carbon\Carbon::parse($request->date_of_birth);
                    $dateOfBirth = $parsedDate->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // If parsing fails, set to null and log the error
                \Log::info('Date parsing failed', [
                    'input' => $request->date_of_birth,
                    'error' => $e->getMessage()
                ]);
                $dateOfBirth = null;
            }
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'location' => $request->location,
            'bio' => $request->bio,
            'date_of_birth' => $dateOfBirth,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profiel succesvol bijgewerkt!'
        ]);
    }

    public function uploadCV(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
        ], [
            'cv.required' => 'CV bestand is verplicht',
            'cv.file' => 'CV moet een geldig bestand zijn',
            'cv.mimes' => 'CV moet een PDF, DOC of DOCX bestand zijn',
            'cv.max' => 'CV mag maximaal 10MB groot zijn',
        ]);

        $user = Auth::user();
        
        // Store new CV
        $file = $request->file('cv');
        $filename = 'cv_' . $user->id . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('cvs', $filename, 'public');

        // Create CV file record
        $cvFile = $user->cvFiles()->create([
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'CV succesvol geüpload!',
            'filename' => $file->getClientOriginalName(),
            'url' => url('/file/' . str_replace('/', '--', $path)),
            'cv_id' => $cvFile->id
        ]);
    }

    public function removeCV(Request $request)
    {
        $request->validate([
            'cv_id' => 'required|integer|exists:cv_files,id'
        ]);

        $user = Auth::user();
        $cvFile = $user->cvFiles()->findOrFail($request->cv_id);
        
        // Delete file from storage
        if (\Storage::disk('public')->exists($cvFile->file_path)) {
            \Storage::disk('public')->delete($cvFile->file_path);
        }
        
        // Also try direct file deletion as backup
        $fullPath = storage_path('app/public/' . $cvFile->file_path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Delete database record
        $cvFile->delete();

        return response()->json([
            'success' => true,
            'message' => 'CV succesvol verwijderd!'
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        try {
            // Set PHP upload limits programmatically
            ini_set('upload_max_filesize', '10M');
            ini_set('post_max_size', '20M');
            ini_set('max_execution_time', 300);
            ini_set('max_input_time', 300);
            ini_set('memory_limit', '256M');
            
            // Debug logging
            \Log::info('Photo upload attempt', [
                'has_file' => $request->hasFile('photo'),
                'all_files' => $request->allFiles(),
                'input_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'content_length' => $request->header('Content-Length'),
                'php_upload_max' => ini_get('upload_max_filesize'),
                'php_post_max' => ini_get('post_max_size'),
                'php_max_execution' => ini_get('max_execution_time')
            ]);
            
            // Check if file exists first - try multiple ways
            $photo = null;
            if ($request->hasFile('photo')) {
                $photo = $request->file('photo');
            } elseif ($request->hasFile('file')) {
                $photo = $request->file('file');
            } elseif (isset($_FILES['photo'])) {
                $photo = $request->file('photo');
            }
            
            if (!$photo) {
                \Log::error('No photo file found in request', [
                    'all_files' => $request->allFiles(),
                    'input_keys' => array_keys($request->all()),
                    'files_superglobal' => $_FILES,
                    'request_size' => $request->header('Content-Length'),
                    'php_upload_max' => ini_get('upload_max_filesize'),
                    'php_post_max' => ini_get('post_max_size')
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Er is geen foto geselecteerd. Debug: ' . json_encode($request->allFiles()) . ' | PHP Limits: upload_max=' . ini_get('upload_max_filesize') . ', post_max=' . ini_get('post_max_size')
                ], 422);
            }
            
            // Check if file is valid
            if (!$photo->isValid()) {
                $errorCode = $photo->getError();
                $errorMessages = [
                    0 => 'Geen fout',
                    1 => 'Bestand is te groot voor PHP upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
                    2 => 'Bestand is te groot voor HTML MAX_FILE_SIZE',
                    3 => 'Bestand is slechts gedeeltelijk geüpload',
                    4 => 'Geen bestand geüpload',
                    6 => 'Ontbrekende tijdelijke map',
                    7 => 'Schrijven naar schijf mislukt',
                    8 => 'PHP extensie stopte de upload'
                ];
                
                return response()->json([
                    'success' => false,
                    'message' => 'Upload fout: ' . ($errorMessages[$errorCode] ?? 'Onbekende fout (' . $errorCode . ')')
                ], 422);
            }
            
            // Manual validation
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
            if (!in_array($photo->getMimeType(), $allowedMimes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alleen JPEG, PNG, JPG, GIF, WEBP en SVG bestanden zijn toegestaan.'
                ], 422);
            }
            
            if ($photo->getSize() > 5 * 1024 * 1024) { // 5MB
                return response()->json([
                    'success' => false,
                    'message' => 'De foto mag maximaal 5MB groot zijn.'
                ], 422);
            }

            $user = Auth::user();
            
            // Get file content and MIME type
            $fileContent = file_get_contents($photo->getRealPath());
            $mimeType = $photo->getMimeType();
            
            // Store photo as BLOB in database
            try {
                $user->update([
                    'photo_blob' => base64_encode($fileContent),
                    'photo_mime_type' => $mimeType,
                    'photo' => null, // Clear old file path
                    'updated_at' => now() // Force update timestamp
                ]);
            } catch (\Exception $e) {
                \Log::error('Database storage error: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Er is een fout opgetreden bij het opslaan van de foto: ' . $e->getMessage()
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Foto succesvol geüpload!',
                'photo_url' => route('secure.photo', ['token' => $user->getPhotoToken()])
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validatie fout: ' . implode(', ', $e->errors()['photo'] ?? ['Onbekende validatie fout'])
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Photo upload error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het uploaden van de foto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addSkill(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:technical,soft'
        ]);

        $user = Auth::user();
        $skill = $user->skills()->create([
            'name' => $request->name,
            'type' => $request->type
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vaardigheid toegevoegd!',
            'skill' => [
                'id' => $skill->id,
                'name' => $skill->name,
                'type' => $skill->type,
                'created_at' => $skill->created_at,
                'updated_at' => $skill->updated_at
            ]
        ]);
    }

    public function removeSkill(Request $request, $skillId)
    {
        $user = Auth::user();
        $skill = $user->skills()->findOrFail($skillId);
        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vaardigheid verwijderd!'
        ]);
    }

    public function addExperience(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'current' => 'boolean'
        ], [
            'title.required' => 'Functietitel is verplicht',
            'company.required' => 'Bedrijf is verplicht',
            'start_date.required' => 'Startdatum is verplicht',
            'start_date.date' => 'Startdatum moet een geldige datum zijn',
            'end_date.date' => 'Einddatum moet een geldige datum zijn',
            'end_date.after' => 'Einddatum moet na de startdatum liggen',
            'description.max' => 'Beschrijving mag maximaal 1000 karakters bevatten'
        ]);

        $user = Auth::user();
        $experience = $user->experiences()->create([
            'title' => $request->title,
            'company' => $request->company,
            'start_date' => $request->start_date,
            'end_date' => $request->current ? null : $request->end_date,
            'description' => $request->description,
            'current' => $request->current ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Werkervaring toegevoegd!',
            'experience' => [
                'id' => $experience->id,
                'title' => $experience->title,
                'company' => $experience->company,
                'start_date' => $experience->start_date->format('Y-m-d'),
                'end_date' => $experience->end_date ? $experience->end_date->format('Y-m-d') : null,
                'description' => $experience->description,
                'current' => $experience->current,
                'created_at' => $experience->created_at,
                'updated_at' => $experience->updated_at
            ]
        ]);
    }

    public function showExperience(Request $request, $experienceId)
    {
        $user = Auth::user();
        $experience = $user->experiences()->findOrFail($experienceId);

        return response()->json([
            'success' => true,
            'experience' => [
                'id' => $experience->id,
                'title' => $experience->title,
                'company' => $experience->company,
                'start_date' => $experience->start_date->format('Y-m-d'),
                'end_date' => $experience->end_date ? $experience->end_date->format('Y-m-d') : null,
                'description' => $experience->description,
                'current' => $experience->current,
                'created_at' => $experience->created_at,
                'updated_at' => $experience->updated_at
            ]
        ]);
    }

    public function updateExperience(Request $request, $experienceId)
    {
        // Debug: Log incoming request data
        \Log::info('Update Experience Request Data', $request->all());
        \Log::info('Request method', ['method' => $request->method()]);
        \Log::info('Content type', ['content_type' => $request->header('Content-Type')]);
        \Log::info('Raw input', ['raw' => $request->getContent()]);
        
        // Debug individual fields
        \Log::info('Title', ['title' => $request->input('title')]);
        \Log::info('Company', ['company' => $request->input('company')]);
        \Log::info('Start date', ['start_date' => $request->input('start_date')]);
        \Log::info('End date', ['end_date' => $request->input('end_date')]);
        \Log::info('Current', ['current' => $request->input('current')]);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string|max:1000',
            'current' => 'nullable|boolean'
        ], [
            'title.required' => 'Functietitel is verplicht',
            'company.required' => 'Bedrijf is verplicht',
            'start_date.required' => 'Startdatum is verplicht',
            'start_date.date' => 'Startdatum moet een geldige datum zijn',
            'end_date.date' => 'Einddatum moet een geldige datum zijn',
            'end_date.after' => 'Einddatum moet na de startdatum liggen',
            'description.max' => 'Beschrijving mag maximaal 1000 karakters bevatten'
        ]);

        $user = Auth::user();
        $experience = $user->experiences()->findOrFail($experienceId);
        
        $experience->update([
            'title' => $request->title,
            'company' => $request->company,
            'start_date' => $request->start_date,
            'end_date' => $request->current ? null : $request->end_date,
            'description' => $request->description,
            'current' => $request->current ?? false
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Werkervaring bijgewerkt!',
            'experience' => [
                'id' => $experience->id,
                'title' => $experience->title,
                'company' => $experience->company,
                'start_date' => $experience->start_date->format('Y-m-d'),
                'end_date' => $experience->end_date ? $experience->end_date->format('Y-m-d') : null,
                'description' => $experience->description,
                'current' => $experience->current,
                'created_at' => $experience->created_at,
                'updated_at' => $experience->updated_at
            ]
        ]);
    }

    public function removeExperience(Request $request, $experienceId)
    {
        $user = Auth::user();
        $experience = $user->experiences()->findOrFail($experienceId);
        $experience->delete();

        return response()->json([
            'success' => true,
            'message' => 'Werkervaring verwijderd!'
        ]);
    }
}
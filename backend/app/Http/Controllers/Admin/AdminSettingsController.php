<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EnvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminSettingsController extends Controller
{
    protected $envService;

    public function __construct(EnvService $envService)
    {
        $this->envService = $envService;
    }
    
    /**
     * Check if user is super-admin
     */
    protected function ensureSuperAdmin()
    {
        if (!auth()->check() || !auth()->user()->hasRole('super-admin')) {
            abort(403, 'Je hebt geen rechten om deze pagina te bekijken. Alleen super-admins hebben toegang tot instellingen.');
        }
    }

    /**
     * Display the settings page
     * Alleen toegankelijk voor super-admin
     */
    public function index()
    {
        $this->ensureSuperAdmin();
        
        // Get current mail settings
        $mailSettings = [
            'MAIL_MAILER' => $this->envService->get('MAIL_MAILER', 'log'),
            'MAIL_HOST' => $this->envService->get('MAIL_HOST', ''),
            'MAIL_PORT' => $this->envService->get('MAIL_PORT', '587'),
            'MAIL_USERNAME' => $this->envService->get('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => $this->envService->get('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => $this->envService->get('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => $this->envService->get('MAIL_FROM_ADDRESS', 'noreply@nexa-skillmatching.nl'),
            'MAIL_FROM_NAME' => $this->envService->get('MAIL_FROM_NAME', 'NEXA Skillmatching'),
        ];

        return view('admin.settings.index', compact('mailSettings'));
    }

    /**
     * Update mail settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateMail(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'MAIL_MAILER' => 'required|in:log,smtp,sendmail,mailgun,ses,postmark,resend',
            'MAIL_HOST' => 'required_if:MAIL_MAILER,smtp|nullable|string|max:255',
            'MAIL_PORT' => 'required_if:MAIL_MAILER,smtp|nullable|integer|min:1|max:65535',
            'MAIL_USERNAME' => 'nullable|string|max:255',
            'MAIL_PASSWORD' => 'nullable|string|max:255',
            'MAIL_ENCRYPTION' => 'nullable|in:tls,ssl,null',
            'MAIL_FROM_ADDRESS' => 'required|email|max:255',
            'MAIL_FROM_NAME' => 'required|string|max:255',
        ], [
            'MAIL_MAILER.required' => 'Mailer is verplicht.',
            'MAIL_MAILER.in' => 'Ongeldige mailer geselecteerd.',
            'MAIL_HOST.required_if' => 'SMTP host is verplicht wanneer SMTP mailer is geselecteerd.',
            'MAIL_PORT.required_if' => 'SMTP poort is verplicht wanneer SMTP mailer is geselecteerd.',
            'MAIL_FROM_ADDRESS.required' => 'From adres is verplicht.',
            'MAIL_FROM_ADDRESS.email' => 'From adres moet een geldig e-mailadres zijn.',
            'MAIL_FROM_NAME.required' => 'From naam is verplicht.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $mailSettings = [
                'MAIL_MAILER' => $request->input('MAIL_MAILER'),
                'MAIL_HOST' => $request->input('MAIL_HOST', ''),
                'MAIL_PORT' => $request->input('MAIL_PORT', '587'),
                'MAIL_USERNAME' => $request->input('MAIL_USERNAME', ''),
                'MAIL_PASSWORD' => $request->input('MAIL_PASSWORD', ''),
                'MAIL_ENCRYPTION' => $request->input('MAIL_ENCRYPTION', 'tls'),
                'MAIL_FROM_ADDRESS' => $request->input('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => $request->input('MAIL_FROM_NAME'),
            ];

            // Only update password if it's provided (not empty)
            if (empty($request->input('MAIL_PASSWORD'))) {
                unset($mailSettings['MAIL_PASSWORD']);
            }

            $this->envService->set($mailSettings);

            return redirect()->route('admin.settings.index')
                ->with('success', 'Mail instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Test email connection
     * Alleen toegankelijk voor super-admin
     */
    public function testEmail(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ongeldig e-mailadres.',
            ], 422);
        }

        try {
            \Mail::raw('Dit is een test email van NEXA Skillmatching. Als je dit bericht ontvangt, werkt de mailserver correct!', function ($message) use ($request) {
                $message->to($request->input('test_email'))
                    ->subject('Test Email - NEXA Skillmatching')
                    ->from(config('mail.from.address', 'noreply@nexa-skillmatching.nl'), config('mail.from.name', 'NEXA Skillmatching'));
            });

            $mailer = config('mail.default');
            if ($mailer === 'log') {
                return response()->json([
                    'success' => true,
                    'message' => 'Email is gelogd. Check storage/logs/laravel.log voor de inhoud. (Mailer staat op "log" mode)',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test email succesvol verzonden naar ' . $request->input('test_email') . '!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het verzenden: ' . $e->getMessage(),
            ], 500);
        }
    }
}


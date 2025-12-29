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

        // Get current SEO settings
        $seoSettings = [
            'GOOGLE_SEO_PROPERTY_ID' => $this->envService->get('GOOGLE_SEO_PROPERTY_ID', ''),
            'GOOGLE_ANALYTICS_ID' => $this->envService->get('GOOGLE_ANALYTICS_ID', ''),
            'GOOGLE_TAG_MANAGER_ID' => $this->envService->get('GOOGLE_TAG_MANAGER_ID', ''),
            'META_DESCRIPTION' => $this->envService->get('META_DESCRIPTION', ''),
            'META_KEYWORDS' => $this->envService->get('META_KEYWORDS', ''),
            'GOOGLE_SITE_VERIFICATION' => $this->envService->get('GOOGLE_SITE_VERIFICATION', ''),
        ];

        // Get current Maps settings
        $mapsSettings = [
            'GOOGLE_MAPS_API_KEY' => $this->envService->get('GOOGLE_MAPS_API_KEY', ''),
            'GOOGLE_MAPS_ZOOM' => $this->envService->get('GOOGLE_MAPS_ZOOM', '12'),
            'GOOGLE_MAPS_CENTER_LAT' => $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
            'GOOGLE_MAPS_CENTER_LNG' => $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
            'GOOGLE_MAPS_TYPE' => $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap'),
        ];

        // Get current WhatsApp settings
        $whatsappSettings = [
            'WHATSAPP_API_TOKEN' => $this->envService->get('WHATSAPP_API_TOKEN', ''),
            'WHATSAPP_PHONE_NUMBER_ID' => $this->envService->get('WHATSAPP_PHONE_NUMBER_ID', ''),
            'WHATSAPP_BUSINESS_ACCOUNT_ID' => $this->envService->get('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
            'WHATSAPP_API_VERSION' => $this->envService->get('WHATSAPP_API_VERSION', 'v18.0'),
            'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => $this->envService->get('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
            'WHATSAPP_DEFAULT_MESSAGE' => $this->envService->get('WHATSAPP_DEFAULT_MESSAGE', ''),
        ];

        return view('admin.settings.index', compact('mailSettings', 'seoSettings', 'mapsSettings', 'whatsappSettings'));
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
            // Haal SMTP username op voor envelope sender
            // De envelope sender (SMTP MAIL FROM) moet overeenkomen met de SMTP authenticatie gebruiker
            // om te voorkomen dat de mailserver de verzending weigert
            $smtpUsername = $this->envService->get('MAIL_USERNAME', '');
            $configuredFromAddress = $this->envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
            $fromName = $this->envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));
            
            // Gebruik SMTP username als from address als deze beschikbaar is EN verschilt van configured address
            // Dit voorkomt "not authorized to send on behalf of" errors wanneer de server dit niet toestaat
            // Als SMTP username niet beschikbaar is of gelijk is aan configured address, gebruik de configured from address
            $fromAddress = (!empty($smtpUsername) && $smtpUsername !== $configuredFromAddress) ? $smtpUsername : $configuredFromAddress;
            
            \Mail::raw('Dit is een test email van NEXA Skillmatching. Als je dit bericht ontvangt, werkt de mailserver correct!', function ($message) use ($request, $fromAddress, $fromName) {
                $message->to($request->input('test_email'))
                    ->subject('Test Email - NEXA Skillmatching')
                    ->from($fromAddress, $fromName);
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

    /**
     * Update SEO settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateSeo(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'GOOGLE_SEO_PROPERTY_ID' => 'nullable|string|max:255',
            'GOOGLE_ANALYTICS_ID' => 'nullable|string|max:255',
            'GOOGLE_TAG_MANAGER_ID' => 'nullable|string|max:255',
            'META_DESCRIPTION' => 'nullable|string|max:500',
            'META_KEYWORDS' => 'nullable|string|max:500',
            'GOOGLE_SITE_VERIFICATION' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $seoSettings = [
                'GOOGLE_SEO_PROPERTY_ID' => $request->input('GOOGLE_SEO_PROPERTY_ID', ''),
                'GOOGLE_ANALYTICS_ID' => $request->input('GOOGLE_ANALYTICS_ID', ''),
                'GOOGLE_TAG_MANAGER_ID' => $request->input('GOOGLE_TAG_MANAGER_ID', ''),
                'META_DESCRIPTION' => $request->input('META_DESCRIPTION', ''),
                'META_KEYWORDS' => $request->input('META_KEYWORDS', ''),
                'GOOGLE_SITE_VERIFICATION' => $request->input('GOOGLE_SITE_VERIFICATION', ''),
            ];

            $this->envService->set($seoSettings);

            return redirect()->route('admin.settings.index')
                ->with('success', 'SEO instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update Google Maps settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateMaps(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'GOOGLE_MAPS_API_KEY' => 'required|string|max:255',
            'GOOGLE_MAPS_ZOOM' => 'nullable|integer|min:1|max:20',
            'GOOGLE_MAPS_CENTER_LAT' => 'nullable|numeric|between:-90,90',
            'GOOGLE_MAPS_CENTER_LNG' => 'nullable|numeric|between:-180,180',
            'GOOGLE_MAPS_TYPE' => 'nullable|in:roadmap,satellite,hybrid,terrain',
        ], [
            'GOOGLE_MAPS_API_KEY.required' => 'Google Maps API Key is verplicht.',
            'GOOGLE_MAPS_ZOOM.integer' => 'Zoom level moet een getal zijn tussen 1 en 20.',
            'GOOGLE_MAPS_ZOOM.min' => 'Zoom level moet minimaal 1 zijn.',
            'GOOGLE_MAPS_ZOOM.max' => 'Zoom level mag maximaal 20 zijn.',
            'GOOGLE_MAPS_CENTER_LAT.numeric' => 'Latitude moet een geldig getal zijn.',
            'GOOGLE_MAPS_CENTER_LAT.between' => 'Latitude moet tussen -90 en 90 liggen.',
            'GOOGLE_MAPS_CENTER_LNG.numeric' => 'Longitude moet een geldig getal zijn.',
            'GOOGLE_MAPS_CENTER_LNG.between' => 'Longitude moet tussen -180 en 180 liggen.',
            'GOOGLE_MAPS_TYPE.in' => 'Ongeldig kaart type geselecteerd.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $mapsSettings = [
                'GOOGLE_MAPS_API_KEY' => $request->input('GOOGLE_MAPS_API_KEY'),
                'GOOGLE_MAPS_ZOOM' => $request->input('GOOGLE_MAPS_ZOOM', '12'),
                'GOOGLE_MAPS_CENTER_LAT' => $request->input('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
                'GOOGLE_MAPS_CENTER_LNG' => $request->input('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
                'GOOGLE_MAPS_TYPE' => $request->input('GOOGLE_MAPS_TYPE', 'roadmap'),
            ];

            $this->envService->set($mapsSettings);

            return redirect()->route('admin.settings.index')
                ->with('success', 'Google Maps instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update WhatsApp Business settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateWhatsapp(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'WHATSAPP_API_TOKEN' => 'nullable|string|max:500',
            'WHATSAPP_PHONE_NUMBER_ID' => 'nullable|string|max:255',
            'WHATSAPP_BUSINESS_ACCOUNT_ID' => 'nullable|string|max:255',
            'WHATSAPP_API_VERSION' => 'nullable|string|max:50',
            'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => 'nullable|string|max:255',
            'WHATSAPP_DEFAULT_MESSAGE' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $whatsappSettings = [
                'WHATSAPP_API_TOKEN' => $request->input('WHATSAPP_API_TOKEN', ''),
                'WHATSAPP_PHONE_NUMBER_ID' => $request->input('WHATSAPP_PHONE_NUMBER_ID', ''),
                'WHATSAPP_BUSINESS_ACCOUNT_ID' => $request->input('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
                'WHATSAPP_API_VERSION' => $request->input('WHATSAPP_API_VERSION', 'v18.0'),
                'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => $request->input('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
                'WHATSAPP_DEFAULT_MESSAGE' => $request->input('WHATSAPP_DEFAULT_MESSAGE', ''),
            ];

            $this->envService->set($whatsappSettings);

            return redirect()->route('admin.settings.index')
                ->with('success', 'WhatsApp Business instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }
}


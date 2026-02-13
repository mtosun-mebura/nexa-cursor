<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\ComingSoonController;
use App\Services\EnvService;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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

    /**
     * Update Coming Soon pagina-instellingen (getoond wanneer geen actieve module)
     */
    public function updateComingSoon(Request $request)
    {
        $this->ensureSuperAdmin();

        $validator = Validator::make($request->all(), [
            'coming_soon_title' => 'required|string|max:255',
            'coming_soon_text' => 'required|string|max:1000',
            'coming_soon_secondary_text' => 'nullable|string|max:500',
            'coming_soon_show_email' => 'nullable|in:0,1',
            'coming_soon_contact_email' => 'nullable|email|max:255',
            'coming_soon_contact_label' => 'nullable|string|max:100',
            'coming_soon_footer_text' => 'nullable|string|max:500',
        ], [
            'coming_soon_title.required' => 'Titel is verplicht.',
            'coming_soon_text.required' => 'Tekst is verplicht.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.frontend.index')
                ->withErrors($validator)
                ->withInput();
        }

        GeneralSetting::set('coming_soon_title', $request->input('coming_soon_title'));
        GeneralSetting::set('coming_soon_text', $request->input('coming_soon_text'));
        GeneralSetting::set('coming_soon_secondary_text', $request->input('coming_soon_secondary_text', ''));
        GeneralSetting::set('coming_soon_show_email', $request->has('coming_soon_show_email') && $request->input('coming_soon_show_email') ? '1' : '0');
        GeneralSetting::set('coming_soon_contact_email', $request->input('coming_soon_contact_email', ''));
        GeneralSetting::set('coming_soon_contact_label', $request->input('coming_soon_contact_label', 'E-mail'));
        GeneralSetting::set('coming_soon_footer_text', $request->input('coming_soon_footer_text', '© {year} {site}. Binnenkort beschikbaar.'));

        return redirect()->route('admin.settings.frontend.index')
            ->with('success', 'Coming soon-instellingen opgeslagen. Deze pagina wordt getoond zolang er geen actieve module is.');
    }

    /**
     * Front-end configuraties (Coming Soon en overige frontend-instellingen)
     * Alleen toegankelijk voor super-admin
     */
    public function frontendIndex()
    {
        $this->ensureSuperAdmin();

        $comingSoonSettings = [
            'coming_soon_title' => GeneralSetting::get('coming_soon_title', 'We zijn bijna live'),
            'coming_soon_text' => GeneralSetting::get('coming_soon_text', 'Onze website wordt op dit moment voor u klaargemaakt. Binnenkort vindt u hier alle informatie en mogelijkheden.'),
            'coming_soon_secondary_text' => GeneralSetting::get('coming_soon_secondary_text', 'Heeft u vragen? Neem gerust contact met ons op.'),
            'coming_soon_show_email' => GeneralSetting::get('coming_soon_show_email', '1'),
            'coming_soon_contact_email' => GeneralSetting::get('coming_soon_contact_email', ''),
            'coming_soon_contact_label' => GeneralSetting::get('coming_soon_contact_label', 'E-mail'),
            'coming_soon_footer_text' => GeneralSetting::get('coming_soon_footer_text', '© {year} {site}. Binnenkort beschikbaar.'),
        ];

        return view('admin.settings.frontend', compact('comingSoonSettings'));
    }

    /**
     * Preview van de Coming Soon-pagina zoals bezoekers die zien (super-admin only).
     */
    public function frontendComingSoonPreview()
    {
        $this->ensureSuperAdmin();

        $settings = ComingSoonController::getSettings();
        $showEmail = !empty($settings['coming_soon_show_email']) && $settings['coming_soon_show_email'] !== '0';
        $contactEmail = $settings['coming_soon_contact_email'] ?? '';

        return view('frontend.coming-soon', [
            'settings' => $settings,
            'showEmail' => $showEmail,
            'contactEmail' => $contactEmail,
        ]);
    }

    /**
     * Display general settings page
     * Alleen toegankelijk voor super-admin
     */
    public function generalIndex()
    {
        $this->ensureSuperAdmin();
        
        $logo = GeneralSetting::get('logo');
        $favicon = GeneralSetting::get('favicon');
        $logoSize = GeneralSetting::get('logo_size', '26');
        $siteName = GeneralSetting::get('site_name', config('app.name'));
        $siteDescription = GeneralSetting::get('site_description', '');
        $dashboardLinkLabel = GeneralSetting::get('dashboard_link_label', 'Mijn Nexa');
        
        // Verify files exist
        if ($logo && !Storage::disk('public')->exists($logo)) {
            \Log::warning('Logo file not found in storage', ['path' => $logo]);
            $logo = null;
        }
        
        if ($favicon && !Storage::disk('public')->exists($favicon)) {
            \Log::warning('Favicon file not found in storage', ['path' => $favicon]);
            $favicon = null;
        }
        
        return view('admin.settings.general', compact('logo', 'favicon', 'logoSize', 'siteName', 'siteDescription', 'dashboardLinkLabel'));
    }

    /**
     * Update general settings
     * Alleen toegankelijk voor super-admin
     */
    public function generalUpdate(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $validator = Validator::make($request->all(), [
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'dashboard_link_label' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg|max:2048',
            'logo_size' => 'nullable|integer|min:10|max:100',
        ], [
            'logo.image' => 'Logo moet een afbeelding zijn.',
            'logo.mimes' => 'Logo moet een jpeg, png, jpg, gif of svg bestand zijn.',
            'logo.max' => 'Logo mag maximaal 2MB groot zijn.',
            'favicon.image' => 'Favicon moet een afbeelding zijn.',
            'favicon.mimes' => 'Favicon moet een ico, png of jpg bestand zijn.',
            'favicon.max' => 'Favicon mag maximaal 2MB groot zijn.',
            'logo_size.integer' => 'Logo grootte moet een getal zijn.',
            'logo_size.min' => 'Logo grootte moet minimaal 10px zijn.',
            'logo_size.max' => 'Logo grootte mag maximaal 100px zijn.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.general.index')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Applicatienaam en omschrijving
            if ($request->has('site_name')) {
                GeneralSetting::set('site_name', $request->input('site_name', ''));
            }
            if ($request->has('site_description')) {
                GeneralSetting::set('site_description', $request->input('site_description', ''));
            }
            if ($request->has('dashboard_link_label')) {
                GeneralSetting::set('dashboard_link_label', $request->input('dashboard_link_label', ''));
            }

            // Ensure settings directory exists
            $settingsDir = storage_path('app/public/settings');
            if (!file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }
            
            // Handle logo upload (only if not already uploaded via AJAX)
            if ($request->hasFile('logo') && !$request->ajax()) {
                $logoFile = $request->file('logo');
                
                // Delete old logo if exists
                $oldLogo = GeneralSetting::get('logo');
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }
                
                // Store new logo
                $logoPath = $logoFile->store('settings', 'public');
                
                // Verify file was stored
                if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
                    \Log::error('Logo storage failed', [
                        'path' => $logoPath,
                        'file_exists' => $logoPath ? Storage::disk('public')->exists($logoPath) : false,
                        'storage_path' => storage_path('app/public'),
                        'settings_dir_exists' => file_exists($settingsDir),
                    ]);
                    throw new \Exception('Logo bestand kon niet worden opgeslagen. Controleer de storage permissies.');
                }
                
                // Save path to database
                GeneralSetting::set('logo', $logoPath);
                
                \Log::info('Logo uploaded successfully', [
                    'path' => $logoPath,
                    'full_path' => storage_path('app/public/' . $logoPath),
                    'file_exists' => Storage::disk('public')->exists($logoPath),
                ]);
            }

            // Handle favicon upload (only if not already uploaded via AJAX)
            if ($request->hasFile('favicon') && !$request->ajax()) {
                $faviconFile = $request->file('favicon');
                
                // Delete old favicon if exists
                $oldFavicon = GeneralSetting::get('favicon');
                if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                    Storage::disk('public')->delete($oldFavicon);
                }
                
                // Store new favicon
                $faviconPath = $faviconFile->store('settings', 'public');
                
                // Verify file was stored
                if (!$faviconPath || !Storage::disk('public')->exists($faviconPath)) {
                    \Log::error('Favicon storage failed', [
                        'path' => $faviconPath,
                        'file_exists' => $faviconPath ? Storage::disk('public')->exists($faviconPath) : false,
                        'storage_path' => storage_path('app/public'),
                        'settings_dir_exists' => file_exists($settingsDir),
                    ]);
                    throw new \Exception('Favicon bestand kon niet worden opgeslagen. Controleer de storage permissies.');
                }
                
                // Save path to database
                GeneralSetting::set('favicon', $faviconPath);
                
                \Log::info('Favicon uploaded successfully', [
                    'path' => $faviconPath,
                    'full_path' => storage_path('app/public/' . $faviconPath),
                    'file_exists' => Storage::disk('public')->exists($faviconPath),
                ]);
            }

            // Update logo size
            if ($request->has('logo_size')) {
                GeneralSetting::set('logo_size', $request->input('logo_size'));
            }

            return redirect()->route('admin.settings.general.index')
                ->with('success', 'Algemene configuraties succesvol bijgewerkt!');
        } catch (\Exception $e) {
            \Log::error('Error updating general settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('admin.settings.general.index')
                ->with('error', 'Er is een fout opgetreden: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload logo immediately via AJAX
     */
    public function uploadLogo(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $request->validate([
            'logo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'logo.required' => 'Selecteer een logo bestand.',
            'logo.file' => 'Het bestand moet een geldig bestand zijn.',
            'logo.mimes' => 'Alleen JPEG, PNG, JPG, GIF en SVG bestanden zijn toegestaan.',
            'logo.max' => 'Het bestand mag maximaal 2MB groot zijn.',
        ]);

        try {
            // Ensure settings directory exists
            $settingsDir = storage_path('app/public/settings');
            if (!file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }
            
            $logoFile = $request->file('logo');
            
            // Delete old logo if exists
            $oldLogo = GeneralSetting::get('logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }
            
            // Store new logo
            $logoPath = $logoFile->store('settings', 'public');
            
            // Verify file was stored
            if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
                \Log::error('Logo storage failed', [
                    'path' => $logoPath,
                    'file_exists' => $logoPath ? Storage::disk('public')->exists($logoPath) : false,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Logo bestand kon niet worden opgeslagen. Controleer de storage permissies.'
                ], 500);
            }
            
            // Save path to database
            GeneralSetting::set('logo', $logoPath);
            
            \Log::info('Logo uploaded successfully', ['path' => $logoPath]);
            
            return response()->json([
                'success' => true,
                'message' => 'Logo succesvol geüpload.',
                'logo_url' => route('admin.settings.logo') . '?t=' . time()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading logo', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload favicon immediately via AJAX
     */
    public function uploadFavicon(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $request->validate([
            'favicon' => 'required|file|mimes:ico,png,jpg|max:2048',
        ], [
            'favicon.required' => 'Selecteer een favicon bestand.',
            'favicon.file' => 'Het bestand moet een geldig bestand zijn.',
            'favicon.mimes' => 'Alleen ICO, PNG en JPG bestanden zijn toegestaan.',
            'favicon.max' => 'Het bestand mag maximaal 2MB groot zijn.',
        ]);

        try {
            // Ensure settings directory exists
            $settingsDir = storage_path('app/public/settings');
            if (!file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }
            
            $faviconFile = $request->file('favicon');
            
            // Delete old favicon if exists
            $oldFavicon = GeneralSetting::get('favicon');
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }
            
            // Store new favicon
            $faviconPath = $faviconFile->store('settings', 'public');
            
            // Verify file was stored
            if (!$faviconPath || !Storage::disk('public')->exists($faviconPath)) {
                \Log::error('Favicon storage failed', [
                    'path' => $faviconPath,
                    'file_exists' => $faviconPath ? Storage::disk('public')->exists($faviconPath) : false,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Favicon bestand kon niet worden opgeslagen. Controleer de storage permissies.'
                ], 500);
            }
            
            // Save path to database
            GeneralSetting::set('favicon', $faviconPath);
            
            \Log::info('Favicon uploaded successfully', ['path' => $faviconPath]);
            
            return response()->json([
                'success' => true,
                'message' => 'Favicon succesvol geüpload.',
                'favicon_url' => route('admin.settings.favicon') . '?t=' . time()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading favicon', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get logo file
     */
    public function getLogo()
    {
        $this->ensureSuperAdmin();
        
        $logoPath = GeneralSetting::get('logo');
        
        if (!$logoPath || !Storage::disk('public')->exists($logoPath)) {
            abort(404, 'Logo niet gevonden');
        }
        
        $file = Storage::disk('public')->get($logoPath);
        $mimeType = Storage::disk('public')->mimeType($logoPath);
        
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="logo"');
    }

    /**
     * Get favicon file
     */
    public function getFavicon()
    {
        $this->ensureSuperAdmin();
        
        $faviconPath = GeneralSetting::get('favicon');
        
        if (!$faviconPath || !Storage::disk('public')->exists($faviconPath)) {
            abort(404, 'Favicon niet gevonden');
        }
        
        $file = Storage::disk('public')->get($faviconPath);
        $mimeType = Storage::disk('public')->mimeType($faviconPath);
        
        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="favicon"');
    }

    /**
     * Update logo size immediately via AJAX
     */
    public function updateLogoSize(Request $request)
    {
        $this->ensureSuperAdmin();
        
        $request->validate([
            'logo_size' => 'required|integer|min:10|max:100',
        ], [
            'logo_size.required' => 'Logo grootte is verplicht.',
            'logo_size.integer' => 'Logo grootte moet een getal zijn.',
            'logo_size.min' => 'Logo grootte moet minimaal 10px zijn.',
            'logo_size.max' => 'Logo grootte mag maximaal 100px zijn.',
        ]);

        try {
            $logoSize = $request->input('logo_size');
            GeneralSetting::set('logo_size', $logoSize);
            
            \Log::info('Logo size updated', ['size' => $logoSize]);
            
            return response()->json([
                'success' => true,
                'message' => 'Logo grootte succesvol bijgewerkt.',
                'logo_size' => $logoSize
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating logo size', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: ' . $e->getMessage()
            ], 500);
        }
    }
}


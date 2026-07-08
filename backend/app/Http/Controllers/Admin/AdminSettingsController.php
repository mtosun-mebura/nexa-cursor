<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\ComingSoonController;
use App\Models\Company;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Models\Module;
use App\Services\AiChatAssistantService;
use App\Services\EnvService;
use App\Services\GoogleReviewsService;
use App\Services\GoogleSearchConsoleService;
use App\Services\GoogleSeoSettingsService;
use App\Services\TenantCompanyDataPushService;
use App\Services\TenantStorageBundleService;
use App\Services\TenantSyncSettingsService;
use App\Services\TenantWebsiteBundleService;
use App\Services\InfoRequestFormPreviewContextService;
use App\Services\WebsiteBuilderService;
use App\Support\DutchPhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSettingsController extends Controller
{
    protected $envService;

    protected TenantWebsiteBundleService $tenantWebsiteBundle;

    protected TenantCompanyDataPushService $tenantCompanyDataPush;

    protected TenantStorageBundleService $tenantStorageBundle;

    protected TenantSyncSettingsService $tenantSyncSettings;

    protected GoogleSeoSettingsService $googleSeoSettings;

    protected GoogleSearchConsoleService $googleSearchConsole;

    public function __construct(
        EnvService $envService,
        TenantWebsiteBundleService $tenantWebsiteBundle,
        TenantCompanyDataPushService $tenantCompanyDataPush,
        TenantStorageBundleService $tenantStorageBundle,
        TenantSyncSettingsService $tenantSyncSettings,
        GoogleSeoSettingsService $googleSeoSettings,
        GoogleSearchConsoleService $googleSearchConsole,
    ) {
        $this->envService = $envService;
        $this->tenantWebsiteBundle = $tenantWebsiteBundle;
        $this->tenantCompanyDataPush = $tenantCompanyDataPush;
        $this->tenantStorageBundle = $tenantStorageBundle;
        $this->tenantSyncSettings = $tenantSyncSettings;
        $this->googleSeoSettings = $googleSeoSettings;
        $this->googleSearchConsole = $googleSearchConsole;
    }

    /**
     * Check if user is super-admin
     */
    protected function ensureSuperAdmin()
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Je hebt geen rechten om deze pagina te bekijken. Alleen super-admins hebben toegang tot instellingen.');
        }
    }

    /**
     * Actieve tenant (company_id) voor per-tenant configuratie in admin.
     * Super-admin: sessie selected_tenant; overige admins: company_id van de gebruiker.
     */
    protected function settingsCompanyId(): ?int
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        if ($user->hasRole('super-admin')) {
            $st = session('selected_tenant');
            if ($st !== null && $st !== '' && is_numeric($st)) {
                $id = (int) $st;

                return Company::query()->whereKey($id)->exists() ? $id : null;
            }

            return null;
        }

        return $user->company_id ? (int) $user->company_id : null;
    }

    /**
     * Redirect wanneer er geen tenant-context is voor instellingen die per company worden opgeslagen.
     */
    protected function requireSettingsTenantOrRedirect(): ?\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($this->settingsCompanyId() === null) {
            $message = $user->hasRole('super-admin')
                ? 'Selecteer eerst een tenant (bedrijf) in de zijbalk om per-tenant configuraties te bewerken.'
                : 'Geen bedrijf gekoppeld aan dit account.';

            return redirect()->route('admin.settings.index')->with('settings_tenant_save_notice', $message);
        }

        return null;
    }

    /**
     * JSON-fout wanneer AJAX-instellingen geen tenant-context hebben.
     */
    protected function jsonTenantRequired(): ?\Illuminate\Http\JsonResponse
    {
        if ($this->settingsCompanyId() !== null) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => auth()->user()?->hasRole('super-admin')
                ? 'Selecteer eerst een tenant (bedrijf) in de zijbalk.'
                : 'Geen bedrijf gekoppeld aan dit account.',
        ], 422);
    }

    /**
     * Display the settings page
     * Alleen toegankelijk voor super-admin
     */
    public function index()
    {
        $this->ensureSuperAdmin();

        $settingsCompanyId = $this->settingsCompanyId();
        $tenantScopedSettingsActive = $settingsCompanyId !== null;

        // Get current mail settings (EnvService reads from GeneralSetting first for these keys)
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

        // Get current SEO settings (tenant + platform fallback via GeneralSetting)
        $seoSettings = $this->googleSeoSettings->formSettings($settingsCompanyId);

        // Get current Maps settings (zelfde bron als overal elders: Admin → Instellingen → Maps)
        $mapsSettings = [
            'GOOGLE_MAPS_API_KEY' => $this->envService->getGoogleMapsApiKey(),
            'GOOGLE_MAPS_MAP_ID' => $this->envService->getGoogleMapsMapId(),
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
            'WHATSAPP_CLICK_TO_CHAT_ENABLED' => $this->envService->get('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0'),
            'WHATSAPP_CLICK_TO_CHAT_NUMBER' => $this->envService->get('WHATSAPP_CLICK_TO_CHAT_NUMBER', ''),
            'WHATSAPP_WIDGET_ENABLED' => $this->envService->get('WHATSAPP_WIDGET_ENABLED', '0'),
            'WHATSAPP_WIDGET_PHONE' => $this->envService->get('WHATSAPP_WIDGET_PHONE', ''),
            'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => $this->envService->get('WHATSAPP_WIDGET_DEFAULT_MESSAGE', 'Hallo, ik heb een vraag over jullie diensten.'),
        ];

        $googleReviewsPlaceId = GeneralSetting::get('google_reviews_place_id', '');
        $googleReviewsBusinessName = GeneralSetting::get('google_reviews_business_name', '');
        $googleReviewsCacheHours = (string) max(1, min(168, (int) GeneralSetting::get('google_reviews_cache_hours', '24')));
        $googleReviewsCount = (int) GeneralSetting::get('google_reviews_count', '5');
        $googleReviewsCount = max(1, min(20, $googleReviewsCount));
        $googleReviewsMinStars = (int) GeneralSetting::get('google_reviews_min_stars', '1');
        $googleReviewsMinStars = max(1, min(5, $googleReviewsMinStars));
        $googleReviewsSectionTitle = trim((string) GeneralSetting::get('google_reviews_section_title', ''));
        $googleReviewsSectionBackground = GoogleReviewsService::normalizeHexColor(
            trim((string) GeneralSetting::get('google_reviews_section_background', ''))
        );

        $companiesForSync = Company::query()->orderBy('name')->get(['id', 'name']);

        $tenantSyncScope = $this->tenantCompanyDataPush->describeSyncScope();

        $tenantSyncTargets = $this->tenantSyncSettings->targets();
        $tenantSyncActiveTarget = $this->tenantSyncSettings->activeTarget();
        $tenantSyncSettings = $this->tenantSyncSettings->formSettings($tenantSyncActiveTarget);

        $tenantSyncTargetDatabaseUrlPrefill = $this->tenantWebsiteBundle->suggestedTargetDatabaseUrl();

        return view('admin.settings.index', compact(
            'mailSettings',
            'seoSettings',
            'mapsSettings',
            'whatsappSettings',
            'googleReviewsPlaceId',
            'googleReviewsBusinessName',
            'googleReviewsCacheHours',
            'googleReviewsCount',
            'googleReviewsMinStars',
            'googleReviewsSectionTitle',
            'googleReviewsSectionBackground',
            'tenantSyncSettings',
            'tenantSyncTargets',
            'tenantSyncActiveTarget',
            'tenantSyncTargetDatabaseUrlPrefill',
            'companiesForSync',
            'tenantSyncScope',
            'settingsCompanyId',
            'tenantScopedSettingsActive',
        ));
    }

    /**
     * Doel-database voor website-push (PROD) + vlag om push toe te staan.
     */
    public function updateTenantSync(Request $request)
    {
        $this->ensureSuperAdmin();

        $this->mergeTenantSyncDefaults($request);

        $redirectBase = route('admin.settings.index');
        $redirectTo = $redirectBase.'#tenant-sync';

        try {
            $this->tenantSyncSettings->validateRequest($request);
            $target = $this->tenantSyncSettings->saveFromRequest($request);

            return redirect()->to($redirectBase.'?saved=1#tenant-sync')
                ->with('success', 'Omgeving "'.$target->name.'" opgeslagen.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e->redirectTo($redirectTo);
        } catch (\Throwable $e) {
            return redirect()->to($redirectTo)
                ->with('error', 'Opslaan mislukt: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Voeg een nieuwe (lege) doel-omgeving toe en maak die actief.
     */
    public function createTenantSyncTarget(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $target = $this->tenantSyncSettings->createTarget($validated['name'] ?? null);

        return redirect()->to(route('admin.settings.index').'#tenant-sync')
            ->with('success', 'Nieuwe omgeving "'.$target->name.'" toegevoegd.');
    }

    /**
     * Kies naar welke omgeving er gesynchroniseerd wordt.
     */
    public function activateTenantSyncTarget(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'tenant_sync_target_id' => ['required', 'integer'],
        ]);

        $this->tenantSyncSettings->activate((int) $validated['tenant_sync_target_id']);

        return redirect()->to(route('admin.settings.index').'#tenant-sync');
    }

    /**
     * Verwijder een doel-omgeving.
     */
    public function deleteTenantSyncTarget(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'tenant_sync_target_id' => ['required', 'integer'],
        ]);

        $this->tenantSyncSettings->deleteTarget((int) $validated['tenant_sync_target_id']);

        return redirect()->to(route('admin.settings.index').'#tenant-sync')
            ->with('success', 'Omgeving verwijderd.');
    }

    private function mergeTenantSyncDefaults(Request $request): void
    {
        foreach (['tenant_sync_ssh_port' => '22', 'tenant_sync_ssh_remote_db_port' => '5432'] as $field => $default) {
            $value = $request->input($field);
            if ($value === null || $value === '') {
                $request->merge([$field => $default]);
            }
        }
    }

    /**
     * Test PDO-verbinding naar de opgegeven database-URL (formulier of opgeslagen waarde).
     */
    public function testTenantSync(Request $request)
    {
        $this->ensureSuperAdmin();

        try {
            $config = $this->tenantSyncSettings->connectionConfig($request);
            $this->tenantSyncSettings->validateConfig($config);
            $this->tenantWebsiteBundle->testSyncConnection(
                trim((string) $request->input('tenant_sync_target_database_url', '')),
                $config
            );

            $message = 'Verbinding met doel-database OK.';
            if ($config->sshEnabled) {
                $message .= ' (via SSH-tunnel naar '.$config->sshHost.')';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Voer volledige tenant-push uit (alle tabellen met company_id + companies-rij).
     * Bij AJAX/JSON (Accept: application/json of X-Requested-With: XMLHttpRequest): JSON zonder redirect.
     */
    public function runTenantSync(Request $request)
    {
        $this->ensureSuperAdmin();

        $wantsJson = $request->expectsJson() || $request->ajax();

        $tenantSyncRedirect = fn () => redirect()->route('admin.settings.index')->withFragment('tenant-sync');

        if (! $this->tenantWebsiteBundle->isPushGloballyEnabled()) {
            $msg = 'Sync staat uit. Schakel push in bij onderstaande instellingen of zet TENANT_SYNC_PUSH_ENABLED in .env.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return $tenantSyncRedirect()->with('error', $msg);
        }

        if (! $this->tenantWebsiteBundle->pushAllowedForEnvironment()) {
            $msg = 'Sync naar productie is geblokkeerd. Zet TENANT_SYNC_ALLOW_PRODUCTION_PUSH=true in .env als dit bewust moet.';
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return $tenantSyncRedirect()->with('error', $msg);
        }

        $validator = Validator::make($request->all(), [
            'source_company_id' => ['required', 'integer', 'exists:companies,id'],
            'confirm_full_sync' => ['required', 'accepted'],
        ], [
            'source_company_id.required' => 'Kies een bron-tenant (bedrijf).',
            'source_company_id.exists' => 'Het gekozen bedrijf bestaat niet.',
            'confirm_full_sync.required' => 'Vink de bevestiging aan om de sync te starten.',
            'confirm_full_sync.accepted' => 'Vink de bevestiging aan om de sync te starten.',
        ]);

        if ($validator->fails()) {
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Controleer het formulier.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            return $tenantSyncRedirect()
                ->withErrors($validator)
                ->withInput();
        }

        $sourceCompanyId = (int) $request->input('source_company_id');
        $wantsStream = $wantsJson && $request->header('X-Tenant-Sync-Stream') === '1';

        if ($wantsStream) {
            return $this->streamTenantSyncRun($sourceCompanyId);
        }

        try {
            $result = $this->tenantCompanyDataPush->pushFullTenant($sourceCompanyId);
        } catch (\Throwable $e) {
            $msg = 'Sync mislukt: '.$e->getMessage();
            if ($wantsJson) {
                return response()->json(['success' => false, 'message' => $msg], 500);
            }

            return $tenantSyncRedirect()->with('error', $msg);
        }

        $msg = $result['report']['summary'] ?? (
            'Tenant-sync voltooid. Doel company_id: '.$result['remote_company_id']
            .'. Ingevoegd: '.$result['inserted'].', overgeslagen: '.$result['skipped'].'.'
        );

        if ($wantsJson) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'report' => $result['report'] ?? null,
                'result' => $result,
            ]);
        }

        return redirect()->route('admin.settings.index')
            ->withFragment('tenant-sync')
            ->with('success', $msg)
            ->with('tenant_sync_report', $result['report'] ?? null)
            ->with('tenant_sync_completed', true);
    }

    private function streamTenantSyncRun(int $sourceCompanyId): StreamedResponse
    {
        return response()->stream(function () use ($sourceCompanyId): void {
            $this->flushTenantSyncStream();

            $emit = function (array $event): void {
                echo json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n";
                $this->flushTenantSyncStream();
            };

            try {
                $result = $this->tenantCompanyDataPush->pushFullTenant($sourceCompanyId, $emit);
                $emit([
                    'type' => 'complete',
                    'success' => true,
                    'message' => $result['report']['summary'] ?? (
                        'Tenant-sync voltooid. Doel company_id: '.$result['remote_company_id']
                        .'. Ingevoegd: '.$result['inserted'].', overgeslagen: '.$result['skipped'].'.'
                    ),
                    'report' => $result['report'] ?? null,
                ]);
            } catch (\Throwable $e) {
                $emit([
                    'type' => 'complete',
                    'success' => false,
                    'message' => 'Sync mislukt: '.$e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function flushTenantSyncStream(): void
    {
        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        flush();
    }

    /**
     * ZIP-download: volledige tenant-export (v2): bestanden, website_pages-manifest, tenant-general_settings.
     */
    public function exportTenantStorageBundle(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::query()->findOrFail((int) $request->query('company_id'));

        return $this->tenantStorageBundle->exportZip($company);
    }

    /**
     * ZIP-import: tenant-export (v2: bestanden + pagina’s + instellingen) of legacy v1 (alleen bestanden).
     */
    public function importTenantStorageBundle(Request $request)
    {
        $this->ensureSuperAdmin();
        $maxKb = (int) config('upload.tenant_bundle_max_kb', 512000);
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'bundle' => ['required', 'file', 'mimes:zip', 'max:'.$maxKb],
        ], [
            'bundle.required' => 'Selecteer een ZIP-bestand.',
            'bundle.mimes' => 'Alleen ZIP is toegestaan.',
            'bundle.max' => 'ZIP is te groot (max. '.(int) floor($maxKb / 1024).' MB). Bij 413 Request Entity Too Large: verhoog client_max_body_size in nginx (zie deploy/nginx-nexa.conf).',
        ]);

        try {
            $company = Company::query()->findOrFail((int) $request->input('company_id'));
            $result = $this->tenantStorageBundle->importZip($company, $request->file('bundle'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index')
                ->withFragment('tenant-sync')
                ->with('error', 'Import mislukt: '.$e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.settings.index')
            ->withFragment('tenant-sync')
            ->with('success', 'Tenant-export geïmporteerd: '.$result['copied_files'].' bestand(en), '
                .$result['imported_pages']." pagina's, ".$result['imported_settings'].' instelling(en), '
                .($result['imported_photos'] ?? 0).' profielfoto(\'s).');
    }

    /**
     * ZIP-download: website_pages (manifest) + door pagina’s gerefereerde bestanden (zie TenantWebsiteBundleService).
     */
    public function exportTenantWebsiteBundle(Request $request)
    {
        $this->ensureSuperAdmin();
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::query()->findOrFail((int) $request->query('company_id'));

        return $this->tenantWebsiteBundle->exportZip($company);
    }

    /**
     * ZIP-import: website-pagina’s + bestanden naar storage/app/public (website-bundle manifest).
     */
    public function importTenantWebsiteBundle(Request $request)
    {
        $this->ensureSuperAdmin();
        $maxKb = (int) config('upload.tenant_bundle_max_kb', 512000);
        $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'bundle' => ['required', 'file', 'mimes:zip', 'max:'.$maxKb],
        ], [
            'bundle.required' => 'Selecteer een ZIP-bestand.',
            'bundle.mimes' => 'Alleen ZIP is toegestaan.',
            'bundle.max' => 'ZIP is te groot (max. '.(int) floor($maxKb / 1024).' MB). Bij 413 Request Entity Too Large: verhoog client_max_body_size in nginx (zie deploy/nginx-nexa.conf).',
        ]);

        try {
            $company = Company::query()->findOrFail((int) $request->input('company_id'));
            $result = $this->tenantWebsiteBundle->importZip($company, $request->file('bundle'));
        } catch (\Throwable $e) {
            return redirect()->route('admin.settings.index')
                ->withFragment('tenant-sync')
                ->with('error', 'Website-import mislukt: '.$e->getMessage())
                ->withInput();
        }

        return redirect()->route('admin.settings.index')
            ->withFragment('tenant-sync')
            ->with('success', 'Website-import voltooid: '.$result['imported_pages']." pagina's, ".$result['copied_files'].' bestand(en) gekopieerd naar storage/app/public.');
    }

    /**
     * Update mail settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateMail(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

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
                'MAIL_ENCRYPTION' => $request->input('MAIL_ENCRYPTION', 'tls'),
                'MAIL_FROM_ADDRESS' => $request->input('MAIL_FROM_ADDRESS'),
                'MAIL_FROM_NAME' => $request->input('MAIL_FROM_NAME'),
            ];
            if ($request->filled('MAIL_PASSWORD')) {
                $mailSettings['MAIL_PASSWORD'] = $request->input('MAIL_PASSWORD');
            }

            foreach ($mailSettings as $key => $value) {
                GeneralSetting::set($key, (string) $value, $companyId);
            }

            return redirect()->route('admin.settings.index')
                ->with('success', 'Mail instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
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
            $fromAddress = (! empty($smtpUsername) && $smtpUsername !== $configuredFromAddress) ? $smtpUsername : $configuredFromAddress;

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
                'message' => 'Test email succesvol verzonden naar '.$request->input('test_email').'!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden bij het verzenden: '.$e->getMessage(),
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

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

        $validator = Validator::make($request->all(), [
            GoogleSeoSettingsService::KEY_PROPERTY_ID => 'nullable|string|max:255',
            GoogleSeoSettingsService::KEY_ANALYTICS_ID => 'nullable|string|max:255',
            GoogleSeoSettingsService::KEY_TAG_MANAGER_ID => 'nullable|string|max:255',
            GoogleSeoSettingsService::KEY_META_DESCRIPTION => 'nullable|string|max:500',
            GoogleSeoSettingsService::KEY_META_KEYWORDS => 'nullable|string|max:500',
            GoogleSeoSettingsService::KEY_SITE_VERIFICATION => 'nullable|string|max:255',
            GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_ENABLED => 'nullable|boolean',
            GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_SERVICE_ACCOUNT => 'nullable|string|max:20000',
            GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_SITEMAP_PATH => 'nullable|string|max:255',
            GoogleSeoSettingsService::KEY_SEARCH_CONSOLE_AUTO_SITEMAP => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.settings.index', [], 303)
                ->withErrors($validator)
                ->withInput()
                ->withFragment('seo');
        }

        try {
            $this->googleSeoSettings->saveFromRequest($request->all(), $companyId);

            $message = 'SEO instellingen succesvol bijgewerkt!';
            if ($this->googleSeoSettings->isSearchConsoleEnabled($companyId)
                && $this->googleSeoSettings->shouldAutoSubmitSitemap($companyId)
                && $this->googleSeoSettings->hasServiceAccount($companyId)) {
                $submit = $this->googleSearchConsole->submitSitemap($companyId);
                $message .= $submit['ok']
                    ? ' '.$submit['message']
                    : ' Let op: sitemap kon niet automatisch worden ingediend — '.$submit['message'];
            }

            return redirect()->route('admin.settings.index', ['saved' => 1], 303)
                ->with('success', $message)
                ->withFragment('seo');
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.settings.index', [], 303)
                ->with('error', $e->getMessage())
                ->withInput()
                ->withFragment('seo');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index', [], 303)
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
                ->withInput()
                ->withFragment('seo');
        }
    }

    public function testSeoConnection(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }

        $result = $this->googleSearchConsole->testConnection($this->settingsCompanyId());

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function submitSeoSitemap(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }

        $result = $this->googleSearchConsole->submitSitemap($this->settingsCompanyId());

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Update Google Maps settings
     * Alleen toegankelijk voor super-admin
     */
    public function updateMaps(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

        $validator = Validator::make($request->all(), [
            'GOOGLE_MAPS_API_KEY' => 'required|string|max:255',
            'GOOGLE_MAPS_MAP_ID' => 'nullable|string|max:255',
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
                'GOOGLE_MAPS_MAP_ID' => $request->input('GOOGLE_MAPS_MAP_ID', ''),
                'GOOGLE_MAPS_ZOOM' => $request->input('GOOGLE_MAPS_ZOOM', '12'),
                'GOOGLE_MAPS_CENTER_LAT' => $request->input('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
                'GOOGLE_MAPS_CENTER_LNG' => $request->input('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
                'GOOGLE_MAPS_TYPE' => $request->input('GOOGLE_MAPS_TYPE', 'roadmap'),
            ];

            foreach ($mapsSettings as $key => $value) {
                GeneralSetting::set($key, (string) $value, $companyId);
            }

            return redirect()->route('admin.settings.index')
                ->with('success', 'Google Maps instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update Google Reviews settings (Place ID + cache; gebruikt dezelfde Maps API-sleutel)
     */
    public function updateGoogleReviews(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

        // Lege cacheduur normaliseren naar 24 zodat validatie slaagt; waarde 1 blijft gewoon 1
        $cacheHoursInput = $request->input('google_reviews_cache_hours');
        if ($cacheHoursInput === '' || $cacheHoursInput === null) {
            $request->merge(['google_reviews_cache_hours' => 24]);
        }

        $validator = Validator::make($request->all(), [
            'google_reviews_place_id' => 'nullable|string|max:255',
            'google_reviews_business_name' => 'nullable|string|max:255',
            'google_reviews_cache_hours' => 'nullable|integer|min:1|max:168',
            'google_reviews_count' => 'nullable|integer|min:1|max:5',
            'google_reviews_min_stars' => 'nullable|integer|min:1|max:5',
            'google_reviews_section_title' => 'nullable|string|max:255',
            'google_reviews_section_background' => 'nullable|string|max:32',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('admin.settings.index').'#google-reviews')
                ->withErrors($validator)
                ->withInput();
        }

        $bgRaw = trim((string) $request->input('google_reviews_section_background', ''));
        $bgNorm = GoogleReviewsService::normalizeHexColor($bgRaw);
        if ($bgRaw !== '' && $bgNorm === '') {
            return redirect()->to(route('admin.settings.index').'#google-reviews')
                ->withErrors(['google_reviews_section_background' => 'Ongeldige kleur. Gebruik een hex-waarde, bijv. #f3f4f6 of #abc.'])
                ->withInput();
        }

        try {
            $oldPlaceId = trim((string) GeneralSetting::get('google_reviews_place_id', '', $companyId));
            $oldBusinessName = trim((string) GeneralSetting::get('google_reviews_business_name', '', $companyId));
            $newPlaceId = trim((string) $request->input('google_reviews_place_id', ''));
            $newBusinessName = trim((string) $request->input('google_reviews_business_name', ''));
            GeneralSetting::set('google_reviews_place_id', $newPlaceId, $companyId);
            GeneralSetting::set('google_reviews_business_name', $newBusinessName, $companyId);
            $hours = (int) $request->input('google_reviews_cache_hours', 24);
            $hours = max(1, min(168, $hours));
            GeneralSetting::set('google_reviews_cache_hours', (string) $hours, $companyId);
            $count = max(1, min(5, (int) $request->input('google_reviews_count', 5)));
            GeneralSetting::set('google_reviews_count', (string) $count, $companyId);
            $minStars = max(1, min(5, (int) $request->input('google_reviews_min_stars', 1)));
            GeneralSetting::set('google_reviews_min_stars', (string) $minStars, $companyId);
            GeneralSetting::set('google_reviews_section_title', trim((string) $request->input('google_reviews_section_title', '')), $companyId);
            GeneralSetting::set('google_reviews_section_background', $bgNorm, $companyId);

            // Cache legen (oude en nieuwe key) zodat verse data wordt opgehaald
            try {
                \Illuminate\Support\Facades\Cache::forget('google_reviews_'.md5($oldPlaceId.'|'.$oldBusinessName));
                \Illuminate\Support\Facades\Cache::forget('google_reviews_'.md5($newPlaceId.'|'.$newBusinessName));
            } catch (\Throwable $e) {
                // Negeer cachefouten; redirect gaat gewoon door
            }

            return redirect()->to(route('admin.settings.index').'#google-reviews')
                ->with('success', 'Google Reviews instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->to(route('admin.settings.index').'#google-reviews')
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
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

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

        $validator = Validator::make($request->all(), [
            'WHATSAPP_API_TOKEN' => 'nullable|string|max:500',
            'WHATSAPP_PHONE_NUMBER_ID' => 'nullable|string|max:255',
            'WHATSAPP_BUSINESS_ACCOUNT_ID' => 'nullable|string|max:255',
            'WHATSAPP_API_VERSION' => 'nullable|string|max:50',
            'WHATSAPP_WEBHOOK_VERIFY_TOKEN' => 'nullable|string|max:255',
            'WHATSAPP_DEFAULT_MESSAGE' => 'nullable|string|max:1000',
            'WHATSAPP_CLICK_TO_CHAT_ENABLED' => 'nullable|in:0,1',
            'WHATSAPP_CLICK_TO_CHAT_NUMBER' => 'nullable|string|max:50',
            'WHATSAPP_WIDGET_ENABLED' => 'nullable|in:0,1',
            'WHATSAPP_WIDGET_PHONE' => 'nullable|string|max:50',
            'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return redirect()->to(route('admin.settings.index').'#whatsapp')
                ->withErrors($validator)
                ->withInput();
        }

        $phoneError = 'Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).';
        $normalizedClickToChat = DutchPhoneNumber::normalizeOptionalNlToInternational(
            trim((string) $request->input('WHATSAPP_CLICK_TO_CHAT_NUMBER', ''))
        );
        $normalizedWidgetPhone = DutchPhoneNumber::normalizeOptionalNlToInternational(
            trim((string) $request->input('WHATSAPP_WIDGET_PHONE', ''))
        );
        if ($normalizedClickToChat === null) {
            return redirect()->to(route('admin.settings.index').'#whatsapp')
                ->withErrors(['WHATSAPP_CLICK_TO_CHAT_NUMBER' => $phoneError])
                ->withInput();
        }
        if ($normalizedWidgetPhone === null) {
            return redirect()->to(route('admin.settings.index').'#whatsapp')
                ->withErrors(['WHATSAPP_WIDGET_PHONE' => $phoneError])
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
                'WHATSAPP_CLICK_TO_CHAT_ENABLED' => $request->boolean('WHATSAPP_CLICK_TO_CHAT_ENABLED') ? '1' : '0',
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => $normalizedClickToChat,
                'WHATSAPP_WIDGET_ENABLED' => $request->boolean('WHATSAPP_WIDGET_ENABLED') ? '1' : '0',
                'WHATSAPP_WIDGET_PHONE' => $normalizedWidgetPhone,
                'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => trim((string) $request->input('WHATSAPP_WIDGET_DEFAULT_MESSAGE', 'Hallo, ik heb een vraag over jullie diensten.')),
            ];

            foreach ($whatsappSettings as $key => $value) {
                GeneralSetting::set($key, (string) $value, $companyId);
            }

            GeneralSetting::set(
                TaxiDispatchSettingsService::KEY_BOOKING_WHATSAPP_CLICK_TO_CHAT,
                $whatsappSettings['WHATSAPP_CLICK_TO_CHAT_ENABLED'],
                $companyId
            );

            return redirect()->to(route('admin.settings.index').'#whatsapp')
                ->with('success', 'WhatsApp Business instellingen succesvol bijgewerkt!');
        } catch (\Exception $e) {
            return redirect()->to(route('admin.settings.index').'#whatsapp')
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Update Coming Soon pagina-instellingen (getoond wanneer geen actieve module)
     */
    public function updateComingSoon(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

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

        GeneralSetting::set('coming_soon_title', $request->input('coming_soon_title'), $companyId);
        GeneralSetting::set('coming_soon_text', $request->input('coming_soon_text'), $companyId);
        GeneralSetting::set('coming_soon_secondary_text', $request->input('coming_soon_secondary_text', ''), $companyId);
        GeneralSetting::set('coming_soon_show_email', $request->has('coming_soon_show_email') && $request->input('coming_soon_show_email') ? '1' : '0', $companyId);
        GeneralSetting::set('coming_soon_contact_email', $request->input('coming_soon_contact_email', ''), $companyId);
        GeneralSetting::set('coming_soon_contact_label', $request->input('coming_soon_contact_label', 'E-mail'), $companyId);
        GeneralSetting::set('coming_soon_footer_text', $request->input('coming_soon_footer_text', '© {year} {site}. Binnenkort beschikbaar.'), $companyId);

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

        $settingsCompanyId = $this->settingsCompanyId();
        $tenantScopedSettingsActive = $settingsCompanyId !== null;

        $comingSoonSettings = [
            'coming_soon_title' => GeneralSetting::get('coming_soon_title', 'We zijn bijna live'),
            'coming_soon_text' => GeneralSetting::get('coming_soon_text', 'Onze website wordt op dit moment voor u klaargemaakt. Binnenkort vindt u hier alle informatie en mogelijkheden.'),
            'coming_soon_secondary_text' => GeneralSetting::get('coming_soon_secondary_text', 'Heeft u vragen? Neem gerust contact met ons op.'),
            'coming_soon_show_email' => GeneralSetting::get('coming_soon_show_email', '1'),
            'coming_soon_contact_email' => GeneralSetting::get('coming_soon_contact_email', ''),
            'coming_soon_contact_label' => GeneralSetting::get('coming_soon_contact_label', 'E-mail'),
            'coming_soon_footer_text' => GeneralSetting::get('coming_soon_footer_text', '© {year} {site}. Binnenkort beschikbaar.'),
        ];

        $comingSoonImagePath = GeneralSetting::get('coming_soon_image');
        $comingSoonImageUrl = null;
        if ($comingSoonImagePath && Storage::disk('public')->exists($comingSoonImagePath)) {
            $comingSoonImageUrl = app(\App\Services\WebsiteBuilderService::class)->publicFileUrl(ltrim($comingSoonImagePath, '/'));
        }

        return view('admin.settings.frontend', compact('comingSoonSettings', 'comingSoonImageUrl', 'settingsCompanyId', 'tenantScopedSettingsActive'));
    }

    /**
     * Preview van de Coming Soon-pagina zoals bezoekers die zien (super-admin only).
     */
    public function frontendComingSoonPreview()
    {
        $this->ensureSuperAdmin();

        $settings = ComingSoonController::getSettings();
        $showEmail = ! empty($settings['coming_soon_show_email']) && $settings['coming_soon_show_email'] !== '0';
        $contactEmail = $settings['coming_soon_contact_email'] ?? '';

        return view('frontend.coming-soon', [
            'settings' => $settings,
            'showEmail' => $showEmail,
            'contactEmail' => $contactEmail,
            'adminPreviewReturnUrl' => route('admin.settings.frontend.index'),
        ]);
    }

    /**
     * Display general settings page
     * Alleen toegankelijk voor super-admin
     */
    public function generalIndex()
    {
        $this->ensureSuperAdmin();

        $settingsCompanyId = $this->settingsCompanyId();
        $tenantScopedSettingsActive = $settingsCompanyId !== null;

        $logo = GeneralSetting::get('logo');
        $favicon = GeneralSetting::get('favicon', null, $settingsCompanyId);
        $logoSize = GeneralSetting::get('logo_size', '26');
        $siteName = GeneralSetting::get('site_name', config('app.name'));
        $siteDescription = GeneralSetting::get('site_description', '');
        $aiChatEnabled = GeneralSetting::get('ai_chat_enabled', '0', $settingsCompanyId);
        $aiChatAssistant = app(AiChatAssistantService::class);
        $aiChatModules = Module::query()
            ->where('installed', true)
            ->orderBy('display_name')
            ->get(['name', 'display_name']);
        $aiChatModuleWebhooks = [];
        $aiChatModuleWebhookDefaults = [];
        foreach ($aiChatModules as $module) {
            $moduleName = (string) $module->name;
            $stored = trim((string) GeneralSetting::get($aiChatAssistant->webhookSettingKey($moduleName), '', $settingsCompanyId));
            if ($stored === '' && strtolower($moduleName) === 'taxi') {
                $stored = trim((string) GeneralSetting::get('ai_chat_nexa_taxi_webhook_url', '', $settingsCompanyId));
            }
            $aiChatModuleWebhooks[$moduleName] = $stored;
            $aiChatModuleWebhookDefaults[$moduleName] = $aiChatAssistant->defaultWebhookUrlForModule($moduleName);
        }
        $infoRequestSuccessTitle = GeneralSetting::get('info_request_success_title', 'Uw bericht is verstuurd. We nemen zo snel mogelijk contact met u op.');
        $infoRequestSuccessSubtitle = GeneralSetting::get('info_request_success_subtitle', 'Er wordt binnenkort contact met u opgenomen.');
        $infoRequestSuccessFooter = GeneralSetting::get('info_request_success_footer', 'Uw bericht is succesvol verzonden.');
        $infoRequestSuccessTextsEnabled = GeneralSetting::get('info_request_success_texts_enabled', '1');
        $infoRequestSuccessImage = GeneralSetting::get('info_request_success_image');
        $infoRequestSuccessIcon = GeneralSetting::get('info_request_success_icon', 'ki-filled ki-check-circle');
        $infoRequestSuccessSize = GeneralSetting::get('info_request_success_icon_size', '80');
        $infoRequestSuccessImageSizePercent = GeneralSetting::get('info_request_success_image_size_percent', '80');
        $adminFooterBrand = GeneralSetting::get('admin_footer_brand', 'Nexa Skillmatching');

        if ($infoRequestSuccessImage && ! Storage::disk('public')->exists($infoRequestSuccessImage)) {
            \Log::warning('Success image file not found in storage', ['path' => $infoRequestSuccessImage]);
            $infoRequestSuccessImage = null;
        }

        // Verify files exist
        if ($logo && ! Storage::disk('public')->exists($logo)) {
            \Log::warning('Logo file not found in storage', ['path' => $logo]);
            $logo = null;
        }

        $logoMode = GeneralSetting::get('logo_mode', 'single');
        if (! in_array($logoMode, ['single', 'light_dark'], true)) {
            $logoMode = 'single';
        }
        $logoDark = GeneralSetting::get('logo_dark');
        if ($logoDark && ! Storage::disk('public')->exists($logoDark)) {
            \Log::warning('Dark logo file not found in storage', ['path' => $logoDark]);
            $logoDark = null;
        }
        // Als er een dark logo is geüpload, toon toggle als aangevinkt (en sync DB indien nodig)
        $settingsCompanyId = $this->settingsCompanyId();
        if ($logoDark !== null && $logoMode !== 'light_dark' && $settingsCompanyId !== null) {
            GeneralSetting::set('logo_mode', 'light_dark', $settingsCompanyId);
            $logoMode = 'light_dark';
        }

        if ($favicon && ! Storage::disk('public')->exists($favicon)) {
            \Log::warning('Favicon file not found in storage', ['path' => $favicon]);
            $favicon = null;
        }

        $faviconMeta = app(WebsiteBuilderService::class)->publicFaviconMeta($settingsCompanyId);
        $faviconDisplayUrl = $faviconMeta['url'];

        $infoRequestFormPreviewContexts = app(InfoRequestFormPreviewContextService::class)
            ->contextsForCompany($settingsCompanyId);
        $infoRequestFormPreviewContext = app(InfoRequestFormPreviewContextService::class)
            ->defaultContext($infoRequestFormPreviewContexts);

        return view('admin.settings.general', compact('logo', 'favicon', 'faviconDisplayUrl', 'logoSize', 'logoMode', 'logoDark', 'siteName', 'siteDescription', 'aiChatEnabled', 'aiChatModules', 'aiChatModuleWebhooks', 'aiChatModuleWebhookDefaults', 'adminFooterBrand', 'infoRequestSuccessTitle', 'infoRequestSuccessSubtitle', 'infoRequestSuccessFooter', 'infoRequestSuccessTextsEnabled', 'infoRequestSuccessImage', 'infoRequestSuccessIcon', 'infoRequestSuccessSize', 'infoRequestSuccessImageSizePercent', 'infoRequestFormPreviewContexts', 'infoRequestFormPreviewContext', 'settingsCompanyId', 'tenantScopedSettingsActive'));
    }

    /**
     * Update general settings
     * Alleen toegankelijk voor super-admin
     */
    public function generalUpdate(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($redirect = $this->requireSettingsTenantOrRedirect()) {
            return $redirect;
        }
        $companyId = $this->settingsCompanyId();

        $validator = Validator::make($request->all(), [
            'site_name' => 'nullable|string|max:255',
            'site_description' => 'nullable|string|max:1000',
            'ai_chat_enabled' => 'nullable',
            'ai_chat_webhooks' => 'nullable|array',
            'ai_chat_webhooks.*' => 'nullable|url|max:500',
            'info_request_success_title' => 'nullable|string|max:500',
            'info_request_success_subtitle' => 'nullable|string|max:500',
            'info_request_success_footer' => 'nullable|string|max:500',
            'info_request_success_texts_enabled' => 'nullable|in:0,1',
            'info_request_success_icon' => 'nullable|string|max:100',
            'info_request_success_icon_size' => 'nullable|integer|min:32|max:200',
            'info_request_success_image_size_percent' => 'nullable|integer|min:10|max:100',
            'admin_footer_brand' => 'nullable|string|max:255',
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
                GeneralSetting::set('site_name', $request->input('site_name', ''), $companyId);
            }
            if ($request->has('site_description')) {
                GeneralSetting::set('site_description', $request->input('site_description', ''), $companyId);
            }
            GeneralSetting::set('ai_chat_enabled', $request->has('ai_chat_enabled') ? '1' : '0', $companyId);
            if ($request->has('ai_chat_webhooks') && is_array($request->input('ai_chat_webhooks'))) {
                $aiChatAssistant = app(AiChatAssistantService::class);
                foreach ($request->input('ai_chat_webhooks') as $moduleName => $webhookUrl) {
                    $moduleSlug = strtolower(trim((string) $moduleName));
                    if ($moduleSlug === '') {
                        continue;
                    }
                    GeneralSetting::set(
                        $aiChatAssistant->webhookSettingKey($moduleSlug),
                        trim((string) $webhookUrl),
                        $companyId
                    );
                }
            }
            if ($request->has('admin_footer_brand')) {
                GeneralSetting::set('admin_footer_brand', $request->input('admin_footer_brand', ''), $companyId);
            }
            if ($request->has('info_request_success_title')) {
                GeneralSetting::set('info_request_success_title', $request->input('info_request_success_title', ''), $companyId);
            }
            if ($request->has('info_request_success_subtitle')) {
                GeneralSetting::set('info_request_success_subtitle', $request->input('info_request_success_subtitle', ''), $companyId);
            }
            if ($request->has('info_request_success_footer')) {
                GeneralSetting::set('info_request_success_footer', $request->input('info_request_success_footer', ''), $companyId);
            }
            if ($request->has('info_request_success_texts_enabled')) {
                GeneralSetting::set('info_request_success_texts_enabled', $request->input('info_request_success_texts_enabled') === '0' ? '0' : '1', $companyId);
            } else {
                GeneralSetting::set('info_request_success_texts_enabled', '1', $companyId);
            }
            if ($request->has('info_request_success_icon')) {
                GeneralSetting::set('info_request_success_icon', $request->input('info_request_success_icon', ''), $companyId);
            }
            if ($request->has('info_request_success_icon_size')) {
                GeneralSetting::set('info_request_success_icon_size', (string) $request->input('info_request_success_icon_size', '80'), $companyId);
            }
            if ($request->has('info_request_success_image_size_percent')) {
                GeneralSetting::set('info_request_success_image_size_percent', (string) $request->input('info_request_success_image_size_percent', '80'), $companyId);
            }

            // Ensure settings directory exists
            $settingsDir = storage_path('app/public/settings');
            if (! file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }

            // Handle logo upload (only if not already uploaded via AJAX)
            if ($request->hasFile('logo') && ! $request->ajax()) {
                $logoFile = $request->file('logo');

                // Delete old logo if exists
                $oldLogo = GeneralSetting::get('logo', null, $companyId);
                if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                    Storage::disk('public')->delete($oldLogo);
                }

                // Store new logo
                $logoPath = $logoFile->store('settings', 'public');

                // Verify file was stored
                if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
                    \Log::error('Logo storage failed', [
                        'path' => $logoPath,
                        'file_exists' => $logoPath ? Storage::disk('public')->exists($logoPath) : false,
                        'storage_path' => storage_path('app/public'),
                        'settings_dir_exists' => file_exists($settingsDir),
                    ]);
                    throw new \Exception('Logo bestand kon niet worden opgeslagen. Controleer de storage permissies.');
                }

                // Save path to database
                GeneralSetting::set('logo', $logoPath, $companyId);

                \Log::info('Logo uploaded successfully', [
                    'path' => $logoPath,
                    'full_path' => storage_path('app/public/'.$logoPath),
                    'file_exists' => Storage::disk('public')->exists($logoPath),
                ]);
            }

            // Handle favicon upload (only if not already uploaded via AJAX)
            if ($request->hasFile('favicon') && ! $request->ajax()) {
                $faviconFile = $request->file('favicon');

                // Delete old favicon if exists
                $oldFavicon = GeneralSetting::get('favicon', null, $companyId);
                if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                    Storage::disk('public')->delete($oldFavicon);
                }

                // Store new favicon
                $faviconPath = $faviconFile->store('settings', 'public');

                // Verify file was stored
                if (! $faviconPath || ! Storage::disk('public')->exists($faviconPath)) {
                    \Log::error('Favicon storage failed', [
                        'path' => $faviconPath,
                        'file_exists' => $faviconPath ? Storage::disk('public')->exists($faviconPath) : false,
                        'storage_path' => storage_path('app/public'),
                        'settings_dir_exists' => file_exists($settingsDir),
                    ]);
                    throw new \Exception('Favicon bestand kon niet worden opgeslagen. Controleer de storage permissies.');
                }

                // Save path to database
                GeneralSetting::set('favicon', $faviconPath, $companyId);

                \Log::info('Favicon uploaded successfully', [
                    'path' => $faviconPath,
                    'full_path' => storage_path('app/public/'.$faviconPath),
                    'file_exists' => Storage::disk('public')->exists($faviconPath),
                ]);
            }

            // Update logo size
            if ($request->has('logo_size')) {
                GeneralSetting::set('logo_size', $request->input('logo_size'), $companyId);
            }

            if ($request->has('logo_mode') && in_array($request->input('logo_mode'), ['single', 'light_dark'], true)) {
                GeneralSetting::set('logo_mode', $request->input('logo_mode'), $companyId);
            }

            return redirect()->route('admin.settings.general.index')
                ->with('success', 'Algemene configuraties succesvol bijgewerkt!');
        } catch (\Exception $e) {
            \Log::error('Error updating general settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('admin.settings.general.index')
                ->with('error', 'Er is een fout opgetreden: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload logo immediately via AJAX
     */
    public function uploadLogo(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        $request->validate([
            'logo' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'logo_type' => 'nullable|string|in:light,dark',
        ], [
            'logo.required' => 'Selecteer een logo bestand.',
            'logo.file' => 'Het bestand moet een geldig bestand zijn.',
            'logo.mimes' => 'Alleen JPEG, PNG, JPG, GIF en SVG bestanden zijn toegestaan.',
            'logo.max' => 'Het bestand mag maximaal 2MB groot zijn.',
        ]);

        $isDark = $request->input('logo_type') === 'dark';

        try {
            // Ensure settings directory exists
            $settingsDir = storage_path('app/public/settings');
            if (! file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }

            $logoFile = $request->file('logo');

            if ($isDark) {
                $oldLogo = GeneralSetting::get('logo_dark', null, $companyId);
                $settingKey = 'logo_dark';
            } else {
                $oldLogo = GeneralSetting::get('logo', null, $companyId);
                $settingKey = 'logo';
            }
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $logoPath = $logoFile->store('settings', 'public');

            if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
                \Log::error('Logo storage failed', [
                    'path' => $logoPath,
                    'file_exists' => $logoPath ? Storage::disk('public')->exists($logoPath) : false,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Logo bestand kon niet worden opgeslagen. Controleer de storage permissies.',
                ], 500);
            }

            GeneralSetting::set($settingKey, $logoPath, $companyId);
            if ($isDark) {
                GeneralSetting::set('logo_mode', 'light_dark', $companyId);
            }

            \Log::info('Logo uploaded successfully', ['path' => $logoPath, 'type' => $isDark ? 'dark' : 'light']);

            $logoUrl = $isDark
                ? route('admin.settings.logo-dark').'?t='.time()
                : route('admin.settings.logo').'?t='.time();

            return response()->json([
                'success' => true,
                'message' => 'Logo succesvol geüpload.',
                'logo_url' => $logoUrl,
                'logo_type' => $isDark ? 'dark' : 'light',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading logo', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verwijder light mode-logo (bestand en instelling)
     */
    public function removeLogoLight()
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        $path = GeneralSetting::get('logo', null, $companyId);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        GeneralSetting::set('logo', '', $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Light logo verwijderd.',
        ]);
    }

    /**
     * Verwijder dark mode-logo (terug naar één logo voor beide modi)
     */
    public function removeLogoDark()
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        $path = GeneralSetting::get('logo_dark', null, $companyId);
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        GeneralSetting::set('logo_dark', '', $companyId);
        GeneralSetting::set('logo_mode', 'single', $companyId);

        return response()->json([
            'success' => true,
            'message' => 'Dark logo verwijderd. Er wordt weer het light mode-logo gebruikt.',
        ]);
    }

    /**
     * Upload favicon immediately via AJAX
     */
    public function uploadFavicon(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

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
            if (! file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }

            $faviconFile = $request->file('favicon');

            // Delete old favicon if exists
            $oldFavicon = GeneralSetting::get('favicon', null, $companyId);
            if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
                Storage::disk('public')->delete($oldFavicon);
            }

            // Store new favicon
            $faviconPath = $faviconFile->store('settings', 'public');

            // Verify file was stored
            if (! $faviconPath || ! Storage::disk('public')->exists($faviconPath)) {
                \Log::error('Favicon storage failed', [
                    'path' => $faviconPath,
                    'file_exists' => $faviconPath ? Storage::disk('public')->exists($faviconPath) : false,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Favicon bestand kon niet worden opgeslagen. Controleer de storage permissies.',
                ], 500);
            }

            // Save path to database
            GeneralSetting::set('favicon', $faviconPath, $companyId);

            \Log::info('Favicon uploaded successfully', ['path' => $faviconPath]);

            $faviconMeta = app(WebsiteBuilderService::class)->publicFaviconMeta($companyId);

            return response()->json([
                'success' => true,
                'message' => 'Favicon succesvol geüpload.',
                'favicon_url' => $faviconMeta['url'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading favicon', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload succes-icoon/plaatje voor formulier succesbericht (AJAX)
     */
    public function uploadSuccessImage(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        try {
            $request->validate([
                'info_request_success_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ], [
                'info_request_success_image.required' => 'Selecteer een afbeelding.',
                'info_request_success_image.file' => 'Het bestand is ongeldig of kon niet worden gelezen.',
                'info_request_success_image.mimes' => 'Ongeldig bestandstype. Alleen JPEG, PNG, JPG, GIF, SVG en WebP zijn toegestaan.',
                'info_request_success_image.max' => 'Het bestand is te groot. Maximaal 5MB (5120 KB) toegestaan. Uw bestand is groter.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = isset($errors['info_request_success_image'][0])
                ? $errors['info_request_success_image'][0]
                : 'De afbeelding voldoet niet aan de eisen. Controleer het formaat (max. 5MB) en het type (JPEG, PNG, GIF, SVG, WebP).';

            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], 422);
        }

        try {
            $settingsDir = storage_path('app/public/settings');
            if (! file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }

            $oldPath = GeneralSetting::get('info_request_success_image', null, $companyId);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('info_request_success_image')->store('settings', 'public');
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Bestand kon niet worden opgeslagen.'], 500);
            }

            GeneralSetting::set('info_request_success_image', $path, $companyId);

            return response()->json([
                'success' => true,
                'message' => 'Afbeelding geüpload.',
                'image_url' => route('admin.settings.success-image').'?t='.time(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading success image', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Er is een fout opgetreden: '.$e->getMessage()], 500);
        }
    }

    /**
     * Verwijder succes-afbeelding (gebruik weer icoon)
     */
    public function removeSuccessImage(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        $oldPath = GeneralSetting::get('info_request_success_image', null, $companyId);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        GeneralSetting::set('info_request_success_image', '', $companyId);

        return response()->json(['success' => true, 'message' => 'Afbeelding verwijderd.']);
    }

    /**
     * Upload centrale afbeelding voor Coming Soon-pagina (AJAX)
     */
    public function uploadComingSoonImage(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        try {
            $request->validate([
                'coming_soon_image' => 'required|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            ], [
                'coming_soon_image.required' => 'Selecteer een afbeelding.',
                'coming_soon_image.file' => 'Het bestand is ongeldig of kon niet worden gelezen.',
                'coming_soon_image.mimes' => 'Ongeldig bestandstype. Alleen JPEG, PNG, JPG, GIF, SVG en WebP zijn toegestaan.',
                'coming_soon_image.max' => 'Het bestand is te groot. Maximaal 5MB (5120 KB) toegestaan. Uw bestand is groter.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $message = isset($errors['coming_soon_image'][0])
                ? $errors['coming_soon_image'][0]
                : 'De afbeelding voldoet niet aan de eisen. Controleer het formaat (max. 5MB) en het type (JPEG, PNG, GIF, SVG, WebP).';

            return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], 422);
        }

        try {
            $settingsDir = storage_path('app/public/settings');
            if (! file_exists($settingsDir)) {
                File::makeDirectory($settingsDir, 0755, true);
            }

            $oldPath = GeneralSetting::get('coming_soon_image', null, $companyId);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('coming_soon_image')->store('settings', 'public');
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return response()->json(['success' => false, 'message' => 'Bestand kon niet worden opgeslagen.'], 500);
            }

            GeneralSetting::set('coming_soon_image', $path, $companyId);

            return response()->json([
                'success' => true,
                'message' => 'Afbeelding geüpload.',
                'image_url' => route('admin.settings.coming-soon-image').'?t='.time(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error uploading coming soon image', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Er is een fout opgetreden: '.$e->getMessage()], 500);
        }
    }

    /**
     * Verwijder centrale Coming Soon-afbeelding
     */
    public function removeComingSoonImage(Request $request)
    {
        $this->ensureSuperAdmin();

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

        $oldPath = GeneralSetting::get('coming_soon_image', null, $companyId);
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
        GeneralSetting::set('coming_soon_image', '', $companyId);

        return response()->json(['success' => true, 'message' => 'Afbeelding verwijderd.']);
    }

    /**
     * Get logo file (toegankelijk voor alle ingelogde admins, o.a. voor sidebar)
     */
    public function getLogo()
    {
        $logoPath = GeneralSetting::get('logo');

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            abort(404, 'Logo niet gevonden');
        }

        $file = Storage::disk('public')->get($logoPath);
        $mimeType = Storage::disk('public')->mimeType($logoPath);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="logo"');
    }

    /**
     * Get dark mode logo file (sidebar e.d.)
     */
    public function getLogoDark()
    {
        $logoPath = GeneralSetting::get('logo_dark');
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            abort(404, 'Dark logo niet gevonden');
        }
        $file = Storage::disk('public')->get($logoPath);
        $mimeType = Storage::disk('public')->mimeType($logoPath);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="logo-dark"');
    }

    /**
     * Get succes-afbeelding voor formulier (voor frontend en admin preview)
     */
    public function getSuccessImage()
    {
        $path = GeneralSetting::get('info_request_success_image');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Afbeelding niet gevonden');
        }
        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="success-image"');
    }

    /**
     * Get Coming Soon centrale afbeelding (admin preview)
     */
    public function getComingSoonImage()
    {
        $path = GeneralSetting::get('coming_soon_image');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Afbeelding niet gevonden');
        }
        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="coming-soon-image"');
    }

    /**
     * Get favicon file
     */
    public function getFavicon()
    {
        $faviconPath = GeneralSetting::get('favicon');

        if (! $faviconPath || ! Storage::disk('public')->exists($faviconPath)) {
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

        if ($j = $this->jsonTenantRequired()) {
            return $j;
        }
        $companyId = $this->settingsCompanyId();

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
            GeneralSetting::set('logo_size', $logoSize, $companyId);

            \Log::info('Logo size updated', ['size' => $logoSize]);

            return response()->json([
                'success' => true,
                'message' => 'Logo grootte succesvol bijgewerkt.',
                'logo_size' => $logoSize,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating logo size', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Er is een fout opgetreden: '.$e->getMessage(),
            ], 500);
        }
    }
}

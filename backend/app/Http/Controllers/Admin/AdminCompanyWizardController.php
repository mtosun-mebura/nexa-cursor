<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\GeneralSetting;
use App\Models\Module as ModuleModel;
use App\Models\User;
use App\Modules\NexaTaxi\Services\TaxiDispatchSettingsService;
use App\Services\CompanyEntitlementService;
use App\Services\EnvService;
use App\Services\GoogleSeoSettingsService;
use App\Services\ModuleManager;
use App\Services\NexaPricingService;
use App\Services\PaymentProviderService;
use App\Services\TenantConfigAccessService;
use App\Services\TenantOnboardingService;
use App\Services\WebsiteBuilderService;
use App\Support\DutchPhoneNumber;
use App\Support\TenantConfigCapability;
use App\Support\TenantPackageCapability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AdminCompanyWizardController extends AdminCompanyController
{
    public const TOTAL_STEPS = 10;

    private const SESSION_PREFIX = 'company_wizard.';

    /** Tijdens onboarding (stap 2–laatste): koppel nieuwe resources aan dit bedrijf als URL-parameters ontbreken. */
    public const SESSION_ACTIVE_ONBOARDING_COMPANY_ID = 'tenant_onboarding_active_company_id';

    /**
     * @return array<int, array{label: string, short: string, icon: string}>
     */
    public static function stepMeta(): array
    {
        return [
            1 => ['label' => 'Bedrijf & logo', 'short' => 'Bedrijf', 'icon' => 'ki-notepad'],
            2 => ['label' => 'Vestigingen', 'short' => 'Vestigingen', 'icon' => 'ki-geolocation'],
            3 => ['label' => 'Domein', 'short' => 'Domein', 'icon' => 'ki-cloud'],
            4 => ['label' => 'Modules', 'short' => 'Modules', 'icon' => 'ki-element-11'],
            5 => ['label' => 'Gebruikers', 'short' => 'Gebruikers', 'icon' => 'ki-users'],
            6 => ['label' => 'Website', 'short' => 'Website', 'icon' => 'ki-screen'],
            7 => ['label' => 'Mailserver', 'short' => 'Mail', 'icon' => 'ki-sms'],
            8 => ['label' => 'Google SEO & reviews', 'short' => 'Google', 'icon' => 'ki-abstract-26'],
            9 => ['label' => 'WhatsApp & Mollie', 'short' => 'Integraties', 'icon' => 'ki-setting-2'],
            10 => ['label' => 'Afronden', 'short' => 'Afronden', 'icon' => 'ki-verify'],
        ];
    }

    public static function clampStep(int $step): int
    {
        return max(1, min(self::TOTAL_STEPS, $step));
    }

    public function __construct(
        EnvService $envService,
        protected ModuleManager $moduleManager,
        protected WebsiteBuilderService $websiteBuilder
    ) {
        parent::__construct($envService);
    }

    public function start(Request $request): View|RedirectResponse
    {
        $this->authorizeWizard();

        session()->forget(self::SESSION_ACTIVE_ONBOARDING_COMPANY_ID);

        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        return view('admin.companies.wizard.step1', [
            'company' => null,
            'currentStep' => 1,
            'maxReachable' => 1,
            'wizardSteps' => self::stepMeta(),
            'branches' => $branches,
            'nexaPackages' => $this->nexaPackagesForSelect(),
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsZoom' => $googleMapsZoom,
            'googleMapsCenterLat' => $googleMapsCenterLat,
            'googleMapsCenterLng' => $googleMapsCenterLng,
            'googleMapsType' => $googleMapsType,
            'wizardAccessLockedSteps' => [],
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $this->authorizeWizard();

        $company = $this->createCompanyFromWizardRequest($request);
        $this->bindWizardTenantContext($company);
        $this->setMaxReachable($company, 2);

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 2])
            ->with('success', 'Stap 1 opgeslagen. Vul nu vestigingen in of ga verder.');
    }

    public function step(Request $request, Company $company, int $step): View|RedirectResponse
    {
        $this->assertCanViewWizardCompany($company);

        if ($step < 1 || $step > self::TOTAL_STEPS) {
            abort(404);
        }

        $maxReachable = $this->getMaxReachable($company);
        if ($step > $maxReachable) {
            return redirect()->route('admin.companies.wizard.step', [$company, $maxReachable]);
        }

        $branches = Branch::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        $company->load(['domains', 'locations', 'modules']);

        if ($step >= 2) {
            $this->bindWizardTenantContext($company);
        }

        $viewData = array_merge([
            'company' => $company,
            'currentStep' => $step,
            'maxReachable' => $maxReachable,
            'wizardSteps' => self::stepMeta(),
            'branches' => $branches,
            'nexaPackages' => $this->nexaPackagesForSelect(),
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsZoom' => $googleMapsZoom,
            'googleMapsCenterLat' => $googleMapsCenterLat,
            'googleMapsCenterLng' => $googleMapsCenterLng,
            'googleMapsType' => $googleMapsType,
        ], $this->wizardAccessViewData($company, $step));

        if (! $this->tenantConfigAccess()->canAccessWizardStep(auth()->user(), $company, $step)) {
            return view('admin.companies.wizard.step-locked', array_merge($viewData, [
                'lockedStepLabel' => self::stepMeta()[$step]['label'] ?? 'Configuratie',
                'lockedMessage' => TenantConfigAccessService::DENIED_MESSAGE,
            ]));
        }

        return match ($step) {
            1 => view('admin.companies.wizard.step1', $viewData),
            2 => view('admin.companies.wizard.step2', $viewData),
            3 => view('admin.companies.wizard.step3', $viewData),
            4 => view('admin.companies.wizard.step4', array_merge($viewData, [
                'allModules' => ModuleModel::orderBy('display_name')->get(),
            ])),
            5 => view('admin.companies.wizard.step5', array_merge($viewData, [
                'companyUsers' => User::query()
                    ->where('company_id', $company->id)
                    ->orderByDesc('created_at')
                    ->get(),
            ])),
            6 => view('admin.companies.wizard.step6', array_merge($viewData, [
                'websitePages' => $this->websiteBuilder->loadAllPagesForAdminIndex((int) $company->id, true),
                'activeTheme' => $this->websiteBuilder->getActiveTheme((int) $company->id),
            ])),
            7 => view('admin.companies.wizard.step7', array_merge($viewData, $this->mailConfigViewData($company))),
            8 => view('admin.companies.wizard.step8', array_merge($viewData, $this->googleConfigViewData($company))),
            9 => view('admin.companies.wizard.step9', array_merge($viewData, $this->integrationsConfigViewData($company))),
            10 => view('admin.companies.wizard.step10', array_merge($viewData, $this->summaryViewData($company))),
            default => abort(404),
        };
    }

    public function submitStep(Request $request, Company $company, int $step): RedirectResponse
    {
        $this->assertCanEditWizardCompany($company);

        if ($step < 1 || $step > self::TOTAL_STEPS) {
            abort(404);
        }

        $maxReachable = $this->getMaxReachable($company);
        if ($step > $maxReachable) {
            return redirect()->route('admin.companies.wizard.step', [$company, $maxReachable]);
        }

        $this->tenantConfigAccess()->assertWizardStep(auth()->user(), $company, $step);

        return match ($step) {
            1 => $this->submitStep1Update($request, $company),
            2 => $this->submitStep2($request, $company),
            3 => $this->submitStep3($request, $company),
            4 => $this->submitStep4($request, $company),
            5 => $this->submitStep5($company),
            6 => $this->submitStep6($company),
            7 => $this->submitStep7($request, $company),
            8 => $this->submitStep8($request, $company),
            9 => $this->submitStep9($request, $company),
            10 => $this->submitStep10($company),
            default => abort(404),
        };
    }

    private function submitStep1Update(Request $request, Company $company): RedirectResponse
    {
        $this->normalizeWizardStep1Phone($request);

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $this->mergeWizardIndustry($request);

        $request->validate($this->wizardStep1Rules(), $this->validationMessagesForWizardStep1());

        $data = $request->only([
            'name', 'kvk_number', 'email', 'phone', 'website', 'industry',
            'street', 'house_number', 'postal_code', 'city', 'country',
            'latitude', 'longitude', 'description',
            'contact_first_name', 'contact_last_name',
            'building_image',
            'package_key',
        ]);
        $this->applyPackageKeyFromRequest($request, $data);
        $data['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        $data['is_main'] = $request->has('is_main') ? (bool) $request->input('is_main') : false;
        $data['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : false;

        if ($request->has('branch_select') && $request->input('branch_select') !== 'other' && $request->input('branch_select') !== '') {
            $data['industry'] = $request->input('branch_select');
        } elseif ($request->has('branch_select') && $request->input('branch_select') === 'other') {
            $data['industry'] = $request->input('industry', '');
        }

        if ($request->hasFile('logo')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $data['logo_blob'] = base64_encode($fileContent);
            $data['logo_mime_type'] = $request->file('logo')->getMimeType();
            $data['logo_path'] = null;
        }

        $useLightDarkLogo = $request->input('company_logo_mode') === 'light_dark';
        if ($useLightDarkLogo && $request->hasFile('logo_dark')) {
            $darkContent = file_get_contents($request->file('logo_dark')->getRealPath());
            $data['logo_dark_blob'] = base64_encode($darkContent);
            $data['logo_dark_mime_type'] = $request->file('logo_dark')->getMimeType();
        } elseif (! $useLightDarkLogo) {
            $data['logo_dark_blob'] = null;
            $data['logo_dark_mime_type'] = null;
        }

        $company->update($data);

        $this->setMaxReachable($company, max(2, $this->getMaxReachable($company)));

        return $this->continueWizard($company, 1, 'Bedrijfsgegevens bijgewerkt.');
    }

    private function submitStep2(Request $request, Company $company): RedirectResponse
    {
        if ($request->boolean('skip_locations')) {
            return $this->continueWizard($company, 2, 'Stap overgeslagen. Ga verder met het stappenplan.');
        }

        $locationsIn = $request->input('locations', []);
        if (empty($locationsIn[0]['name'] ?? '')) {
            return $this->continueWizard($company, 2, 'Geen vestiging toegevoegd. Ga verder met het stappenplan.');
        }

        $request->validate([
            'locations' => 'nullable|array',
            'locations.*.name' => 'required_with:locations|string|max:255|min:2',
            'locations.*.street' => 'nullable|string|max:255',
            'locations.*.house_number' => 'nullable|string|max:20',
            'locations.*.postal_code' => ['nullable', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'locations.*.city' => 'nullable|string|max:255',
            'locations.*.country' => 'nullable|string|max:255',
            'locations.*.phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'locations.*.email' => 'nullable|email:rfc,dns|max:255',
            'locations.*.is_active' => 'nullable|boolean',
        ]);

        $company->refresh();
        $companyWantsMainLocation = (bool) $company->is_main;

        $locations = $request->input('locations', []);
        if (! empty($locations) && ! empty($locations[0]['name'] ?? '')) {
            $hasMain = false;
            foreach ($locations as $locationData) {
                if (empty($locationData['name'])) {
                    continue;
                }
                /* Hoofdvestiging alleen in wizard stap 1 (company.is_main); eerste vestiging volgt dat. */
                if ($companyWantsMainLocation && ! $hasMain) {
                    $locationData['is_main'] = true;
                    $hasMain = true;
                } else {
                    $locationData['is_main'] = false;
                }
                $locationData['is_active'] = isset($locationData['is_active']) ? (bool) $locationData['is_active'] : true;
                $company->locations()->create($locationData);
            }
        }

        return $this->continueWizard($company, 2, 'Vestigingen opgeslagen.');
    }

    private function submitStep3(Request $request, Company $company): RedirectResponse
    {
        if ($request->boolean('skip_domain')) {
            return $this->continueWizard($company, 3, 'Domein overgeslagen. Ga verder met het stappenplan.');
        }

        $request->merge([
            'host' => CompanyDomain::normalizeHost((string) $request->input('host', '')),
        ]);

        if ($request->filled('host')) {
            $request->validate([
                'host' => [
                    'required',
                    'string',
                    'max:255',
                    'regex:/^[a-z0-9.\-]+$/',
                    \Illuminate\Validation\Rule::unique('company_domains', 'host'),
                ],
                'is_primary' => 'sometimes|boolean',
            ], [
                'host.regex' => 'Voer een geldige hostnaam in.',
                'host.unique' => 'Deze hostnaam is al gekoppeld.',
            ]);

            $isPrimary = $request->boolean('is_primary');
            if ($isPrimary) {
                CompanyDomain::query()->where('company_id', $company->id)->update(['is_primary' => false]);
            }
            if (! $company->domains()->exists()) {
                $isPrimary = true;
            }

            $company->domains()->create([
                'host' => $request->input('host'),
                'is_primary' => $isPrimary,
            ]);
        }

        return $this->continueWizard(
            $company,
            3,
            $request->filled('host') ? 'Domein opgeslagen. Ga verder met het stappenplan.' : 'Ga verder met het stappenplan.'
        );
    }

    private function submitStep4(Request $request, Company $company): RedirectResponse
    {
        $request->validate([
            'module_ids' => 'nullable|array',
            'module_ids.*' => 'integer|exists:modules,id',
        ]);

        $ids = $request->input('module_ids', []);
        $sync = [];
        foreach ($ids as $id) {
            $sync[(int) $id] = ['settings' => null];
        }
        $company->modules()->sync($sync);

        foreach ($ids as $id) {
            $mod = ModuleModel::query()->find($id);
            if ($mod === null) {
                continue;
            }
            $name = $mod->name;
            try {
                if (! $mod->installed) {
                    $this->moduleManager->installModule($name);
                    $mod = $mod->fresh();
                }
                if ($mod && ! $mod->active) {
                    $this->moduleManager->activateModule($name);
                }
            } catch (\Throwable $e) {
                return redirect()
                    ->route('admin.companies.wizard.step', [$company, 4])
                    ->with('error', 'Module: '.$name.' — '.$e->getMessage());
            }
        }

        return $this->continueWizard($company, 4, 'Modules gekoppeld.');
    }

    private function submitStep5(Company $company): RedirectResponse
    {
        return $this->continueWizard($company, 5, 'Gebruikersstap opgeslagen.');
    }

    private function submitStep6(Company $company): RedirectResponse
    {
        return $this->continueWizard($company, 6, 'Website-stap opgeslagen.');
    }

    private function submitStep7(Request $request, Company $company): RedirectResponse
    {
        if (! $request->boolean('skip_config')) {
            $mailer = trim((string) $request->input('MAIL_MAILER', ''));
            if ($mailer !== '') {
                $rules = [
                    'MAIL_MAILER' => 'required|in:log,smtp,sendmail,mailgun,ses,postmark,resend',
                    'MAIL_HOST' => 'required_if:MAIL_MAILER,smtp|nullable|string|max:255',
                    'MAIL_PORT' => 'required_if:MAIL_MAILER,smtp|nullable|integer|min:1|max:65535',
                    'MAIL_USERNAME' => 'nullable|string|max:255',
                    'MAIL_PASSWORD' => 'nullable|string|max:255',
                    'MAIL_ENCRYPTION' => 'nullable|in:tls,ssl,null',
                    'MAIL_FROM_ADDRESS' => 'required|email|max:255',
                    'MAIL_FROM_NAME' => 'required|string|max:255',
                ];
                $request->validate($rules, [
                    'MAIL_MAILER.in' => 'Ongeldige mailer geselecteerd.',
                    'MAIL_HOST.required_if' => 'SMTP host is verplicht wanneer SMTP is geselecteerd.',
                    'MAIL_PORT.required_if' => 'SMTP poort is verplicht wanneer SMTP is geselecteerd.',
                    'MAIL_FROM_ADDRESS.required' => 'From adres is verplicht.',
                    'MAIL_FROM_ADDRESS.email' => 'From adres moet een geldig e-mailadres zijn.',
                    'MAIL_FROM_NAME.required' => 'From naam is verplicht.',
                ]);

                $mailSettings = [
                    'MAIL_MAILER' => $mailer,
                    'MAIL_HOST' => (string) $request->input('MAIL_HOST', ''),
                    'MAIL_PORT' => (string) $request->input('MAIL_PORT', '587'),
                    'MAIL_USERNAME' => (string) $request->input('MAIL_USERNAME', ''),
                    'MAIL_ENCRYPTION' => (string) $request->input('MAIL_ENCRYPTION', 'tls'),
                    'MAIL_FROM_ADDRESS' => (string) $request->input('MAIL_FROM_ADDRESS'),
                    'MAIL_FROM_NAME' => (string) $request->input('MAIL_FROM_NAME'),
                ];
                if ($request->filled('MAIL_PASSWORD')) {
                    $mailSettings['MAIL_PASSWORD'] = (string) $request->input('MAIL_PASSWORD');
                }
                foreach ($mailSettings as $key => $value) {
                    GeneralSetting::set($key, (string) $value, $company->id);
                }
            }
        }

        return $this->continueWizard(
            $company,
            7,
            $request->boolean('skip_config') || trim((string) $request->input('MAIL_MAILER', '')) === ''
                ? 'NEXA Suite-mailserver blijft van toepassing. Ga verder met het stappenplan.'
                : 'Mailserver opgeslagen. Ga verder met het stappenplan.'
        );
    }

    private function submitStep8(Request $request, Company $company): RedirectResponse
    {
        if (! $request->boolean('skip_config')) {
            $request->validate([
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
                'google_reviews_place_id' => 'nullable|string|max:255',
                'google_reviews_business_name' => 'nullable|string|max:255',
                'google_reviews_section_title' => 'nullable|string|max:255',
            ]);

            try {
                app(GoogleSeoSettingsService::class)->saveFromRequest($request->all(), $company->id);
            } catch (\InvalidArgumentException $e) {
                return redirect()
                    ->route('admin.companies.wizard.step', [$company, 8])
                    ->with('error', $e->getMessage())
                    ->withInput();
            }

            GeneralSetting::set('google_reviews_place_id', trim((string) $request->input('google_reviews_place_id', '')), $company->id);
            GeneralSetting::set('google_reviews_business_name', trim((string) $request->input('google_reviews_business_name', '')), $company->id);
            GeneralSetting::set('google_reviews_section_title', trim((string) $request->input('google_reviews_section_title', '')), $company->id);
        }

        return $this->continueWizard($company, 8, 'Google-instellingen bijgewerkt. Ga verder met het stappenplan.');
    }

    private function submitStep9(Request $request, Company $company): RedirectResponse
    {
        if (! $request->boolean('skip_config')) {
            $user = auth()->user();
            $canWhatsapp = $this->tenantConfigAccess()->can($user, $company, TenantConfigCapability::WHATSAPP);
            $canMollie = $this->tenantConfigAccess()->can($user, $company, TenantConfigCapability::MOLLIE);

            $request->validate([
                'WHATSAPP_CLICK_TO_CHAT_ENABLED' => 'nullable|in:0,1',
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => 'nullable|string|max:50',
                'WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER' => 'nullable|string|max:50',
                'WHATSAPP_WIDGET_ENABLED' => 'nullable|in:0,1',
                'WHATSAPP_WIDGET_PHONE' => 'nullable|string|max:50',
                'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => 'nullable|string|max:1000',
                'mollie_api_key' => 'nullable|string|max:255',
                'mollie_is_active' => 'nullable|in:0,1',
                'mollie_test_mode' => 'nullable|in:0,1',
                'mollie_driver_payments' => 'nullable|in:0,1',
                'mollie_booking_payments' => 'nullable|in:0,1',
                'mollie_webhook_url' => 'nullable|string|max:500',
            ]);

            if ($canWhatsapp) {
                $phoneError = 'Telefoonnummer moet een geldig Nederlands nummer zijn (bijv. 0612345678 of +31612345678).';
                $normalizedClickToChat = DutchPhoneNumber::normalizeOptionalNlToInternational(
                    trim((string) $request->input('WHATSAPP_CLICK_TO_CHAT_NUMBER', ''))
                );
                $normalizedCompanyNotify = DutchPhoneNumber::normalizeOptionalNlToInternational(
                    trim((string) $request->input('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', ''))
                );
                $normalizedWidgetPhone = DutchPhoneNumber::normalizeOptionalNlToInternational(
                    trim((string) $request->input('WHATSAPP_WIDGET_PHONE', ''))
                );
                if ($normalizedClickToChat === null) {
                    throw ValidationException::withMessages(['WHATSAPP_CLICK_TO_CHAT_NUMBER' => $phoneError]);
                }
                if ($normalizedCompanyNotify === null) {
                    throw ValidationException::withMessages(['WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER' => $phoneError]);
                }
                if ($normalizedWidgetPhone === null) {
                    throw ValidationException::withMessages(['WHATSAPP_WIDGET_PHONE' => $phoneError]);
                }

                $platformApiActive = app(\App\Services\WhatsAppBusinessService::class)->hasApiToken();
                foreach ([
                    'WHATSAPP_CLICK_TO_CHAT_ENABLED' => ($platformApiActive ? '0' : ($request->boolean('WHATSAPP_CLICK_TO_CHAT_ENABLED') ? '1' : '0')),
                    'WHATSAPP_CLICK_TO_CHAT_NUMBER' => $normalizedClickToChat,
                    'WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER' => $normalizedCompanyNotify,
                    'WHATSAPP_WIDGET_ENABLED' => $request->boolean('WHATSAPP_WIDGET_ENABLED') ? '1' : '0',
                    'WHATSAPP_WIDGET_PHONE' => $normalizedWidgetPhone,
                    'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => trim((string) $request->input('WHATSAPP_WIDGET_DEFAULT_MESSAGE', 'Hallo, ik heb een vraag over jullie diensten.')),
                ] as $key => $value) {
                    GeneralSetting::set($key, (string) $value, $company->id);
                }
            }

            if ($canMollie) {
                $entitlements = app(CompanyEntitlementService::class);
                $apiKey = trim((string) $request->input('mollie_api_key', ''));
                $existingMollie = app(PaymentProviderService::class)->mollieSummaryForCompany($company->id);
                if ($apiKey !== '' || ! empty($existingMollie['configured'])) {
                    if (! $entitlements->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS)) {
                        throw ValidationException::withMessages([
                            'mollie_api_key' => $entitlements->deniedMessage(TenantPackageCapability::MOLLIE_PAYMENTS, $company),
                        ]);
                    }
                    app(PaymentProviderService::class)->upsertMollieForCompany(
                        $company->id,
                        $apiKey !== '' ? $apiKey : null,
                        $request->input('mollie_is_active', '1') === '1',
                        $request->input('mollie_test_mode', '0') === '1',
                        trim((string) $request->input('mollie_webhook_url', '')) ?: null
                    );
                    $dispatch = app(TaxiDispatchSettingsService::class);
                    $dispatch->setPaymentDriverEnabled($request->input('mollie_driver_payments', '0') === '1', $company->id);
                    $dispatch->setPaymentBookingEnabled($request->input('mollie_booking_payments', '0') === '1', $company->id);
                }
            }
        }

        return $this->continueWizard($company, 9, 'Integraties bijgewerkt. Controleer de samenvatting en rond af.');
    }

    private function submitStep10(Company $company): RedirectResponse
    {
        try {
            $result = app(TenantOnboardingService::class)->provisionCompanyAdmin($company);
        } catch (RuntimeException $e) {
            return redirect()
                ->route('admin.companies.wizard.step', [$company, 10])
                ->with('error', $e->getMessage());
        }

        session()->forget($this->sessionKey($company));
        session()->forget(self::SESSION_ACTIVE_ONBOARDING_COMPANY_ID);

        $message = 'Tenant-onboarding afgerond.';
        if ($result['created'] && $result['mailed']) {
            $message .= ' De company-admin ('.$result['user']->email.') ontvangt de welkomstmail met uitleg voor de eerste login via een eenmalige code.';
        } elseif ($result['created'] && ! $result['mailed']) {
            $message .= ' De company-admin ('.$result['user']->email.') is aangemaakt, maar de welkomstmail kon niet worden verstuurd. Controleer de mailserver.';
        } elseif (! $result['created']) {
            $message .= ' Bestaande gebruiker '.$result['user']->email.' is als company-admin gekoppeld.';
        }

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', $message);
    }

    private function createCompanyFromWizardRequest(Request $request): Company
    {
        $this->normalizeWizardStep1Phone($request);

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $this->mergeWizardIndustry($request);

        $request->validate($this->wizardStep1Rules(), $this->validationMessagesForWizardStep1());

        $companyData = $request->only([
            'name', 'kvk_number', 'email', 'phone', 'website', 'industry',
            'street', 'house_number', 'postal_code', 'city', 'country',
            'latitude', 'longitude', 'description',
            'contact_first_name', 'contact_last_name',
            'building_image',
            'package_key',
        ]);
        $this->applyPackageKeyFromRequest($request, $companyData);
        $companyData['is_intermediary'] = $request->has('is_intermediary') ? (bool) $request->input('is_intermediary') : false;
        $companyData['is_main'] = $request->has('is_main') ? (bool) $request->input('is_main') : false;
        $companyData['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        if ($request->has('branch_select') && $request->input('branch_select') !== 'other' && $request->input('branch_select') !== '') {
            $companyData['industry'] = $request->input('branch_select');
        } elseif ($request->has('branch_select') && $request->input('branch_select') === 'other') {
            $companyData['industry'] = $request->input('industry', '');
        }

        if ($request->hasFile('logo')) {
            $fileContent = file_get_contents($request->file('logo')->getRealPath());
            $companyData['logo_blob'] = base64_encode($fileContent);
            $companyData['logo_mime_type'] = $request->file('logo')->getMimeType();
            $companyData['logo_path'] = null;
        }

        $useLightDarkLogo = $request->input('company_logo_mode') === 'light_dark';
        if ($useLightDarkLogo && $request->hasFile('logo_dark')) {
            $darkContent = file_get_contents($request->file('logo_dark')->getRealPath());
            $companyData['logo_dark_blob'] = base64_encode($darkContent);
            $companyData['logo_dark_mime_type'] = $request->file('logo_dark')->getMimeType();
        } elseif (! $useLightDarkLogo) {
            $companyData['logo_dark_blob'] = null;
            $companyData['logo_dark_mime_type'] = null;
        }

        return Company::create($companyData);
    }

    /**
     * Tijdens onboarding de zijbalk-tenant op dit bedrijf zetten, anders blokkeert
     * canAccessResource een super-admin die nog een andere tenant geselecteerd had.
     */
    private function bindWizardTenantContext(Company $company): void
    {
        session([self::SESSION_ACTIVE_ONBOARDING_COMPANY_ID => $company->id]);
        if (auth()->user()?->hasRole('super-admin')) {
            session(['selected_tenant' => $company->id]);
        }
    }

    private function authorizeWizard(): void
    {
        if (! auth()->user()->canCreateCompanies()) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function wizardStep1Rules(): array
    {
        $packageKeys = array_keys($this->nexaPackagesForSelect());

        return [
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['required', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'building_image' => 'nullable|integer|in:1,2,3',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'required|string|max:255',
            'street' => 'required|string|max:255|min:2',
            'house_number' => 'required|string|max:20|min:1',
            'postal_code' => ['required', 'string', 'max:20', 'regex:/^[1-9][0-9]{3}\s?[A-Z]{2}$/i'],
            'city' => 'required|string|max:255|min:2',
            'country' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'description' => 'nullable|string|max:5000',
            'is_intermediary' => 'nullable|boolean',
            'is_main' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'contact_first_name' => 'required|string|max:255',
            'contact_last_name' => 'required|string|max:255',
            'package_key' => ['required', 'string', 'max:80', Rule::in($packageKeys)],
            'company_logo_mode' => 'nullable|in:single,light_dark',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
            'logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
        ];
    }

    private function mergeWizardIndustry(Request $request): void
    {
        if ($request->input('branch_select') && $request->input('branch_select') !== 'other') {
            $request->merge(['industry' => $request->input('branch_select')]);
        }
    }

    /** Spaties en scheidingstekens uit telefoon halen vóór validatie (zelfde logica als client-hints). */
    private function normalizeWizardStep1Phone(Request $request): void
    {
        $phone = $request->input('phone');
        if (! is_string($phone)) {
            return;
        }
        $normalized = preg_replace('/\s+/', '', $phone);
        $normalized = str_replace(['-', '.'], '', $normalized);
        $request->merge(['phone' => $normalized]);
    }

    /**
     * Zelfde foutteksten als {@see AdminCompanyController::store()} voor stap-1 velden.
     *
     * @return array<string, string>
     */
    private function validationMessagesForWizardStep1(): array
    {
        return [
            'name.required' => 'Bedrijfsnaam is verplicht.',
            'name.min' => 'Bedrijfsnaam moet minimaal 2 tekens bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'Voer een geldig e-mailadres in.',
            'phone.required' => 'Telefoonnummer is verplicht.',
            'phone.regex' => 'Voer een geldig Nederlands telefoonnummer in (bijv. 0612345678 of +31612345678).',
            'street.required' => 'Straat is verplicht.',
            'street.min' => 'Straat moet minimaal 2 tekens bevatten.',
            'house_number.required' => 'Huisnummer is verplicht.',
            'house_number.min' => 'Huisnummer is verplicht.',
            'postal_code.required' => 'Postcode is verplicht.',
            'postal_code.regex' => 'Voer een geldige Nederlandse postcode in (bijv. 1234AB).',
            'city.required' => 'Plaats is verplicht.',
            'city.min' => 'Plaats moet minimaal 2 tekens bevatten.',
            'kvk_number.required' => 'KVK-nummer is verplicht.',
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
            'contact_first_name.required' => 'Voornaam van de contactpersoon is verplicht.',
            'contact_last_name.required' => 'Achternaam van de contactpersoon is verplicht.',
            'industry.required' => 'Branche is verplicht.',
            'package_key.required' => 'Kies een abonnementspakket.',
            'package_key.in' => 'Kies een bestaand pakket.',
            'website.url' => 'Voer een geldige URL in (bijv. https://www.voorbeeld.nl).',
            'logo.max' => 'Het logo mag maximaal 2MB groot zijn.',
            'logo_dark.max' => 'Het logo voor donkere modus mag maximaal 2MB groot zijn.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mailConfigViewData(Company $company): array
    {
        $companyId = $company->id;
        $mailSettings = [
            'MAIL_MAILER' => $this->envService->get('MAIL_MAILER', '', $companyId),
            'MAIL_HOST' => $this->envService->get('MAIL_HOST', '', $companyId),
            'MAIL_PORT' => $this->envService->get('MAIL_PORT', '587', $companyId),
            'MAIL_USERNAME' => $this->envService->get('MAIL_USERNAME', '', $companyId),
            'MAIL_ENCRYPTION' => $this->envService->get('MAIL_ENCRYPTION', 'tls', $companyId),
            'MAIL_FROM_ADDRESS' => $this->envService->get('MAIL_FROM_ADDRESS', (string) $company->email, $companyId),
            'MAIL_FROM_NAME' => $this->envService->get('MAIL_FROM_NAME', (string) $company->name, $companyId),
        ];
        $tenantHasMail = GeneralSetting::query()
            ->where('company_id', $companyId)
            ->whereIn('key', GeneralSetting::MAIL_SETTING_KEYS)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();

        if (! $tenantHasMail) {
            $mailSettings['MAIL_MAILER'] = '';
            $mailSettings['MAIL_HOST'] = '';
            $mailSettings['MAIL_USERNAME'] = '';
            $mailSettings['MAIL_PORT'] = '587';
            $mailSettings['MAIL_ENCRYPTION'] = 'tls';
            $mailSettings['MAIL_FROM_ADDRESS'] = (string) $company->email;
            $mailSettings['MAIL_FROM_NAME'] = (string) $company->name;
        }

        return [
            'mailSettings' => $mailSettings,
            'mailUsingPlatformFallback' => ! $tenantHasMail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function googleConfigViewData(Company $company): array
    {
        $companyId = $company->id;

        return [
            'seoSettings' => app(GoogleSeoSettingsService::class)->formSettings($companyId),
            'googleReviewsPlaceId' => GeneralSetting::get('google_reviews_place_id', '', $companyId),
            'googleReviewsBusinessName' => GeneralSetting::get('google_reviews_business_name', '', $companyId),
            'googleReviewsSectionTitle' => GeneralSetting::get('google_reviews_section_title', '', $companyId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function integrationsConfigViewData(Company $company): array
    {
        $companyId = $company->id;
        $entitlements = app(CompanyEntitlementService::class);
        $molliePackageAllowed = $entitlements->allows($company, TenantPackageCapability::MOLLIE_PAYMENTS);
        $paymentOptions = app(TaxiDispatchSettingsService::class)->paymentOptionsForTenant($companyId);

        return [
            'whatsappSettings' => [
                'WHATSAPP_CLICK_TO_CHAT_ENABLED' => $this->envService->get('WHATSAPP_CLICK_TO_CHAT_ENABLED', '0', $companyId),
                'WHATSAPP_CLICK_TO_CHAT_NUMBER' => $this->envService->get('WHATSAPP_CLICK_TO_CHAT_NUMBER', '', $companyId),
                'WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER' => $this->envService->get('WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER', '', $companyId),
                'WHATSAPP_WIDGET_ENABLED' => $this->envService->get('WHATSAPP_WIDGET_ENABLED', '0', $companyId),
                'WHATSAPP_WIDGET_PHONE' => $this->envService->get('WHATSAPP_WIDGET_PHONE', '', $companyId),
                'WHATSAPP_WIDGET_DEFAULT_MESSAGE' => $this->envService->get('WHATSAPP_WIDGET_DEFAULT_MESSAGE', 'Hallo, ik heb een vraag over jullie diensten.', $companyId),
            ],
            'whatsappPlatformConfigured' => app(\App\Services\WhatsAppBusinessService::class)->isConfigured(),
            'mollieSummary' => app(PaymentProviderService::class)->mollieSummaryForCompany($companyId),
            'mollieDriverPaymentsEnabled' => (bool) ($paymentOptions['driver'] ?? false),
            'mollieBookingPaymentsEnabled' => (bool) ($paymentOptions['booking'] ?? false),
            'molliePackageAllowed' => $molliePackageAllowed,
            'molliePackageDeniedMessage' => $molliePackageAllowed
                ? null
                : $entitlements->deniedMessage(TenantPackageCapability::MOLLIE_PAYMENTS, $company),
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryViewData(Company $company): array
    {
        $companyId = $company->id;
        $tenantHasMail = GeneralSetting::query()
            ->where('company_id', $companyId)
            ->whereIn('key', GeneralSetting::MAIL_SETTING_KEYS)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->exists();
        $seo = app(GoogleSeoSettingsService::class)->formSettings($companyId);
        $mollie = app(PaymentProviderService::class)->mollieSummaryForCompany($companyId);

        return [
            'packageLabel' => app(NexaPricingService::class)->packageByKey((string) ($company->package_key ?? ''))['name']
                ?? $company->package_key,
            'mailConfigured' => $tenantHasMail,
            'seoConfigured' => ($seo[GoogleSeoSettingsService::KEY_ANALYTICS_ID] ?? '') !== ''
                || ($seo[GoogleSeoSettingsService::KEY_PROPERTY_ID] ?? '') !== ''
                || ($seo[GoogleSeoSettingsService::KEY_TAG_MANAGER_ID] ?? '') !== '',
            'mollieConfigured' => ! empty($mollie['configured']),
        ];
    }

    public static function reachableStep(Company $company): int
    {
        $key = self::SESSION_PREFIX.$company->id.'.max_reachable';
        if (session()->has($key)) {
            return self::clampStep((int) session($key, 1));
        }

        return self::TOTAL_STEPS;
    }

    private function sessionKey(Company $company): string
    {
        return self::SESSION_PREFIX.$company->id.'.max_reachable';
    }

    private function getMaxReachable(Company $company): int
    {
        return self::reachableStep($company);
    }

    private function setMaxReachable(Company $company, int $step): void
    {
        session([$this->sessionKey($company) => min(self::TOTAL_STEPS, max(1, $step))]);
    }

    private function tenantConfigAccess(): TenantConfigAccessService
    {
        return app(TenantConfigAccessService::class);
    }

    private function assertCanViewWizardCompany(Company $company): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $this->bindWizardTenantContext($company);

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $user->company_id !== (int) $company->id) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        if ($user->can('view-companies') || in_array('company-admin', $user->webRoleNames(), true)) {
            return;
        }

        abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
    }

    private function assertCanEditWizardCompany(Company $company): void
    {
        $user = auth()->user();
        if ($user === null) {
            abort(403);
        }

        $this->bindWizardTenantContext($company);

        if ($user->isSuperAdmin()) {
            return;
        }

        if ((int) $user->company_id !== (int) $company->id) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        if ($user->can('edit-companies') || in_array('company-admin', $user->webRoleNames(), true)) {
            return;
        }

        abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
    }

    /**
     * @return array<string, mixed>
     */
    private function wizardAccessViewData(Company $company, int $step): array
    {
        $user = auth()->user();
        $access = $this->tenantConfigAccess();

        return [
            'wizardAccessLockedSteps' => $access->lockedWizardSteps($user, $company),
            'canConfigureWebsite' => $access->can($user, $company, TenantConfigCapability::WEBSITE),
            'canConfigureWhatsapp' => $access->can($user, $company, TenantConfigCapability::WHATSAPP),
            'canConfigureMollie' => $access->can($user, $company, TenantConfigCapability::MOLLIE),
            'configAccessUsers' => $user?->isSuperAdmin()
                ? User::query()->where('company_id', $company->id)->orderBy('first_name')->orderBy('email')->get()
                : collect(),
            'grantsByUserId' => $user?->isSuperAdmin() ? $access->grantsByUserId($company) : [],
        ];
    }

    private function continueWizard(Company $company, int $completedStep, string $success): RedirectResponse
    {
        $this->setMaxReachable($company, max($completedStep + 1, $this->getMaxReachable($company)));
        $next = $this->tenantConfigAccess()->nextAccessibleStep(auth()->user(), $company, $completedStep + 1);

        return redirect()
            ->route('admin.companies.wizard.step', [$company, $next])
            ->with('success', $success);
    }
}

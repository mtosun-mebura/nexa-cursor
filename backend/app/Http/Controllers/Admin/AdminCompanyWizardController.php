<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanyDomain;
use App\Models\Module as ModuleModel;
use App\Models\User;
use App\Services\EnvService;
use App\Services\ModuleManager;
use App\Services\WebsiteBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCompanyWizardController extends AdminCompanyController
{
    private const TOTAL_STEPS = 7;

    private const SESSION_PREFIX = 'company_wizard.';

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
            'branches' => $branches,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsZoom' => $googleMapsZoom,
            'googleMapsCenterLat' => $googleMapsCenterLat,
            'googleMapsCenterLng' => $googleMapsCenterLng,
            'googleMapsType' => $googleMapsType,
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $this->authorizeWizard();

        $company = $this->createCompanyFromWizardRequest($request);
        $this->setMaxReachable($company, 2);

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 2])
            ->with('success', 'Stap 1 opgeslagen. Vul nu vestigingen in of ga verder.');
    }

    public function step(Request $request, Company $company, int $step): View|RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('view-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bekijken.');
        }
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

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

        $viewData = [
            'company' => $company,
            'currentStep' => $step,
            'maxReachable' => $maxReachable,
            'branches' => $branches,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsZoom' => $googleMapsZoom,
            'googleMapsCenterLat' => $googleMapsCenterLat,
            'googleMapsCenterLng' => $googleMapsCenterLng,
            'googleMapsType' => $googleMapsType,
        ];

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
                    ->with('roles')
                    ->orderByDesc('created_at')
                    ->get(),
            ])),
            6 => view('admin.companies.wizard.step6', array_merge($viewData, [
                'websitePages' => $this->websiteBuilder->loadAllPagesForAdminIndex((int) $company->id, true),
                'activeTheme' => $this->websiteBuilder->getActiveTheme(),
            ])),
            7 => view('admin.companies.wizard.step7', $viewData),
            default => abort(404),
        };
    }

    public function submitStep(Request $request, Company $company, int $step): RedirectResponse
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->can('edit-companies')) {
            abort(403, 'Je hebt geen rechten om bedrijven te bewerken.');
        }
        if (! $this->canAccessResource($company)) {
            abort(403, 'Je hebt geen toegang tot dit bedrijf.');
        }

        if ($step < 1 || $step > self::TOTAL_STEPS) {
            abort(404);
        }

        $maxReachable = $this->getMaxReachable($company);
        if ($step > $maxReachable) {
            return redirect()->route('admin.companies.wizard.step', [$company, $maxReachable]);
        }

        return match ($step) {
            1 => $this->submitStep1Update($request, $company),
            2 => $this->submitStep2($request, $company),
            3 => $this->submitStep3($request, $company),
            4 => $this->submitStep4($request, $company),
            5 => $this->submitStep5($company),
            6 => $this->submitStep6($company),
            7 => $this->submitStep7($company),
            default => abort(404),
        };
    }

    private function submitStep1Update(Request $request, Company $company): RedirectResponse
    {
        $this->normalizeWizardStep1Phone($request);

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'building_image' => 'nullable|integer|in:1,2,3',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
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
            'contact_first_name' => 'nullable|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'company_logo_mode' => 'nullable|in:single,light_dark',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
            'logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
        ], $this->validationMessagesForWizardStep1());

        $data = $request->only([
            'name', 'kvk_number', 'email', 'phone', 'website', 'industry',
            'street', 'house_number', 'postal_code', 'city', 'country',
            'latitude', 'longitude', 'description',
            'contact_first_name', 'contact_last_name',
            'building_image',
        ]);
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

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 2])
            ->with('success', 'Bedrijfsgegevens bijgewerkt.');
    }

    private function submitStep2(Request $request, Company $company): RedirectResponse
    {
        if ($request->boolean('skip_locations')) {
            $this->setMaxReachable($company, max(3, $this->getMaxReachable($company)));

            return redirect()
                ->route('admin.companies.wizard.step', [$company, 3])
                ->with('success', 'Stap overgeslagen. Ga verder met domein.');
        }

        $locationsIn = $request->input('locations', []);
        if (empty($locationsIn[0]['name'] ?? '')) {
            $this->setMaxReachable($company, max(3, $this->getMaxReachable($company)));

            return redirect()
                ->route('admin.companies.wizard.step', [$company, 3])
                ->with('success', 'Geen vestiging toegevoegd. Ga verder met domein.');
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

        $this->setMaxReachable($company, max(3, $this->getMaxReachable($company)));

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 3])
            ->with('success', 'Vestigingen opgeslagen.');
    }

    private function submitStep3(Request $request, Company $company): RedirectResponse
    {
        if ($request->boolean('skip_domain')) {
            $this->setMaxReachable($company, max(4, $this->getMaxReachable($company)));

            return redirect()
                ->route('admin.companies.wizard.step', [$company, 4])
                ->with('success', 'Domein overgeslagen. Kies modules.');
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

        $this->setMaxReachable($company, max(4, $this->getMaxReachable($company)));

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 4])
            ->with('success', $request->filled('host') ? 'Domein opgeslagen. Kies modules.' : 'Ga verder met modules.');
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

        $this->setMaxReachable($company, max(5, $this->getMaxReachable($company)));

        return redirect()
            ->route('admin.companies.wizard.step', [$company, 5])
            ->with('success', 'Modules gekoppeld.');
    }

    private function submitStep5(Company $company): RedirectResponse
    {
        $this->setMaxReachable($company, max(6, $this->getMaxReachable($company)));

        return redirect()->route('admin.companies.wizard.step', [$company, 6]);
    }

    private function submitStep6(Company $company): RedirectResponse
    {
        $this->setMaxReachable($company, max(7, $this->getMaxReachable($company)));

        return redirect()->route('admin.companies.wizard.step', [$company, 7]);
    }

    private function submitStep7(Company $company): RedirectResponse
    {
        session()->forget($this->sessionKey($company));

        return redirect()
            ->route('admin.companies.show', $company)
            ->with('success', 'Tenant-onboarding afgerond.');
    }

    private function createCompanyFromWizardRequest(Request $request): Company
    {
        $this->normalizeWizardStep1Phone($request);

        $request->merge([
            'building_image' => $request->filled('building_image') ? (int) $request->input('building_image') : null,
        ]);

        $request->validate([
            'name' => 'required|string|max:255|min:2',
            'kvk_number' => ['nullable', 'string', 'max:20', 'regex:/^[0-9]{8}$/'],
            'building_image' => 'nullable|integer|in:1,2,3',
            'email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(\+31|0)[1-9][0-9]{8}$/'],
            'website' => 'nullable|url:http,https|max:255',
            'industry' => 'nullable|string|max:255',
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
            'contact_first_name' => 'nullable|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'company_logo_mode' => 'nullable|in:single,light_dark',
            'logo' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
            'logo_dark' => 'nullable|file|mimes:svg,png,jpg,jpeg|max:2048',
        ], $this->validationMessagesForWizardStep1());

        $companyData = $request->only([
            'name', 'kvk_number', 'email', 'phone', 'website', 'industry',
            'street', 'house_number', 'postal_code', 'city', 'country',
            'latitude', 'longitude', 'description',
            'contact_first_name', 'contact_last_name',
            'building_image',
        ]);
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

    private function authorizeWizard(): void
    {
        if (! auth()->user()->canCreateCompanies()) {
            abort(403, 'Je hebt geen rechten om bedrijven aan te maken.');
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
            'kvk_number.regex' => 'KVK nummer moet 8 cijfers bevatten (bijv. 12345678).',
            'website.url' => 'Voer een geldige URL in (bijv. https://www.voorbeeld.nl).',
            'logo.max' => 'Het logo mag maximaal 2MB groot zijn.',
            'logo_dark.max' => 'Het logo voor donkere modus mag maximaal 2MB groot zijn.',
        ];
    }

    private function sessionKey(Company $company): string
    {
        return self::SESSION_PREFIX.$company->id.'.max_reachable';
    }

    private function getMaxReachable(Company $company): int
    {
        return (int) session($this->sessionKey($company), 1);
    }

    private function setMaxReachable(Company $company, int $step): void
    {
        session([$this->sessionKey($company) => min(self::TOTAL_STEPS, max(1, $step))]);
    }
}

<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingService;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use App\Modules\NexaTaxi\Support\NexaTaxiSchema;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Services\CompanyEntitlementService;
use App\Services\EnvService;
use App\Support\TenantPackageCapability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class GpsTrackingController extends Controller
{
    use TenantFilter, UsesModuleDatabase;

    public function __construct(
        protected TaxiGpsTrackingService $tracking,
        protected TaxiGpsTrackingSettingsService $settings,
        protected CompanyEntitlementService $entitlements,
        protected EnvService $env
    ) {}

    public function index(): View
    {
        $this->authorizeGpsView();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);

        $maps = $this->env->mapsFormSettings();
        $centerLat = $company && $company->latitude !== null && $company->latitude !== ''
            ? (float) $company->latitude
            : (float) $maps['GOOGLE_MAPS_CENTER_LAT'];
        $centerLng = $company && $company->longitude !== null && $company->longitude !== ''
            ? (float) $company->longitude
            : (float) $maps['GOOGLE_MAPS_CENTER_LNG'];

        $companyId = $company?->id ? (int) $company->id : null;

        return view('taxi::admin.gps-tracking.index', [
            'noTenantSelected' => $companyId === null,
            'googleMapsApiKey' => $maps['GOOGLE_MAPS_API_KEY'],
            'googleMapsMapId' => $maps['GOOGLE_MAPS_MAP_ID'],
            'googleMapsZoom' => (int) $maps['GOOGLE_MAPS_ZOOM'],
            'googleMapsType' => $maps['GOOGLE_MAPS_TYPE'],
            'centerLat' => $centerLat,
            'centerLng' => $centerLng,
            'offlineCodeSet' => $this->settings->hasOfflineCode($companyId),
            'offlineUnlocked' => $this->settings->isOfflineUnlocked($companyId),
            'positionsUrl' => route('admin.taxi.gps_tracking.positions'),
            'unlockUrl' => route('admin.taxi.gps_tracking.unlock'),
            'lockUrl' => route('admin.taxi.gps_tracking.lock'),
            'codeUrl' => route('admin.taxi.gps_tracking.code'),
            'settingsUrl' => route('admin.taxi.gps_tracking.settings'),
            'canManageCode' => $this->canManageGps(),
            'appearance' => $this->settings->appearance($companyId),
        ]);
    }

    public function positions(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeGpsView();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        if ($company === null) {
            return $this->missingTenantResponse($request);
        }

        $conn = $this->moduleConnection();
        if (! NexaTaxiSchema::coreTablesExist($conn)) {
            return response()->json([
                'vehicles' => [],
                'include_offline' => false,
                'offline_code_set' => $this->settings->hasOfflineCode((int) $company->id),
                'offline_unlocked' => false,
            ]);
        }

        $view = $request->query('view') === 'offline' ? 'offline' : 'online';
        if ($view === 'offline' && ! $this->settings->isOfflineUnlocked((int) $company->id)) {
            return response()->json([
                'message' => 'Voer eerst de veiligheidscode in om offline voertuigen te zien.',
                'vehicles' => [],
                'view' => 'online',
                'offline_code_set' => $this->settings->hasOfflineCode((int) $company->id),
                'offline_unlocked' => false,
                'server_now' => now()->toIso8601String(),
            ], 422);
        }

        return response()->json($this->tracking->positions((int) $company->id, $conn, $view));
    }

    public function unlock(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeGpsView();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        if ($company === null) {
            return $this->missingTenantResponse($request);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'regex:/^\d{'.TaxiGpsTrackingSettingsService::MIN_CODE_LENGTH.','.TaxiGpsTrackingSettingsService::MAX_CODE_LENGTH.'}$/',
            ],
        ], [
            'code.required' => 'Vul de veiligheidscode in.',
            'code.regex' => 'De code bestaat uit '.TaxiGpsTrackingSettingsService::MIN_CODE_LENGTH.' tot '.TaxiGpsTrackingSettingsService::MAX_CODE_LENGTH.' cijfers.',
        ]);

        $companyId = (int) $company->id;
        $limiterKey = 'gps-offline-unlock:'.auth()->id().':'.$companyId;
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'message' => 'Te veel pogingen. Probeer het over '.$seconds.' seconden opnieuw.',
            ], 429);
        }

        if (! $this->settings->hasOfflineCode($companyId)) {
            return response()->json([
                'message' => 'Er is nog geen veiligheidscode ingesteld. Stel die eerst in via Veiligheidscode.',
            ], 422);
        }

        if (! $this->settings->codeMatches((string) $validated['code'], $companyId)) {
            RateLimiter::hit($limiterKey, 600);

            return response()->json([
                'message' => 'De veiligheidscode is onjuist.',
            ], 422);
        }

        RateLimiter::clear($limiterKey);
        $this->settings->unlockOffline($companyId);

        return response()->json([
            'ok' => true,
            'offline_unlocked' => true,
            'message' => 'Offline voertuigen zijn nu zichtbaar.',
        ]);
    }

    public function lock(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeGpsView();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        if ($company === null) {
            return $this->missingTenantResponse($request);
        }

        $this->settings->lockOffline((int) $company->id);

        return response()->json([
            'ok' => true,
            'offline_unlocked' => false,
            'message' => 'Offline voertuigen zijn weer verborgen.',
        ]);
    }

    public function updateCode(Request $request): RedirectResponse
    {
        $this->authorizeGpsManage();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        if ($company === null) {
            return redirect()
                ->route('admin.taxi.gps_tracking.index')
                ->withErrors(['tenant' => 'Selecteer eerst een bedrijf om de veiligheidscode in te stellen.']);
        }

        $companyId = (int) $company->id;
        $hasExisting = $this->settings->hasOfflineCode($companyId);
        $digitRule = 'regex:/^\d{'.TaxiGpsTrackingSettingsService::MIN_CODE_LENGTH.','.TaxiGpsTrackingSettingsService::MAX_CODE_LENGTH.'}$/';
        $digitMessage = 'De code bestaat uit '.TaxiGpsTrackingSettingsService::MIN_CODE_LENGTH.' tot '.TaxiGpsTrackingSettingsService::MAX_CODE_LENGTH.' cijfers.';
        $rules = [
            'code' => ['required', 'string', $digitRule],
            'code_confirmation' => ['required', 'string', $digitRule, 'same:code'],
        ];
        if ($hasExisting) {
            $rules['current_code'] = ['required', 'string', $digitRule];
        }

        $validated = $request->validate($rules, [
            'code.required' => 'Vul een nieuwe veiligheidscode in.',
            'code.regex' => $digitMessage,
            'code_confirmation.required' => 'Bevestig de nieuwe veiligheidscode.',
            'code_confirmation.regex' => $digitMessage,
            'code_confirmation.same' => 'De opgegeven nieuwe codes komen niet overeen.',
            'current_code.required' => 'Vul de huidige veiligheidscode in.',
            'current_code.regex' => $digitMessage,
        ]);

        if ($hasExisting && ! $this->settings->codeMatches((string) $request->input('current_code'), $companyId)) {
            return redirect()
                ->route('admin.taxi.gps_tracking.index')
                ->withErrors(['current_code' => 'De huidige veiligheidscode is onjuist.']);
        }

        $this->settings->setOfflineCode((string) $validated['code'], $companyId);
        $this->settings->lockOffline($companyId);

        return redirect()
            ->route('admin.taxi.gps_tracking.index', ['saved' => 1])
            ->with('success', 'Veiligheidscode is opgeslagen. Offline voertuigen blijven verborgen tot de code opnieuw is ingevoerd.');
    }

    public function settings(): View
    {
        $this->authorizeGpsView();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        $companyId = $company?->id ? (int) $company->id : null;
        $appearance = $this->settings->appearance($companyId);

        return view('taxi::admin.gps-tracking.settings', [
            'noTenantSelected' => $companyId === null,
            'appearance' => $appearance,
            'fleet' => $this->appearanceFleetOrEmpty($companyId, $appearance),
            'carStyles' => TaxiGpsTrackingSettingsService::carStyleLabels(),
            'carColorPresets' => TaxiGpsTrackingSettingsService::carColorPresets(),
            'minRefresh' => TaxiGpsTrackingSettingsService::MIN_REFRESH_SECONDS,
            'maxRefresh' => TaxiGpsTrackingSettingsService::MAX_REFRESH_SECONDS,
            'canManage' => $this->canManageGps(),
            'mapUrl' => route('admin.taxi.gps_tracking.index'),
            'saveUrl' => route('admin.taxi.gps_tracking.settings.update'),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeGpsManage();
        $company = $this->tenantCompany();
        $this->assertGpsAddon($company);
        if ($company === null) {
            return redirect()
                ->route('admin.taxi.gps_tracking.settings')
                ->withErrors(['tenant' => 'Selecteer eerst een bedrijf om de GPS-configuratie op te slaan.']);
        }

        $validated = $request->validate([
            'car_color_mode' => ['required', 'in:per_vehicle,single'],
            'type_colors' => ['nullable', 'array'],
            'type_colors.*' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'car_color' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'vehicle_colors' => ['nullable', 'array'],
            'vehicle_colors.*' => ['nullable', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'plate_background' => ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'plate_text_color' => ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'plate_border_color' => ['required', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'refresh_seconds' => [
                'required',
                'integer',
                'min:'.TaxiGpsTrackingSettingsService::MIN_REFRESH_SECONDS,
                'max:'.TaxiGpsTrackingSettingsService::MAX_REFRESH_SECONDS,
            ],
        ], [
            'car_color.regex' => 'Kies een geldige autokleur.',
            'type_colors.*.regex' => 'Kies een geldige kleur per type auto.',
            'vehicle_colors.*.regex' => 'Kies een geldige kleur per voertuig.',
            'plate_background.regex' => 'Kies een geldige achtergrondkleur voor het kenteken.',
            'plate_text_color.regex' => 'Kies een geldige tekstkleur voor het kenteken.',
            'plate_border_color.regex' => 'Kies een geldige randkleur voor het kenteken.',
            'refresh_seconds.required' => 'Vul het aantal seconden in.',
            'refresh_seconds.min' => 'Vernieuwen kan minimaal elke seconde.',
            'refresh_seconds.max' => 'Vernieuwen kan maximaal elke '.TaxiGpsTrackingSettingsService::MAX_REFRESH_SECONDS.' seconden.',
        ]);

        $this->settings->setAppearance($validated, (int) $company->id);

        return redirect()
            ->route('admin.taxi.gps_tracking.settings', ['saved' => 1])
            ->with('success', 'GPS-configuratie is opgeslagen.');
    }

    /**
     * JSON-endpoints zijn geen pagina. In de browser (zonder tenant) naar het dashboard;
     * AJAX/fetch vanaf de kaart blijft JSON.
     */
    private function missingTenantResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['message' => 'Selecteer eerst een bedrijf.'], 422);
        }

        return redirect()->route('admin.dashboard');
    }

    private function tenantCompany(): ?Company
    {
        $id = GeneralSetting::resolveScopeCompanyId();
        if ($id === null || $id <= 0) {
            $fallback = $this->getTenantId();
            $id = $fallback ? (int) $fallback : null;
        }
        if ($id === null || $id <= 0) {
            return null;
        }

        return Company::query()->find($id);
    }

    private function assertGpsAddon(?Company $company): void
    {
        $this->entitlements->assertAllows($company, TenantPackageCapability::GPS_TRACKING);
    }

    private function authorizeGpsView(): void
    {
        $this->authorizeOrPermissionAny(['vehicles.view', 'rides.view']);
    }

    private function authorizeGpsManage(): void
    {
        $this->authorizeOrPermissionAny(['vehicles.update', 'rides.update']);
    }

    private function canManageGps(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->can('vehicles.update') || $user->can('rides.update');
    }

    /**
     * @param  array<string, mixed>  $appearance
     * @return list<array<string, mixed>>
     */
    private function appearanceFleetOrEmpty(?int $companyId, array $appearance): array
    {
        if ($companyId === null || $companyId <= 0) {
            return [];
        }

        try {
            return $this->tracking->appearanceFleet($companyId, $this->moduleConnection(), $appearance);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  list<string>  $abilities
     */
    private function authorizeOrPermissionAny(array $abilities): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        foreach ($abilities as $ability) {
            if (auth()->user()->can($ability)) {
                return;
            }
        }
        abort(403, 'Geen rechten voor deze actie.');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GeneralSetting;
use App\Models\PaymentProvider;
use App\Services\PaymentProviderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class AdminPaymentProviderController extends Controller
{
    use TenantFilter;

    protected $paymentProviderService;

    public function __construct(PaymentProviderService $paymentProviderService)
    {
        $this->paymentProviderService = $paymentProviderService;
    }

    public function index(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders te bekijken.');
        }
        
        $query = PaymentProvider::query()->with('company');
        $this->applyTenantFilter($query);
        
        // Zoeken
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('provider_type', 'like', "%{$search}%");
            });
        }
        
        // Filter op status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Filter op provider type
        if ($request->filled('provider_type')) {
            $query->where('provider_type', $request->provider_type);
        }
        
        // Sortering
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('order', 'desc');
        
        // Valideer sorteer veld
        $allowedSortFields = ['id', 'name', 'provider_type', 'status', 'mode', 'created_at'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        
        // Speciale behandeling voor verschillende sorteervelden
        if ($sortField === 'status') {
            // Sorteer op status met logische volgorde: Inactief, Actief
            $query->orderByRaw("
                CASE 
                    WHEN is_active = false THEN 1
                    WHEN is_active = true THEN 2
                END " . $sortDirection
            );
        } else {
            $query->orderBy($sortField, $sortDirection);
        }
        
        // Haal alle providers op voor filtering en sortering op mode
        $allProviders = $query->get();
        
        // Filter op modus (test_mode in config) - moet in PHP omdat het in JSON staat
        if ($request->filled('mode')) {
            $mode = $request->mode;
            $allProviders = $allProviders->filter(function($provider) use ($mode) {
                $testMode = $provider->getConfigValue('test_mode');
                
                // Normalize test_mode to boolean
                $isTestMode = filter_var($testMode, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($isTestMode === null) {
                    // If test_mode is not set or invalid, treat as false (live mode)
                    $isTestMode = false;
                }
                
                if ($mode === 'test') {
                    return $isTestMode === true;
                } elseif ($mode === 'live') {
                    return $isTestMode === false;
                }
                return true;
            });
        }
        
        // Sorteer op mode als dat het sorteerveld is
        if ($sortField === 'mode') {
            $allProviders = $allProviders->sortBy(function($provider) use ($sortDirection) {
                $testMode = $provider->getConfigValue('test_mode', false);
                return $testMode ? 1 : 2;
            }, SORT_REGULAR, $sortDirection === 'desc');
        }
        
        // Pagineer de resultaten
        $perPage = $request->get('per_page', 25);
        $currentPage = $request->get('page', 1);
        $providers = new \Illuminate\Pagination\LengthAwarePaginator(
            $allProviders->forPage($currentPage, $perPage)->values(),
            $allProviders->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        return view('admin.payment-providers.index', compact('providers'));
    }

    public function create()
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders aan te maken.');
        }
        
        $providerTypes = [
            'mollie' => 'Mollie',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'adyen' => 'Adyen'
        ];

        $tenantCompany = $this->resolveTenantCompany();

        return view('admin.payment-providers.create', [
            'providerTypes' => $providerTypes,
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
            'scopedTenantCompany' => $tenantCompany,
            'storedTenantCompany' => null,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('create-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders aan te maken.');
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider_type' => 'required|string|in:mollie,stripe,paypal,adyen',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'test_mode' => 'boolean'
        ]);

        $companyId = $this->requireTenantCompanyId();
        if ($companyId === null) {
            return back()->withErrors([
                'provider_type' => 'Selecteer eerst een bedrijf (tenant) om een betalingsprovider aan te maken.',
            ])->withInput();
        }

        $existingProvider = PaymentProvider::where('provider_type', $request->provider_type)
            ->where('company_id', $companyId)
            ->first();
        if ($existingProvider) {
            return back()->withErrors(['provider_type' => 'Er bestaat al een provider van het type ' . ucfirst($request->provider_type) . ' voor dit bedrijf.'])->withInput();
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $config = [
            'api_key' => Crypt::encryptString($request->api_key),
            'api_secret' => $request->api_secret ? Crypt::encryptString($request->api_secret) : null,
            'webhook_url' => $request->webhook_url,
            'description' => $request->description,
            'test_mode' => $request->has('test_mode')
                || str_starts_with(trim((string) $request->api_key), 'test_'),
        ];

        PaymentProvider::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'provider_type' => $request->provider_type,
            'is_active' => $request->has('is_active'),
            'config' => $config
        ]);

        return redirect()->route('admin.payment-providers.index')
            ->with('success', 'Betalingsprovider succesvol aangemaakt.');
    }

    public function show(PaymentProvider $paymentProvider)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('view-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders te bekijken.');
        }

        if (! $this->canAccessResource($paymentProvider)) {
            abort(403);
        }
        
        $paymentProvider->loadMissing('company');

        return view('admin.payment-providers.show', [
            'paymentProvider' => $paymentProvider,
            'scopedTenantCompany' => $this->resolveTenantCompany(),
            'storedTenantCompany' => $paymentProvider->company,
        ]);
    }

    public function edit(PaymentProvider $paymentProvider)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders te bewerken.');
        }

        if (! $this->canAccessResource($paymentProvider)) {
            abort(403);
        }
        
        $providerTypes = [
            'mollie' => 'Mollie',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'adyen' => 'Adyen'
        ];

        // Decrypt config values for editing
        $decryptedConfig = [];
        if ($paymentProvider->getConfigValue('api_key')) {
            try {
                $decryptedConfig['api_key'] = Crypt::decryptString($paymentProvider->getConfigValue('api_key'));
            } catch (\Exception $e) {
                $decryptedConfig['api_key'] = '';
            }
        }
        
        if ($paymentProvider->getConfigValue('api_secret')) {
            try {
                $decryptedConfig['api_secret'] = Crypt::decryptString($paymentProvider->getConfigValue('api_secret'));
            } catch (\Exception $e) {
                $decryptedConfig['api_secret'] = '';
            }
        }

        $paymentProvider->loadMissing('company');

        return view('admin.payment-providers.edit', [
            'paymentProvider' => $paymentProvider,
            'providerTypes' => $providerTypes,
            'decryptedConfig' => $decryptedConfig,
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
            'scopedTenantCompany' => $this->resolveTenantCompany(),
            'storedTenantCompany' => $paymentProvider->company,
        ]);
    }

    public function update(Request $request, PaymentProvider $paymentProvider)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders te bewerken.');
        }

        if (! $this->canAccessResource($paymentProvider)) {
            abort(403);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'provider_type' => 'required|string|in:mollie,stripe,paypal,adyen',
            'api_key' => 'required|string',
            'api_secret' => 'nullable|string',
            'webhook_url' => 'nullable|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'test_mode' => 'boolean'
        ]);

        $companyId = (int) $paymentProvider->company_id;
        if ($companyId <= 0) {
            $resolved = $this->requireTenantCompanyId();
            if ($resolved === null) {
                return back()
                    ->withErrors(['provider_type' => 'Selecteer eerst een bedrijf (tenant) in de zijbalk.'])
                    ->withInput();
            }
            $companyId = $resolved;
        }

        $existingProvider = PaymentProvider::where('provider_type', $request->provider_type)
            ->where('company_id', $companyId)
            ->where('id', '!=', $paymentProvider->id)
            ->first();
        if ($existingProvider) {
            return back()->withErrors(['provider_type' => 'Er bestaat al een provider van het type ' . ucfirst($request->provider_type) . ' voor dit bedrijf.'])->withInput();
        }

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $config = $paymentProvider->config ?? [];
        $config['api_key'] = Crypt::encryptString($request->api_key);
        $config['api_secret'] = $request->api_secret ? Crypt::encryptString($request->api_secret) : null;
        $config['webhook_url'] = $request->webhook_url;
        $config['description'] = $request->description;
        $config['test_mode'] = $request->has('test_mode')
            || str_starts_with(trim((string) $request->api_key), 'test_');

        $paymentProvider->update([
            'company_id' => $companyId,
            'name' => $request->name,
            'provider_type' => $request->provider_type,
            'is_active' => $request->has('is_active'),
            'config' => $config
        ]);

        return redirect()->route('admin.payment-providers.index')
            ->with('success', 'Betalingsprovider succesvol bijgewerkt.');
    }

    public function destroy(PaymentProvider $paymentProvider)
    {
        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('delete-payment-providers')) {
            abort(403, 'Je hebt geen rechten om betalingsproviders te verwijderen.');
        }

        if (! $this->canAccessResource($paymentProvider)) {
            abort(403);
        }
        
        $paymentProvider->delete();

        return redirect()->route('admin.payment-providers.index')
            ->with('success', 'Betalingsprovider succesvol verwijderd.');
    }

    public function toggleStatus(PaymentProvider $paymentProvider)
    {
        if (! $this->canAccessResource($paymentProvider)) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Geen toegang tot deze provider.'], 403);
            }
            abort(403);
        }

        if (!auth()->user()->hasRole('super-admin') && !auth()->user()->can('edit-payment-providers')) {
            if (request()->ajax() || request()->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Je hebt geen rechten om de status van betalingsproviders te wijzigen.'], 403);
            }
            abort(403, 'Je hebt geen rechten om de status van betalingsproviders te wijzigen.');
        }

        $paymentProvider->update([
            'is_active' => !$paymentProvider->is_active
        ]);

        $status = $paymentProvider->is_active ? 'geactiveerd' : 'gedeactiveerd';
        
        if (request()->ajax() || request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Betalingsprovider succesvol ' . $status . '.',
                'is_active' => $paymentProvider->is_active
            ]);
        }
        
        return redirect()->route('admin.payment-providers.index')
            ->with('success', "Betalingsprovider succesvol {$status}.");
    }

    public function testConnection(PaymentProvider $paymentProvider)
    {
        if (! $this->canAccessResource($paymentProvider)) {
            return response()->json(['success' => false, 'message' => 'Geen toegang tot deze provider.'], 403);
        }

        try {
            $result = $this->paymentProviderService->testConnection($paymentProvider);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Fout bij testen van verbinding: ' . $e->getMessage()
            ]);
        }
    }

    protected function requireTenantCompanyId(): ?int
    {
        $tenantId = $this->getTenantId();
        if ($tenantId) {
            return (int) $tenantId;
        }

        $scoped = GeneralSetting::resolveScopeCompanyId();
        if ($scoped !== null && $scoped > 0) {
            return $scoped;
        }

        $userCompanyId = auth()->user()?->company_id;

        return $userCompanyId ? (int) $userCompanyId : null;
    }

    protected function resolveTenantCompany(): ?Company
    {
        $companyId = $this->requireTenantCompanyId();
        if ($companyId === null) {
            return null;
        }

        return Company::find($companyId);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NexaSuiteBookingInvoice;
use App\Models\NexaSuiteMarketplaceSetting;
use App\Services\ModuleDatabaseService;
use App\Services\NexaSuiteMarketplaceBillingService;
use App\Services\NexaSuiteMarketplaceDunningService;
use App\Services\NexaSuiteMarketplaceInvoicePdfService;
use App\Modules\NexaTaxi\Services\MarketplacePickupConfirmationService;
use App\Modules\NexaTaxi\Services\RideSettlementEligibilityService;
use App\Services\NexaSuiteMollieService;
use App\Services\PaymentProviderService;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminNexaSuiteMarketplaceController extends Controller
{
    public function index(Request $request, NexaSuiteMarketplaceBillingService $billing): View
    {
        $this->ensureSuperAdmin();
        $period = $this->requestedPeriod($request);
        $settings = NexaSuiteMarketplaceSetting::current();
        $filterCompanyId = app(AdminTenantScope::class)->optionalFilterTenantId($request);

        $rides = $billing->completedMarketplaceRidesInPeriod($period, $filterCompanyId ? (int) $filterCompanyId : null);
        $byCompany = [];
        foreach ($rides as $ride) {
            $companyId = (int) $ride->company_id;
            if ($companyId <= 0) {
                continue;
            }
            if (! isset($byCompany[$companyId])) {
                $byCompany[$companyId] = [
                    'company_id' => $companyId,
                    'ride_count' => 0,
                    'rides_subtotal' => 0.0,
                    'minimum_top_up' => 0.0,
                ];
            }
            $byCompany[$companyId]['ride_count']++;
            $byCompany[$companyId]['rides_subtotal'] += $billing->rideBillableAmount($ride);
            $byCompany[$companyId]['minimum_top_up'] += $billing->rideMinimumTopUp($ride, (float) $settings->fee_percent);
        }

        $companies = Company::query()
            ->whereIn('id', array_keys($byCompany) ?: [0])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = collect($byCompany)->map(function (array $row) use ($companies, $settings, $period) {
            $subtotal = round((float) $row['rides_subtotal'], 2);
            $fee = round(round($subtotal * ((float) $settings->fee_percent / 100), 2) + $row['minimum_top_up'], 2);

            return [
                'company' => $companies->get($row['company_id']),
                'company_id' => $row['company_id'],
                'ride_count' => $row['ride_count'],
                'rides_subtotal' => $subtotal,
                'fee_percent' => (float) $settings->fee_percent,
                'fee_amount' => $fee,
                'invoice' => NexaSuiteBookingInvoice::query()
                    ->where('company_id', $row['company_id'])
                    ->where('billing_period', $period)
                    ->where('status', '!=', 'cancelled')
                    ->first(),
            ];
        })->sortBy(fn (array $row) => strtolower((string) ($row['company']?->name ?? '')))->values();

        $invoices = NexaSuiteBookingInvoice::query()
            ->with('company')
            ->when($filterCompanyId, fn ($q) => $q->where('company_id', $filterCompanyId))
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        $allCompanies = Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.nexa-suite-bookings.index', [
            'period' => $period,
            'settings' => $settings,
            'rows' => $rows,
            'invoices' => $invoices,
            'companies' => $allCompanies,
            'filterCompanyId' => $filterCompanyId,
            'nav' => 'overview',
        ]);
    }

    public function rides(Request $request, NexaSuiteMarketplaceBillingService $billing, ModuleDatabaseService $moduleDb): View
    {
        $this->ensureSuperAdmin();
        $period = $this->requestedPeriod($request);
        $filterCompanyId = app(AdminTenantScope::class)->optionalFilterTenantId($request);
        [$from, $to] = $billing->periodBounds($period);

        $moduleDb->ensureModuleStorageReady('taxi');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $query = \App\Modules\NexaTaxi\Models\RideRequest::on($conn)
            ->where('source', \App\Modules\NexaTaxi\Models\RideRequest::SOURCE_NEXA_SUITE)
            ->whereBetween('pickup_at', [$from, $to])
            ->orderByDesc('pickup_at');

        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $rides = $query->paginate(30)->withQueryString();
        $companyIds = collect($rides->items())->pluck('company_id')->filter()->unique()->all();
        $companies = Company::query()->whereIn('id', $companyIds ?: [0])->get(['id', 'name'])->keyBy('id');
        $allCompanies = Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.nexa-suite-bookings.rides', [
            'period' => $period,
            'rides' => $rides,
            'companies' => $companies,
            'allCompanies' => $allCompanies,
            'filterCompanyId' => $filterCompanyId,
            'statusLabels' => \App\Modules\NexaTaxi\Models\RideRequest::statusLabels(),
            'nav' => 'rides',
        ]);
    }

    public function invoices(Request $request): View
    {
        $this->ensureSuperAdmin();
        $filterCompanyId = app(AdminTenantScope::class)->optionalFilterTenantId($request);
        $query = NexaSuiteBookingInvoice::query()->with('company');
        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('period')) {
            $period = parse_admin_month($request->input('period'));
            if ($period) {
                $query->where('billing_period', $period);
            }
        }

        $invoices = $query->orderByDesc('id')->paginate(20)->withQueryString();
        $companies = Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.nexa-suite-bookings.invoices', [
            'invoices' => $invoices,
            'companies' => $companies,
            'filterCompanyId' => $filterCompanyId,
            'nav' => 'invoices',
        ]);
    }

    public function showInvoice(NexaSuiteBookingInvoice $invoice): View
    {
        $this->ensureSuperAdmin();
        $invoice->load(['company', 'rides']);

        return view('admin.nexa-suite-bookings.show', [
            'invoice' => $invoice,
            'nav' => 'invoices',
        ]);
    }

    public function procedure(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.nexa-suite-bookings.procedure', [
            'nav' => 'procedure',
            'pickupRadiusMeters' => MarketplacePickupConfirmationService::PICKUP_RADIUS_METERS,
            'dropoffRadiusMeters' => MarketplacePickupConfirmationService::DROPOFF_RADIUS_METERS,
            'gpsMaxAgeMinutes' => (int) (MarketplacePickupConfirmationService::GPS_MAX_AGE_SECONDS / 60),
            'holdHours' => (int) config('taxi-dispatch.settlement_hold_hours', RideSettlementEligibilityService::HOLD_HOURS_DEFAULT),
            'reviewHours' => RideSettlementEligibilityService::HOLD_HOURS_HIGH_RISK,
            'signalHours' => (int) config('taxi-dispatch.customer_settlement_signal_hours', 48),
        ]);
    }

    public function settings(Request $request, NexaSuiteMollieService $mollie): View
    {
        $this->ensureSuperAdmin();
        $settings = NexaSuiteMarketplaceSetting::current();

        $customRaw = trim((string) $request->query('bedrag', ''));
        $customAmount = null;
        $customError = null;
        if ($customRaw !== '') {
            $normalized = str_replace(',', '.', $customRaw);
            if (! is_numeric($normalized) || (float) $normalized < 0.01 || (float) $normalized > 5000) {
                $customError = __('Vul een bedrag in tussen €0,01 en €5.000.');
            } else {
                $customAmount = round((float) $normalized, 2);
            }
        }

        return view('admin.nexa-suite-bookings.settings', [
            'settings' => $settings,
            'platformCollectEnabled' => (bool) config('nexa_payout.platform_collect_enabled', true),
            'feePricesInfo' => $this->feePricesInfo($settings),
            'feeOverview' => $this->feeOverview($customAmount),
            'feeCustomRaw' => $customRaw,
            'feeCustomError' => $customError,
            'feeMinimum' => app(\App\Services\Payout\MarketplaceFeeCalculator::class)->config(),
            'mollieConfigured' => $mollie->isConfigured(),
            'mollieApiKeyMasked' => $settings->maskedMollieApiKey(),
            'mollieKeyMode' => $mollie->keyMode(),
            'mollieFromPlatformFallback' => ! $settings->hasStoredMollieApiKey() && $mollie->usesPlatformBillingFallback(),
            'defaultTaxiWebhookUrl' => url('/api/taxi/webhooks/mollie'),
            'nav' => 'settings',
        ]);
    }

    private function feePricesInfo(NexaSuiteMarketplaceSetting $settings): string
    {
        $stored = (array) $settings->fee_minimum_settings;
        $at = ! empty($stored['prices_fetched_at'])
            ? \Illuminate\Support\Carbon::parse($stored['prices_fetched_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i')
            : null;

        return match ($stored['prices_source'] ?? null) {
            'mollie_api' => __('bij Mollie opgehaald op :at', ['at' => $at]),
            'manual' => __('handmatig aangepast').($at ? ' '.__('(laatst bij Mollie opgehaald op :at)', ['at' => $at]) : ''),
            default => __('standaardwaarden (nog niet bij Mollie opgehaald)'),
        };
    }

    /**
     * Fee en wat NEXA overhoudt per voorbeeldrit (iDEAL en kaart), via de MarketplaceFeeCalculator.
     *
     * @return list<array<string, mixed>>
     */
    private function feeOverview(?float $customAmount): array
    {
        $calculator = app(\App\Services\Payout\MarketplaceFeeCalculator::class);
        $amounts = [15.0, 25.0, 40.0, 60.0, 100.0];
        if ($customAmount !== null) {
            $amounts[] = $customAmount;
        }

        $rows = [];
        foreach ($amounts as $index => $gross) {
            $row = ['gross' => $gross, 'custom' => $customAmount !== null && $index === count($amounts) - 1];
            foreach (['ideal' => 'ideal', 'card' => 'creditcard'] as $key => $method) {
                $calc = $calculator->calculate($gross, $method);
                $kept = round($calc['fee'] - $calc['mollie_cost'], 2);
                $row[$key] = $calc + [
                    'driver' => round($gross - $calc['fee'], 2),
                    'kept' => $kept,
                    'kept_percent' => $gross > 0 ? round($kept / $gross * 100, 1) : 0.0,
                ];
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'fee_percent' => 'required|integer|min:0|max:100',
            'fee_minimum_enabled' => 'sometimes|boolean',
            'fee_methods' => 'sometimes|array',
            'fee_methods.*.fixed' => 'required_with:fee_methods|numeric|min:0|max:10',
            'fee_methods.*.percent' => 'required_with:fee_methods|numeric|min:0|max:20',
            'payout_fixed' => 'sometimes|numeric|min:0|max:10',
            'margin_fixed' => 'sometimes|numeric|min:0|max:10',
            'margin_percent' => 'sometimes|numeric|min:0|max:20',
            'allow_marketplace_network_owner' => 'sometimes|boolean',
            'auto_generate' => 'sometimes|boolean',
            'auto_send' => 'sometimes|boolean',
            'billing_day' => 'required|integer|min:1|max:28',
            'billing_time' => 'required|date_format:H:i',
            'tax_rate_percent' => 'required|integer|min:0|max:100',
            'payment_terms_days' => 'required|integer|min:1|max:365',
            'dunning_first_interval_days' => 'required|integer|min:1|max:90',
            'dunning_interval_days' => 'required|integer|min:1|max:90',
            'invoice_number_prefix' => 'required|string|max:20',
            'invoice_title' => 'nullable|string|max:120',
            'sender_email' => 'nullable|email|max:255',
            'sender_name' => 'nullable|string|max:120',
            'invoice_footer' => 'nullable|string|max:2000',
            'mollie_api_key' => 'nullable|string|max:255',
            'mollie_webhook_url' => 'nullable|url|max:500',
            'clear_mollie_api_key' => 'nullable|boolean',
        ]);

        $settings = NexaSuiteMarketplaceSetting::current();
        $feeMinimum = array_filter([
            'enabled' => $request->has('fee_minimum_enabled') ? $request->boolean('fee_minimum_enabled') : null,
            'methods' => collect($validated['fee_methods'] ?? [])
                ->only(['ideal', 'creditcard', 'applepay', 'bancontact', 'default'])
                ->map(fn (array $r) => ['fixed' => round((float) $r['fixed'], 2), 'percent' => round((float) $r['percent'], 2)])
                ->all() ?: null,
            'payout_fixed' => isset($validated['payout_fixed']) ? round((float) $validated['payout_fixed'], 2) : null,
            'margin_fixed' => isset($validated['margin_fixed']) ? round((float) $validated['margin_fixed'], 2) : null,
            'margin_percent' => isset($validated['margin_percent']) ? round((float) $validated['margin_percent'], 2) : null,
        ], fn ($v) => $v !== null);
        unset($validated['fee_minimum_enabled'], $validated['fee_methods'], $validated['payout_fixed'], $validated['margin_fixed'], $validated['margin_percent']);
        if ($feeMinimum !== [] && Schema::hasColumn('nexa_suite_marketplace_settings', 'fee_minimum_settings')) {
            $calculator = app(\App\Services\Payout\MarketplaceFeeCalculator::class);
            $before = $calculator->config()['methods'];
            $validated['fee_minimum_settings'] = array_replace_recursive((array) $settings->fee_minimum_settings, $feeMinimum);
            foreach ($feeMinimum['methods'] ?? [] as $key => $rates) {
                if (($before[$key] ?? null) !== $rates) {
                    $validated['fee_minimum_settings']['prices_source'] = 'manual';
                    break;
                }
            }
        }
        $plainMollieKey = trim((string) ($validated['mollie_api_key'] ?? ''));
        $clearMollieKey = $request->boolean('clear_mollie_api_key');
        unset($validated['mollie_api_key'], $validated['clear_mollie_api_key']);

        if (array_key_exists('mollie_webhook_url', $validated)) {
            $validated['mollie_webhook_url'] = trim((string) ($validated['mollie_webhook_url'] ?? '')) ?: null;
        }

        if ($plainMollieKey !== '') {
            if (! PaymentProviderService::isValidMollieApiKeyFormat($plainMollieKey)) {
                return back()
                    ->withInput()
                    ->withErrors(['mollie_api_key' => __('Ongeldige Mollie API-sleutel. Gebruik een test_… of live_… sleutel.')]);
            }
            if (Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_api_key')) {
                $settings->setEncryptedMollieApiKey($plainMollieKey);
            }
        } elseif ($clearMollieKey && Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_api_key')) {
            $settings->setEncryptedMollieApiKey(null);
        }

        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'mollie_webhook_url')) {
            unset($validated['mollie_webhook_url']);
        }

        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'allow_marketplace_network_owner')) {
            unset($validated['allow_marketplace_network_owner']);
        }

        $settings->fill([
            ...$validated,
            'auto_generate' => $request->boolean('auto_generate'),
            'auto_send' => $request->boolean('auto_send'),
        ]);
        if (Schema::hasColumn('nexa_suite_marketplace_settings', 'allow_marketplace_network_owner')) {
            $settings->allow_marketplace_network_owner = $request->boolean('allow_marketplace_network_owner');
        }
        $settings->save();

        return redirect()
            ->route('admin.nexa-suite-bookings.settings')
            ->with('success', __('Instellingen opgeslagen.'));
    }

    public function refreshFeePrices(\App\Services\Payout\MollieMethodPricingFetcher $fetcher): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $back = redirect()->route('admin.nexa-suite-bookings.settings');

        if (! Schema::hasColumn('nexa_suite_marketplace_settings', 'fee_minimum_settings')) {
            return $back->with('error', __('De database-update voor de minimale fee is nog niet uitgevoerd. Er is niets gewijzigd.'));
        }

        try {
            $fetched = $fetcher->fetch();
        } catch (\RuntimeException $e) {
            return $back->with('error', $e->getMessage());
        }

        $settings = NexaSuiteMarketplaceSetting::current();
        $current = app(\App\Services\Payout\MarketplaceFeeCalculator::class)->config()['methods'];
        $labels = [
            'ideal' => 'iDEAL', 'creditcard' => 'Creditcard', 'applepay' => 'Apple Pay',
            'bancontact' => 'Bancontact', 'default' => 'Overige/onbekend',
        ];
        $fmt = fn (array $r) => ($r['percent'] > 0 ? number_format($r['percent'], 2, ',', '.').'% + ' : '').'€'.number_format($r['fixed'], 2, ',', '.');

        $changes = [];
        foreach ($fetched['methods'] as $key => $rates) {
            $old = $current[$key] ?? ['fixed' => 0.0, 'percent' => 0.0];
            if (abs($old['fixed'] - $rates['fixed']) > 0.004 || abs($old['percent'] - $rates['percent']) > 0.004) {
                $changes[] = __($labels[$key] ?? $key).': '.$fmt($old).' → '.$fmt($rates);
            }
        }

        $stored = (array) $settings->fee_minimum_settings;
        $stored['prices_fetched_at'] = now()->toIso8601String();
        $stored['prices_source'] = 'mollie_api';
        if ($changes !== []) {
            $stored['methods'] = array_replace($stored['methods'] ?? [], $fetched['methods']);
        }
        $settings->update(['fee_minimum_settings' => $stored]);

        if ($changes === []) {
            return $back->with('success', __('Prijzen zijn al actueel (gecontroleerd bij Mollie). Uitbetalingskosten zijn niet via Mollie op te halen en blijven handmatig.'));
        }

        $message = __('Prijzen bij Mollie opgehaald en bijgewerkt: :changes. Uitbetalingskosten zijn niet via Mollie op te halen en blijven handmatig.', ['changes' => implode('; ', $changes)]);
        if ($fetched['skipped'] !== []) {
            $message .= ' '.__('Niet aangepast (geen bruikbaar tarief bij Mollie): :methods.', ['methods' => implode(', ', array_map(fn ($k) => isset($labels[$k]) ? __($labels[$k]) : $k, $fetched['skipped']))]);
        }

        return $back->with('success', $message);
    }

    public function generate(
        Request $request,
        NexaSuiteMarketplaceBillingService $billing,
    ): RedirectResponse {
        $this->ensureSuperAdmin();
        $period = $this->requestedPeriod($request);
        $companyId = $request->filled('company_id') ? (int) $request->input('company_id') : null;
        $send = $request->boolean('send');
        $stats = $billing->generateForPeriod($period, $send, $companyId);

        return back()->with('success', sprintf(
            __('Facturen periode %s: %d aangemaakt, %d verzonden, %d overgeslagen.'),
            $period,
            $stats['generated'],
            $stats['sent'],
            $stats['skipped']
        ));
    }

    public function send(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        if ($invoice->isPaid()) {
            return back()->with('error', __('Deze factuur is al betaald.'));
        }
        if (! $billing->sendInvoice($invoice)) {
            return back()->with('error', __('Verzenden mislukt: geen facturatie-e-mail voor deze tenant.'));
        }

        return back()->with('success', __('Factuur :number is naar de tenant verstuurd.', ['number' => $invoice->invoice_number]));
    }

    public function markPaid(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $billing->markPaid($invoice);

        return back()->with('success', __('Factuur gemarkeerd als betaald.'));
    }

    public function updateStatus(Request $request, NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled',
        ]);
        $billing->updateStatus($invoice, $validated['status']);

        return back()->with('success', __('Status bijgewerkt naar :status.', ['status' => __(NexaSuiteBookingInvoice::statusLabelFor($validated['status']))]));
    }

    public function sendReminder(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceDunningService $dunning): RedirectResponse
    {
        $this->ensureSuperAdmin();
        if (! $invoice->isOpen()) {
            return back()->with('error', __('Alleen openstaande verzonden facturen kunnen een aanmaning krijgen.'));
        }
        $level = $invoice->first_reminder_sent_at ? 2 : 1;
        if ($invoice->second_reminder_sent_at) {
            return back()->with('error', __('Er zijn al twee aanmaningen verstuurd.'));
        }
        if (! $dunning->sendReminder($invoice, $level)) {
            return back()->with('error', __('Aanmaning niet verstuurd: geen facturatie-e-mail.'));
        }

        return back()->with('success', $level === 2 ? __('Tweede aanmaning verstuurd.') : __('Eerste aanmaning verstuurd.'));
    }

    public function runNow(NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $stats = $billing->runMonthlyBilling(true);

        return back()->with('success', sprintf(
            __('Automatische facturatie gedraaid: %d aangemaakt, %d verzonden.'),
            $stats['generated'],
            $stats['sent']
        ));
    }

    public function runDunning(NexaSuiteMarketplaceDunningService $dunning): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $stats = $dunning->run();

        return back()->with('success', sprintf(
            __('Aanmaningen gecontroleerd: %d openstaand, %d eerste, %d tweede.'),
            $stats['checked'],
            $stats['first'],
            $stats['second']
        ));
    }

    public function downloadPdf(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceInvoicePdfService $pdfService): Response
    {
        $this->ensureSuperAdmin();
        $result = $pdfService->generateAndStore($invoice->fresh());
        $filename = 'nexa-suite-boekingen-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($result['bytes'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function requestedPeriod(Request $request): string
    {
        return parse_admin_month($request->input('period'))
            ?? now()->subMonthNoOverflow()->format('Y-m');
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}

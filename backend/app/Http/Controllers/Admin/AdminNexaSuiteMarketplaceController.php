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
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
                ];
            }
            $byCompany[$companyId]['ride_count']++;
            $byCompany[$companyId]['rides_subtotal'] += $billing->rideBillableAmount($ride);
        }

        $companies = Company::query()
            ->whereIn('id', array_keys($byCompany) ?: [0])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = collect($byCompany)->map(function (array $row) use ($companies, $settings, $period) {
            $subtotal = round((float) $row['rides_subtotal'], 2);
            $fee = round($subtotal * ((float) $settings->fee_percent / 100), 2);

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

    public function settings(): View
    {
        $this->ensureSuperAdmin();

        return view('admin.nexa-suite-bookings.settings', [
            'settings' => NexaSuiteMarketplaceSetting::current(),
            'nav' => 'settings',
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'fee_percent' => 'required|integer|min:0|max:100',
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
        ]);

        $settings = NexaSuiteMarketplaceSetting::current();
        $settings->fill([
            ...$validated,
            'auto_generate' => $request->boolean('auto_generate'),
            'auto_send' => $request->boolean('auto_send'),
        ]);
        $settings->save();

        return redirect()
            ->route('admin.nexa-suite-bookings.settings')
            ->with('success', 'Instellingen opgeslagen.');
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
            'Facturen periode %s: %d aangemaakt, %d verzonden, %d overgeslagen.',
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
            return back()->with('error', 'Deze factuur is al betaald.');
        }
        if (! $billing->sendInvoice($invoice)) {
            return back()->with('error', 'Verzenden mislukt: geen facturatie-e-mail voor deze tenant.');
        }

        return back()->with('success', 'Factuur '.$invoice->invoice_number.' is naar de tenant verstuurd.');
    }

    public function markPaid(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $billing->markPaid($invoice);

        return back()->with('success', 'Factuur gemarkeerd als betaald.');
    }

    public function updateStatus(Request $request, NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled',
        ]);
        $billing->updateStatus($invoice, $validated['status']);

        return back()->with('success', 'Status bijgewerkt naar '.NexaSuiteBookingInvoice::statusLabelFor($validated['status']).'.');
    }

    public function sendReminder(NexaSuiteBookingInvoice $invoice, NexaSuiteMarketplaceDunningService $dunning): RedirectResponse
    {
        $this->ensureSuperAdmin();
        if (! $invoice->isOpen()) {
            return back()->with('error', 'Alleen openstaande verzonden facturen kunnen een aanmaning krijgen.');
        }
        $level = $invoice->first_reminder_sent_at ? 2 : 1;
        if ($invoice->second_reminder_sent_at) {
            return back()->with('error', 'Er zijn al twee aanmaningen verstuurd.');
        }
        if (! $dunning->sendReminder($invoice, $level)) {
            return back()->with('error', 'Aanmaning niet verstuurd: geen facturatie-e-mail.');
        }

        return back()->with('success', $level === 2 ? 'Tweede aanmaning verstuurd.' : 'Eerste aanmaning verstuurd.');
    }

    public function runNow(NexaSuiteMarketplaceBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $stats = $billing->runMonthlyBilling(true);

        return back()->with('success', sprintf(
            'Automatische facturatie gedraaid: %d aangemaakt, %d verzonden.',
            $stats['generated'],
            $stats['sent']
        ));
    }

    public function runDunning(NexaSuiteMarketplaceDunningService $dunning): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $stats = $dunning->run();

        return back()->with('success', sprintf(
            'Aanmaningen gecontroleerd: %d openstaand, %d eerste, %d tweede.',
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

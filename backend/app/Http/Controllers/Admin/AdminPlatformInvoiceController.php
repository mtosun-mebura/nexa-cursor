<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformBillingLineItem;
use App\Models\PlatformBillingSetting;
use App\Models\PlatformInvoice;
use App\Services\PlatformBilling\PlatformBillingService;
use App\Services\PlatformBilling\PlatformDunningService;
use App\Services\PlatformBilling\PlatformInvoicePdfService;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminPlatformInvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $filterCompanyId = app(AdminTenantScope::class)->optionalFilterTenantId($request);
        $query = PlatformInvoice::query()->with(['company', 'latestPayment']);

        if ($filterCompanyId) {
            $query->where('company_id', $filterCompanyId);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->trim()->toString();
            $driver = DB::connection()->getDriverName();
            $query->where(function ($q) use ($search, $driver) {
                if ($driver === 'pgsql') {
                    $q->whereRaw('invoice_number ILIKE ?', ["%{$search}%"])
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->whereRaw('name ILIKE ?', ["%{$search}%"]));
                } else {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($companyQuery) => $companyQuery->where('name', 'like', "%{$search}%"));
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $invoices = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $invoiceCompanyIds = PlatformInvoice::query()->distinct()->pluck('company_id');
        $companies = Company::query()
            ->where(function ($q) use ($invoiceCompanyIds) {
                $q->where('is_active', true)->orWhereIn('id', $invoiceCompanyIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = PlatformInvoice::query()
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $billingSettings = PlatformBillingSetting::current();
        $dummyInvoicePreview = app(PlatformBillingService::class)->dummyWorkflowInvoicePreview();
        $dunning = app(PlatformDunningService::class);
        $dummyFirstReminder = $dunning->composeReminderMail(
            1,
            (string) $dummyInvoicePreview['company_name'],
            (string) $dummyInvoicePreview['invoice_number'],
            (string) $dummyInvoicePreview['billing_period'],
            (string) $dummyInvoicePreview['due_formatted'],
            (string) $dummyInvoicePreview['amount_formatted'],
        );
        $dummySecondReminder = $dunning->composeReminderMail(
            2,
            (string) $dummyInvoicePreview['company_name'],
            (string) $dummyInvoicePreview['invoice_number'],
            (string) $dummyInvoicePreview['billing_period'],
            (string) $dummyInvoicePreview['due_formatted'],
            (string) $dummyInvoicePreview['amount_formatted'],
        );

        return view('admin.platform-billing.invoices.index', compact(
            'invoices',
            'companies',
            'statusOptions',
            'filterCompanyId',
            'billingSettings',
            'dummyInvoicePreview',
            'dummyFirstReminder',
            'dummySecondReminder',
        ));
    }

    public function show(PlatformInvoice $invoice): View
    {
        $this->ensureSuperAdmin();
        $invoice->load(['company', 'latestPayment']);

        return view('admin.platform-billing.invoices.show', compact('invoice'));
    }

    public function edit(PlatformInvoice $invoice): View
    {
        $this->ensureSuperAdmin();
        $invoice->load(['company', 'latestPayment']);

        $taxRate = $this->taxRateForInvoice($invoice);
        $catalogLineItems = $this->catalogLineItemsForInvoice($invoice);

        return view('admin.platform-billing.invoices.edit', compact('invoice', 'taxRate', 'catalogLineItems'));
    }

    public function downloadPdf(PlatformInvoice $invoice, PlatformInvoicePdfService $pdfService): Response
    {
        $this->ensureSuperAdmin();

        try {
            $result = $pdfService->generateAndStore($invoice->fresh());
            $bytes = $result['bytes'];
        } catch (\Throwable $e) {
            abort(500, 'PDF kon niet worden gemaakt: '.$e->getMessage());
        }

        $filename = 'saas-factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function update(Request $request, PlatformInvoice $invoice, PlatformBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid',
            'payment_terms_days' => 'required|integer|min:1|max:365',
            'notes' => 'nullable|string|max:2000',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string|max:500',
            'line_items.*.quantity' => 'required|numeric|min:0.01|max:9999',
            'line_items.*.unit_price' => 'required|numeric',
            'line_items.*.type' => 'nullable|string|max:40',
            'line_items.*.billing_period' => 'nullable|string|max:20',
            'line_items.*.platform_billing_line_item_id' => 'nullable|integer',
        ]);

        $billing->updateInvoice($invoice, $validated);

        return redirect()
            ->route('admin.platform-billing.invoices.show', $invoice)
            ->with('success', 'Factuur bijgewerkt.');
    }

    public function runNow(PlatformBillingService $billing): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $count = $billing->runMonthlyBilling(now(), force: true);

        return redirect()->route('admin.platform-billing.invoices.index')
            ->with('success', $count > 0
                ? "Facturatie uitgevoerd ({$count} factuur/facturen)."
                : 'Geen nieuwe facturen aangemaakt (controleer tenant-abonnementen of bestaande facturen voor deze periode).');
    }

    public function runDunningNow(PlatformDunningService $dunning): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $stats = $dunning->run(now());

        $parts = [];
        if ($stats['paid'] > 0) {
            $parts[] = $stats['paid'].' via Mollie als betaald gemarkeerd';
        }
        if ($stats['first'] > 0) {
            $parts[] = $stats['first'].' eerste aanmaning(en)';
        }
        if ($stats['second'] > 0) {
            $parts[] = $stats['second'].' tweede aanmaning(en)';
        }
        if ($stats['blocked'] > 0) {
            $parts[] = $stats['blocked'].' tenant(s) geblokkeerd';
        }

        $message = $parts === []
            ? 'Betalingscontrole uitgevoerd. Geen aanmaningen of blokkades nodig.'
            : 'Betalingscontrole uitgevoerd: '.implode(', ', $parts).'.';

        return redirect()->route('admin.platform-billing.invoices.index')->with('success', $message);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, PlatformBillingLineItem>
     */
    private function catalogLineItemsForInvoice(PlatformInvoice $invoice)
    {
        $referencedIds = collect($invoice->line_items ?? [])
            ->pluck('platform_billing_line_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return PlatformBillingLineItem::query()
            ->where(function ($q) use ($referencedIds) {
                $q->where('is_active', true);
                if ($referencedIds->isNotEmpty()) {
                    $q->orWhereIn('id', $referencedIds);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function taxRateForInvoice(PlatformInvoice $invoice): float
    {
        $settings = PlatformBillingSetting::current();
        $issuer = is_array($invoice->issuer_details) && $invoice->issuer_details !== []
            ? $invoice->issuer_details
            : [];

        return (float) ($issuer['tax_rate'] ?? $settings->tax_rate_percent);
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}

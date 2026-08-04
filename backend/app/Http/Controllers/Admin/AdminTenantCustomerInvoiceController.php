<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Services\InvoicePdfService;
use App\Services\PlatformBilling\TenantCustomerInvoicePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AdminTenantCustomerInvoiceController extends Controller
{
    use TenantFilter;

    public function index(): View
    {
        $this->ensureTenantBillingAccess();
        $query = Invoice::query()
            ->where('module', Invoice::MODULE_CUSTOMER)
            ->with('company')
            ->orderByDesc('id');
        $this->applyTenantFilter($query);

        return view('admin.tenant-customer-invoices.index', [
            'invoices' => $query->paginate(25),
        ]);
    }

    public function create(): View
    {
        $this->ensureTenantBillingAccess();
        $companyId = $this->getTenantId() ?: (int) auth()->user()?->company_id;

        return view('admin.tenant-customer-invoices.create', [
            'settings' => InvoiceSetting::getSettingsForCompany($companyId ?: null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureTenantBillingAccess();
        $companyId = (int) ($this->getTenantId() ?: auth()->user()?->company_id);
        if ($companyId <= 0) {
            abort(403, 'Geen tenant gekoppeld.');
        }

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'description' => 'required|string|max:5000',
            'amount' => 'required|numeric|min:0.01',
            'tax_rate_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $net = round((float) $validated['amount'], 2);
        $taxRate = (float) ($validated['tax_rate_percent'] ?? 21);
        $tax = round($net * ($taxRate / 100), 2);
        $total = round($net + $tax, 2);

        $settings = InvoiceSetting::getSettingsForCompany($companyId);
        $invoiceNumber = $this->nextInvoiceNumber($companyId, $settings);

        $invoice = Invoice::query()->create([
            'invoice_number' => $invoiceNumber,
            'company_id' => $companyId,
            'module' => Invoice::MODULE_CUSTOMER,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'amount' => $net,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'currency' => 'EUR',
            'status' => 'draft',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'line_items' => [[
                'description' => $validated['description'],
                'quantity' => 1,
                'unit_price' => $net,
                'total' => $net,
            ]],
        ]);

        return redirect()->route('admin.tenant-customer-invoices.show', $invoice)
            ->with('success', 'Factuur aangemaakt.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorizeInvoice($invoice);

        return view('admin.tenant-customer-invoices.show', compact('invoice'));
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        $this->authorizeInvoice($invoice);

        try {
            $result = $pdfService->generateAndStore($invoice->fresh());
            $bytes = $result['bytes'];
        } catch (\Throwable $e) {
            abort(500, 'PDF kon niet worden gemaakt: '.$e->getMessage());
        }

        $filename = 'factuur-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $invoice->invoice_number).'.pdf';

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function sendWithPaymentLink(Request $request, Invoice $invoice, TenantCustomerInvoicePaymentService $payments): RedirectResponse
    {
        $this->authorizeInvoice($invoice);
        $validated = $request->validate([
            'recipient_email' => 'required|email|max:255',
        ]);

        try {
            $payments->createPaymentLinkAndSend($invoice, $validated['recipient_email']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Factuur met Mollie-betaallink verstuurd.');
    }

    private function ensureTenantBillingAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasRole('super-admin') || $user->hasRole('company-admin')) {
            return;
        }
        abort(403, 'Geen toegang tot klantfacturen.');
    }

    private function authorizeInvoice(Invoice $invoice): void
    {
        $this->ensureTenantBillingAccess();
        if ($invoice->module !== Invoice::MODULE_CUSTOMER) {
            abort(404);
        }
        if (! $this->canAccessResource($invoice)) {
            abort(403);
        }
    }

    private function nextInvoiceNumber(int $companyId, InvoiceSetting $settings): string
    {
        $prefix = trim((string) ($settings->invoice_prefix ?? 'INV'));
        $latest = Invoice::query()
            ->where('company_id', $companyId)
            ->where('module', Invoice::MODULE_CUSTOMER)
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return sprintf('%s-%04d', $prefix, $seq);
    }
}

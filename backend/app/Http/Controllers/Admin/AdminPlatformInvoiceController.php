<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\PlatformInvoice;
use App\Services\PlatformBilling\PlatformBillingService;
use App\Services\PlatformBilling\PlatformInvoicePdfService;
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

        $query = PlatformInvoice::query()->with('company');

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

        if ($request->filled('company_id')) {
            $query->where('company_id', (int) $request->input('company_id'));
        }

        $invoices = $query
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $companies = Company::query()
            ->whereIn('id', PlatformInvoice::query()->distinct()->pluck('company_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = PlatformInvoice::query()
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        return view('admin.platform-billing.invoices.index', compact('invoices', 'companies', 'statusOptions'));
    }

    public function show(PlatformInvoice $invoice): View
    {
        $this->ensureSuperAdmin();
        $invoice->load('company');

        return view('admin.platform-billing.invoices.show', compact('invoice'));
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
            'payment_terms_days' => 'required|integer|min:1|max:365',
        ]);

        if ($invoice->isPaid()) {
            return back()->with('error', 'Betaaltermijn kan niet meer worden gewijzigd op een betaalde factuur.');
        }

        $billing->updateInvoicePaymentTerms($invoice, (int) $validated['payment_terms_days']);

        return back()->with('success', 'Betaaltermijn bijgewerkt.');
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

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()?->hasRole('super-admin')) {
            abort(403);
        }
    }
}

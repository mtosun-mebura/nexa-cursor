<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AdminPaymentOverviewService;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    use TenantFilter;

    public function __construct(
        protected AdminPaymentOverviewService $paymentOverview
    ) {}

    public function index()
    {
        $this->ensureSuperAdmin();

        $tenantId = $this->getTenantId();
        $tenantRows = $this->paymentOverview->tenantSummaries($tenantId);
        $totals = $this->paymentOverview->globalTotals($tenantId);

        $paymentStats = [
            'pending' => $totals['open'],
            'paid' => $totals['paid'],
            'total' => $totals['total'],
        ];

        $stats = [
            'total_payments' => $totals['total'],
            'pending_payments' => $totals['open'],
            'paid_payments' => $totals['paid'],
            'total_revenue' => $totals['paid_amount'],
            'pending_revenue' => $totals['open_amount'],
        ];

        return view('admin.payments.index', compact('stats', 'paymentStats', 'tenantRows', 'tenantId'));
    }

    public function openstaand(Request $request)
    {
        $this->ensureSuperAdmin();

        $tenantId = $this->getTenantId();
        $companyId = $this->resolvePaymentCompanyId($request);
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;

        $payments = $this->paymentOverview->paginateOpenPayments($companyId, $search, 25);
        $filterCompany = $companyId ? Company::query()->find($companyId) : null;
        $tenantRows = $this->paymentOverview->tenantSummaries($tenantId);

        return view('admin.payments.openstaand', compact('payments', 'filterCompany', 'tenantRows', 'tenantId'));
    }

    public function voldaan(Request $request)
    {
        $this->ensureSuperAdmin();

        $tenantId = $this->getTenantId();
        $companyId = $this->resolvePaymentCompanyId($request);
        $search = $request->filled('search') ? trim((string) $request->input('search')) : null;

        $payments = $this->paymentOverview->paginatePaidPayments($companyId, $search, 25);
        $filterCompany = $companyId ? Company::query()->find($companyId) : null;
        $tenantRows = $this->paymentOverview->tenantSummaries($tenantId);

        $chart = $this->paymentOverview->revenueChartForPaid($companyId, 12);
        $chartLabels = $chart['labels'];
        $chartData = $chart['data'];

        return view('admin.payments.voldaan', compact('payments', 'filterCompany', 'tenantRows', 'chartLabels', 'chartData', 'tenantId'));
    }

    private function resolvePaymentCompanyId(Request $request): ?int
    {
        $tenantId = $this->getTenantId();
        if ($tenantId) {
            return (int) $tenantId;
        }

        return $request->filled('company_id') ? (int) $request->input('company_id') : null;
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot betalingen.');
        }
    }
}

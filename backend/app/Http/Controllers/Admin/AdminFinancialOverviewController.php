<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\FinancialOverviews\FinancialOverviewPeriod;
use App\Services\FinancialOverviews\TenantFinancialOverviewService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminFinancialOverviewController extends Controller
{
    use TenantFilter;

    public function __construct(
        protected TenantFinancialOverviewService $overviews
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $this->ensureAccess();
        $company = $this->resolveCompany();
        if ($company === null) {
            return view('admin.payments.overzichten.index', $this->formViewData($request, null, null, []));
        }

        $period = $this->periodFromRequest($request, false);
        $counts = $period ? $this->overviews->previewCounts($company, $period) : [];

        return view('admin.payments.overzichten.index', $this->formViewData($request, $company, $period, $counts));
    }

    public function preview(Request $request): JsonResponse
    {
        $this->ensureAccess();
        $company = $this->resolveCompany();
        if ($company === null) {
            return response()->json(['message' => 'Selecteer eerst een tenant.'], 422);
        }

        $period = $this->periodFromRequest($request, false);
        if ($period === null) {
            return response()->json(['message' => 'Ongeldige periode.'], 422);
        }

        $counts = $this->overviews->previewCounts($company, $period);

        return response()->json([
            'label' => $period->label,
            'start' => $period->start->format('d-m-Y'),
            'end' => $period->end->format('d-m-Y'),
            'invoices' => (int) ($counts['invoices'] ?? 0),
            'income' => (int) ($counts['income'] ?? 0),
            'income_amount' => number_format((float) ($counts['income_amount'] ?? 0), 2, ',', '.'),
            'audit' => (int) ($counts['audit'] ?? 0),
        ]);
    }

    public function download(Request $request): StreamedResponse|Response|RedirectResponse
    {
        $this->ensureAccess();
        $company = $this->resolveCompany();
        if ($company === null) {
            return redirect()
                ->route('admin.payments.overzichten')
                ->withErrors(['tenant' => 'Selecteer eerst een tenant om een overzicht te downloaden.']);
        }

        $validated = $request->validate([
            'report_type' => 'required|in:'.implode(',', array_keys(TenantFinancialOverviewService::reportTypes())),
            'period_type' => 'required|in:'.implode(',', FinancialOverviewPeriod::types()),
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'quarter' => 'nullable|integer|min:1|max:4',
            'half' => 'nullable|integer|min:1|max:2',
            'start_date' => 'nullable|string|max:20',
            'end_date' => 'nullable|string|max:20',
        ]);

        try {
            $period = FinancialOverviewPeriod::fromInput($validated);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['period' => $e->getMessage()]);
        }

        $type = $validated['report_type'];

        try {
            return match ($type) {
                TenantFinancialOverviewService::REPORT_INVOICES => $this->overviews->invoiceZip($company, $period),
                TenantFinancialOverviewService::REPORT_INCOME => $this->overviews->incomePdf($company, $period),
                TenantFinancialOverviewService::REPORT_VAT => $this->overviews->vatPdf($company, $period),
                TenantFinancialOverviewService::REPORT_AUDIT => $this->overviews->auditPdf($company, $period),
                default => back()->withErrors(['report_type' => 'Onbekend overzicht.']),
            };
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['period' => $e->getMessage()]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return back()->withInput()->withErrors(['period' => $e->getMessage() ?: 'Download mislukt.']);
        }
    }

    private function ensureAccess(): void
    {
        $user = auth()->user();
        if (! $user || (! $user->hasRole('super-admin') && ! $user->hasRole('company-admin'))) {
            abort(403, 'Geen toegang tot financiële overzichten.');
        }
    }

    private function resolveCompany(): ?Company
    {
        $companyId = (int) ($this->getTenantId() ?: 0);
        if ($companyId <= 0) {
            return null;
        }

        return Company::query()->find($companyId);
    }

    private function periodFromRequest(Request $request, bool $strict): ?FinancialOverviewPeriod
    {
        $input = $request->only(['period_type', 'year', 'month', 'quarter', 'half', 'start_date', 'end_date']);
        if (($input['period_type'] ?? '') === '') {
            $input['period_type'] = FinancialOverviewPeriod::TYPE_MONTH;
            $now = Carbon::now(config('app.timezone'));
            $input['year'] = $now->year;
            $input['month'] = $now->month;
        }

        try {
            return FinancialOverviewPeriod::fromInput($input);
        } catch (InvalidArgumentException $e) {
            if ($strict) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $counts
     * @return array<string, mixed>
     */
    private function formViewData(Request $request, ?Company $company, ?FinancialOverviewPeriod $period, array $counts): array
    {
        $now = Carbon::now(config('app.timezone'));
        $years = range((int) $now->year, (int) $now->year - 6);
        $months = [];
        $monthNames = [
            1 => 'januari', 2 => 'februari', 3 => 'maart', 4 => 'april',
            5 => 'mei', 6 => 'juni', 7 => 'juli', 8 => 'augustus',
            9 => 'september', 10 => 'oktober', 11 => 'november', 12 => 'december',
        ];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = $monthNames[$m];
        }

        return [
            'company' => $company,
            'period' => $period,
            'counts' => $counts,
            'reportTypes' => TenantFinancialOverviewService::reportTypes(),
            'periodTypes' => FinancialOverviewPeriod::typeLabels(),
            'years' => $years,
            'months' => $months,
            'selectedReport' => old('report_type', $request->input('report_type', TenantFinancialOverviewService::REPORT_INVOICES)),
            'selectedPeriodType' => old('period_type', $request->input('period_type', $period?->type ?? FinancialOverviewPeriod::TYPE_MONTH)),
            'selectedYear' => (int) old('year', $request->input('year', $period?->year ?? $now->year)),
            'selectedMonth' => (int) old('month', $request->input('month', $period?->month ?? $now->month)),
            'selectedQuarter' => (int) old('quarter', $request->input('quarter', $period?->quarter ?? (int) ceil($now->month / 3))),
            'selectedHalf' => (int) old('half', $request->input('half', $period?->half ?? ($now->month <= 6 ? 1 : 2))),
            'startDate' => old('start_date', $request->input('start_date', $period?->startDate())),
            'endDate' => old('end_date', $request->input('end_date', $period?->endDate())),
        ];
    }
}

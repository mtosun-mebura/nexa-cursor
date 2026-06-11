<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Modules\NexaTaxi\Models\RidePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

/**
 * Centraal overzicht van betalingen per tenant, alleen voor modules met betalingen (taxi, skillmatching).
 */
final class AdminPaymentOverviewService
{
    /** @var array<string, array{label: string, open: list<string>, paid: list<string>}> */
    public const PAYMENT_MODULES = [
        'taxi' => [
            'label' => 'Nexa Taxi',
            'open' => [
                RidePayment::STATUS_OPEN,
                RidePayment::STATUS_FAILED,
                RidePayment::STATUS_CANCELED,
                RidePayment::STATUS_EXPIRED,
            ],
            'paid' => [RidePayment::STATUS_PAID],
        ],
        'skillmatching' => [
            'label' => 'Skillmatching',
            'open' => ['pending'],
            'paid' => ['paid'],
        ],
    ];

    /**
     * @return Collection<int, Company>
     */
    public function companiesWithPaymentModules(?int $onlyCompanyId = null): Collection
    {
        $moduleNames = array_keys(self::PAYMENT_MODULES);

        $query = Company::query()
            ->where('is_active', true)
            ->whereHas('modules', function ($q) use ($moduleNames) {
                $q->whereIn('modules.name', $moduleNames)
                    ->where('modules.installed', true)
                    ->where('modules.active', true);
            })
            ->with(['modules' => function ($q) use ($moduleNames) {
                $q->whereIn('modules.name', $moduleNames)
                    ->where('modules.installed', true)
                    ->where('modules.active', true);
            }]);

        if ($onlyCompanyId !== null && $onlyCompanyId > 0) {
            $query->whereKey($onlyCompanyId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * @return list<array{
     *     company: Company,
     *     module_labels: list<string>,
     *     open_count: int,
     *     open_amount: float,
     *     paid_count: int,
     *     paid_amount: float,
     *     total_count: int
     * }>
     */
    public function tenantSummaries(?int $onlyCompanyId = null): array
    {
        $rows = [];
        foreach ($this->companiesWithPaymentModules($onlyCompanyId) as $company) {
            $rows[] = $this->summarizeCompany($company);
        }

        return $rows;
    }

    /**
     * @return array{
     *     company: Company,
     *     module_labels: list<string>,
     *     open_count: int,
     *     open_amount: float,
     *     paid_count: int,
     *     paid_amount: float,
     *     total_count: int
     * }
     */
    public function summarizeCompany(Company $company): array
    {
        $moduleNames = $this->activePaymentModuleNamesForCompany($company);
        $openCount = 0;
        $openAmount = 0.0;
        $paidCount = 0;
        $paidAmount = 0.0;

        if (in_array('taxi', $moduleNames, true) && Schema::hasTable('ride_payments')) {
            $openCount += $this->countRidePayments($company->id, self::PAYMENT_MODULES['taxi']['open']);
            $openAmount += $this->sumRidePayments($company->id, self::PAYMENT_MODULES['taxi']['open']);
            $paidCount += $this->countRidePayments($company->id, self::PAYMENT_MODULES['taxi']['paid']);
            $paidAmount += $this->sumRidePayments($company->id, self::PAYMENT_MODULES['taxi']['paid']);
        }

        if (in_array('skillmatching', $moduleNames, true) && Schema::hasTable('payments')) {
            $openCount += $this->countPayments($company->id, self::PAYMENT_MODULES['skillmatching']['open']);
            $openAmount += $this->sumPayments($company->id, self::PAYMENT_MODULES['skillmatching']['open']);
            $paidCount += $this->countPayments($company->id, self::PAYMENT_MODULES['skillmatching']['paid']);
            $paidAmount += $this->sumPayments($company->id, self::PAYMENT_MODULES['skillmatching']['paid']);
        }

        if (Schema::hasTable('invoices')) {
            $paidCount += $this->countStandalonePaidInvoices($company->id);
            $paidAmount += $this->sumStandalonePaidInvoices($company->id);
        }

        $labels = [];
        foreach ($moduleNames as $name) {
            $labels[] = self::PAYMENT_MODULES[$name]['label'] ?? $name;
        }

        return [
            'company' => $company,
            'module_labels' => $labels,
            'open_count' => $openCount,
            'open_amount' => $openAmount,
            'paid_count' => $paidCount,
            'paid_amount' => $paidAmount,
            'total_count' => $openCount + $paidCount,
        ];
    }

    /**
     * @return array{open: int, paid: int, total: int, open_amount: float, paid_amount: float}
     */
    public function globalTotals(?int $onlyCompanyId = null): array
    {
        $open = 0;
        $paid = 0;
        $openAmount = 0.0;
        $paidAmount = 0.0;

        foreach ($this->tenantSummaries($onlyCompanyId) as $row) {
            $open += $row['open_count'];
            $paid += $row['paid_count'];
            $openAmount += $row['open_amount'];
            $paidAmount += $row['paid_amount'];
        }

        return [
            'open' => $open,
            'paid' => $paid,
            'total' => $open + $paid,
            'open_amount' => $openAmount,
            'paid_amount' => $paidAmount,
        ];
    }

    /**
     * Financiële stats voor het admin-dashboard (taxi + skillmatching, alleen betaalmodules).
     *
     * @return array{
     *     total_revenue: float,
     *     pending_revenue: float,
     *     paid_payments: int,
     *     pending_payments: int,
     *     average_ticket: float,
     *     revenue_trend: Collection<int, array{month: string, label: string, total: float}>,
     *     recent_payments: Collection<int, stdClass>,
     *     tenant_rows: list<array>|null,
     *     payment_stats: array{pending: int, paid: int, total: int}
     * }
     */
    public function dashboardFinancials(?int $tenantId = null, int $trendMonths = 6): array
    {
        if ($tenantId !== null && $tenantId > 0) {
            $company = $this->findCompanyWithPaymentModules($tenantId);
            if ($company === null) {
                return $this->emptyDashboardFinancials($trendMonths);
            }

            $summary = $this->summarizeCompany($company);
            $paidCount = $summary['paid_count'];
            $chart = $this->revenueChartForPaid($tenantId, $trendMonths);

            return [
                'total_revenue' => $summary['paid_amount'],
                'pending_revenue' => $summary['open_amount'],
                'paid_payments' => $paidCount,
                'pending_payments' => $summary['open_count'],
                'average_ticket' => $paidCount > 0
                    ? round($summary['paid_amount'] / $paidCount, 2)
                    : 0.0,
                'revenue_trend' => $this->chartToRevenueTrend($chart),
                'recent_payments' => $this->recentPaymentsForDashboard($tenantId, 5),
                'tenant_rows' => null,
                'payment_stats' => [
                    'pending' => $summary['open_count'],
                    'paid' => $paidCount,
                    'total' => $summary['total_count'],
                ],
            ];
        }

        $totals = $this->globalTotals();
        $paidCount = $totals['paid'];
        $chart = $this->revenueChartForPaid(null, $trendMonths);

        return [
            'total_revenue' => $totals['paid_amount'],
            'pending_revenue' => $totals['open_amount'],
            'paid_payments' => $paidCount,
            'pending_payments' => $totals['open'],
            'average_ticket' => $paidCount > 0
                ? round($totals['paid_amount'] / $paidCount, 2)
                : 0.0,
            'revenue_trend' => $this->chartToRevenueTrend($chart),
            'recent_payments' => $this->recentPaymentsForDashboard(null, 5),
            'tenant_rows' => $this->tenantSummaries(),
            'payment_stats' => [
                'pending' => $totals['open'],
                'paid' => $paidCount,
                'total' => $totals['total'],
            ],
        ];
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function recentPaymentsForDashboard(?int $tenantId, int $limit = 5): Collection
    {
        return $this->collectPaidPayments($tenantId, null)
            ->concat($this->collectOpenPayments($tenantId, null))
            ->sortByDesc(fn (stdClass $item) => $item->sort_at)
            ->take($limit)
            ->values();
    }

    /**
     * @param  array{labels: list<string>, data: list<float|int>}  $chart
     * @return Collection<int, array{month: string, label: string, total: float}>
     */
    private function chartToRevenueTrend(array $chart): Collection
    {
        $trend = collect();
        foreach ($chart['labels'] as $index => $label) {
            $trend->push([
                'month' => (string) $index,
                'label' => $label,
                'total' => (float) ($chart['data'][$index] ?? 0),
            ]);
        }

        return $trend;
    }

    /**
     * @return array{
     *     total_revenue: float,
     *     pending_revenue: float,
     *     paid_payments: int,
     *     pending_payments: int,
     *     average_ticket: float,
     *     revenue_trend: Collection<int, array{month: string, label: string, total: float}>,
     *     recent_payments: Collection<int, stdClass>,
     *     tenant_rows: null,
     *     payment_stats: array{pending: int, paid: int, total: int}
     * }
     */
    private function emptyDashboardFinancials(int $trendMonths): array
    {
        $chart = $this->revenueChartForPaid(null, $trendMonths);

        return [
            'total_revenue' => 0.0,
            'pending_revenue' => 0.0,
            'paid_payments' => 0,
            'pending_payments' => 0,
            'average_ticket' => 0.0,
            'revenue_trend' => $this->chartToRevenueTrend($chart),
            'recent_payments' => collect(),
            'tenant_rows' => null,
            'payment_stats' => ['pending' => 0, 'paid' => 0, 'total' => 0],
        ];
    }

    /**
     * @return LengthAwarePaginator<int, stdClass>
     */
    public function paginateOpenPayments(?int $companyId, ?string $search, int $perPage = 25): LengthAwarePaginator
    {
        return $this->paginatePaymentItems(
            $this->collectOpenPayments($companyId, $search),
            $perPage
        );
    }

    /**
     * @return LengthAwarePaginator<int, stdClass>
     */
    public function paginatePaidPayments(?int $companyId, ?string $search, int $perPage = 25): LengthAwarePaginator
    {
        return $this->paginatePaymentItems(
            $this->collectPaidPayments($companyId, $search),
            $perPage
        );
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function collectOpenPayments(?int $companyId, ?string $search): Collection
    {
        return $this->collectPaymentsByBucket('open', $companyId, $search);
    }

    /**
     * @return Collection<int, stdClass>
     */
    public function collectPaidPayments(?int $companyId, ?string $search): Collection
    {
        return $this->collectPaymentsByBucket('paid', $companyId, $search);
    }

    /**
     * @return list<string>
     */
    public function revenueChartForPaid(?int $companyId = null, int $months = 12): array
    {
        $labels = [];
        $monthKeys = [];
        $dataByMonth = [];
        $start = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $monthKeys[] = $key;
            $labels[] = $month->translatedFormat('M Y');
            $dataByMonth[$key] = 0.0;
        }

        $companyIds = $this->resolveCompanyIds($companyId);
        if ($companyIds === []) {
            return ['labels' => $labels, 'data' => array_values($dataByMonth)];
        }

        if (Schema::hasTable('payments')) {
            $rows = Payment::query()
                ->whereIn('company_id', $companyIds)
                ->where('status', 'paid')
                ->where(function ($q) use ($start) {
                    $q->where('paid_at', '>=', $start)
                        ->orWhere(function ($nested) use ($start) {
                            $nested->whereNull('paid_at')->where('created_at', '>=', $start);
                        });
                })
                ->get(['amount', 'paid_at', 'created_at']);

            foreach ($rows as $row) {
                $at = $row->paid_at ?? $row->created_at;
                if ($at === null) {
                    continue;
                }
                $key = $at->format('Y-m');
                if (array_key_exists($key, $dataByMonth)) {
                    $dataByMonth[$key] += (float) $row->amount;
                }
            }
        }

        if (Schema::hasTable('ride_payments')) {
            $rideRows = DB::table('ride_payments')
                ->whereIn('company_id', $companyIds)
                ->where('status', RidePayment::STATUS_PAID)
                ->where('paid_at', '>=', $start)
                ->get(['amount', 'paid_at']);

            foreach ($rideRows as $row) {
                if ($row->paid_at === null) {
                    continue;
                }
                $key = \Carbon\Carbon::parse($row->paid_at)->format('Y-m');
                if (array_key_exists($key, $dataByMonth)) {
                    $dataByMonth[$key] += (float) $row->amount;
                }
            }
        }

        if (Schema::hasTable('invoices')) {
            foreach ($this->standalonePaidInvoicesQuery($companyIds)->get() as $invoice) {
                $at = $invoice->paid_date ?? $invoice->invoice_date ?? $invoice->updated_at;
                if ($at === null) {
                    continue;
                }
                $key = $at->format('Y-m');
                if (array_key_exists($key, $dataByMonth)) {
                    $dataByMonth[$key] += (float) $invoice->total_amount;
                }
            }
        }

        $data = [];
        foreach ($monthKeys as $key) {
            $data[] = $dataByMonth[$key];
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function collectPaymentsByBucket(string $bucket, ?int $companyId, ?string $search): Collection
    {
        $items = collect();
        $companyIds = $this->resolveCompanyIds($companyId);
        if ($companyIds === []) {
            return $items;
        }

        $companies = Company::query()->whereIn('id', $companyIds)->get()->keyBy('id');

        foreach ($companies as $company) {
            $moduleNames = $this->activePaymentModuleNamesForCompany($company);

            if (in_array('skillmatching', $moduleNames, true) && Schema::hasTable('payments')) {
                $statuses = self::PAYMENT_MODULES['skillmatching'][$bucket];
                $query = Payment::query()
                    ->with(['invoice', 'jobMatch'])
                    ->where('company_id', $company->id)
                    ->whereIn('status', $statuses);

                if ($search !== null && $search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                            ->orWhere('id', 'like', "%{$search}%");
                    });
                }

                foreach ($query->orderByDesc($bucket === 'paid' ? 'paid_at' : 'created_at')->get() as $payment) {
                    $items->push($this->mapSkillmatchingPayment($payment, $company));
                }
            }

            if (in_array('taxi', $moduleNames, true) && Schema::hasTable('ride_payments')) {
                $statuses = self::PAYMENT_MODULES['taxi'][$bucket];
                $query = DB::table('ride_payments')
                    ->where('company_id', $company->id)
                    ->whereIn('status', $statuses);

                if ($search !== null && $search !== '') {
                    $like = '%'.$search.'%';
                    $query->where(function ($q) use ($like) {
                        $q->where('ride_request_id', 'like', $like)
                            ->orWhere('mollie_payment_id', 'like', $like);
                    });
                }

                $orderCol = $bucket === 'paid' ? 'paid_at' : 'created_at';
                foreach ($query->orderByDesc($orderCol)->get() as $row) {
                    $items->push($this->mapRidePayment($row, $company));
                }
            }

            if ($bucket === 'paid' && Schema::hasTable('invoices')) {
                $invoiceQuery = $this->standalonePaidInvoicesQuery([$company->id]);

                if ($search !== null && $search !== '') {
                    $like = '%'.$search.'%';
                    $invoiceQuery->where(function ($q) use ($like) {
                        $q->where('invoice_number', 'like', $like)
                            ->orWhere('customer_name', 'like', $like)
                            ->orWhere('customer_email', 'like', $like);
                    });
                }

                foreach ($invoiceQuery->orderByDesc('paid_date')->orderByDesc('invoice_date')->get() as $invoice) {
                    $items->push($this->mapInvoice($invoice, $company));
                }
            }
        }

        return $items->sortByDesc(fn (stdClass $item) => $item->sort_at)->values();
    }

    private function mapSkillmatchingPayment(Payment $payment, Company $company): stdClass
    {
        $item = new stdClass;
        $item->source = 'skillmatching';
        $item->source_label = self::PAYMENT_MODULES['skillmatching']['label'];
        $item->id = (int) $payment->id;
        $item->company_id = (int) $payment->company_id;
        $item->company_name = $company->name;
        $item->amount = (float) $payment->amount;
        $item->currency = $payment->currency ?? 'EUR';
        $item->status = (string) $payment->status;
        $item->status_label = $payment->status === 'paid' ? 'Voldaan' : 'Openstaand';
        $item->reference = $payment->invoice?->invoice_number ?? ('#'.$payment->id);
        $item->reference_url = $payment->invoice_id
            ? route('admin.invoices.show', $payment->invoice_id)
            : null;
        $item->occurred_at = $payment->paid_at ?? $payment->created_at;
        $item->sort_at = optional($item->occurred_at)->timestamp ?? 0;

        return $item;
    }

    private function mapInvoice(Invoice $invoice, Company $company): stdClass
    {
        $isTaxi = $invoice->module === Invoice::MODULE_TAXI;
        $item = new stdClass;
        $item->source = $isTaxi ? 'taxi' : 'skillmatching';
        $item->source_label = $isTaxi
            ? self::PAYMENT_MODULES['taxi']['label']
            : 'Factuur';
        $item->id = (int) $invoice->id;
        $item->company_id = (int) $invoice->company_id;
        $item->company_name = $company->name;
        $item->amount = (float) $invoice->total_amount;
        $item->currency = $invoice->currency ?? 'EUR';
        $item->status = 'paid';
        $item->status_label = 'Voldaan';
        $item->reference = $invoice->invoice_number;
        $item->reference_url = route('admin.invoices.show', $invoice->id);
        $item->occurred_at = $invoice->paid_date ?? $invoice->invoice_date ?? $invoice->updated_at;
        $item->sort_at = optional($item->occurred_at)->timestamp ?? 0;

        return $item;
    }

    private function mapRidePayment(object $row, Company $company): stdClass
    {
        $status = (string) $row->status;
        $item = new stdClass;
        $item->source = 'taxi';
        $item->source_label = self::PAYMENT_MODULES['taxi']['label'];
        $item->id = (int) $row->id;
        $item->company_id = (int) $row->company_id;
        $item->company_name = $company->name;
        $item->amount = (float) $row->amount;
        $item->currency = $row->currency ?? 'EUR';
        $item->status = $status;
        $item->status_label = $status === RidePayment::STATUS_PAID
            ? 'Voldaan'
            : 'Openstaand';
        $item->reference = 'Rit #'.(int) $row->ride_request_id;
        $item->reference_url = route('admin.taxi.ride_requests.show', (int) $row->ride_request_id);
        $item->occurred_at = $row->paid_at ?? $row->created_at;
        $item->sort_at = $item->occurred_at ? \Carbon\Carbon::parse($item->occurred_at)->timestamp : 0;

        return $item;
    }

    /**
     * @param  Collection<int, stdClass>  $items
     * @return LengthAwarePaginator<int, stdClass>
     */
    private function paginatePaymentItems(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page']
        );
    }

    /**
     * @return list<int>
     */
    private function findCompanyWithPaymentModules(int $companyId): ?Company
    {
        $moduleNames = array_keys(self::PAYMENT_MODULES);

        return Company::query()
            ->where('id', $companyId)
            ->with(['modules' => function ($q) use ($moduleNames) {
                $q->whereIn('modules.name', $moduleNames)
                    ->where('modules.installed', true)
                    ->where('modules.active', true);
            }])
            ->first();
    }

    private function resolveCompanyIds(?int $companyId): array
    {
        if ($companyId !== null && $companyId > 0) {
            $company = $this->findCompanyWithPaymentModules($companyId);
            if ($company === null || $this->activePaymentModuleNamesForCompany($company) === []) {
                return [];
            }

            return [(int) $companyId];
        }

        return $this->companiesWithPaymentModules()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @return list<string>
     */
    private function activePaymentModuleNamesForCompany(Company $company): array
    {
        $allowed = array_keys(self::PAYMENT_MODULES);
        $names = [];
        foreach ($company->modules as $module) {
            $name = strtolower((string) $module->name);
            if (in_array($name, $allowed, true)) {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $statuses
     */
    private function countRidePayments(int $companyId, array $statuses): int
    {
        return (int) DB::table('ride_payments')
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->count();
    }

    /**
     * @param  list<string>  $statuses
     */
    private function sumRidePayments(int $companyId, array $statuses): float
    {
        return (float) DB::table('ride_payments')
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->sum('amount');
    }

    /**
     * @param  list<string>  $statuses
     */
    private function countPayments(int $companyId, array $statuses): int
    {
        return (int) Payment::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->count();
    }

    /**
     * @param  list<string>  $statuses
     */
    private function sumPayments(int $companyId, array $statuses): float
    {
        return (float) Payment::query()
            ->where('company_id', $companyId)
            ->whereIn('status', $statuses)
            ->sum('amount');
    }

    private function countStandalonePaidInvoices(int $companyId): int
    {
        return (int) $this->standalonePaidInvoicesQuery([$companyId])->count();
    }

    private function sumStandalonePaidInvoices(int $companyId): float
    {
        return (float) $this->standalonePaidInvoicesQuery([$companyId])->sum('total_amount');
    }

    /**
     * Betaalde facturen die nog niet via payments/ride_payments in het overzicht zitten.
     *
     * @param  list<int>  $companyIds
     */
    private function standalonePaidInvoicesQuery(array $companyIds): Builder
    {
        $coveredInvoiceIds = [];
        $coveredRideIds = [];

        if (Schema::hasTable('payments')) {
            $coveredInvoiceIds = Payment::query()
                ->whereIn('company_id', $companyIds)
                ->where('status', 'paid')
                ->whereNotNull('invoice_id')
                ->pluck('invoice_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if (Schema::hasTable('ride_payments')) {
            $coveredRideIds = DB::table('ride_payments')
                ->whereIn('company_id', $companyIds)
                ->where('status', RidePayment::STATUS_PAID)
                ->pluck('ride_request_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $query = Invoice::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', 'paid');

        if ($coveredInvoiceIds !== []) {
            $query->whereNotIn('id', $coveredInvoiceIds);
        }

        $query->where(function (Builder $q) use ($coveredRideIds) {
            $q->where('module', '!=', Invoice::MODULE_TAXI)
                ->orWhereNull('module')
                ->orWhereNull('module_reference_id');

            if ($coveredRideIds === []) {
                $q->orWhere('module', Invoice::MODULE_TAXI);
            } else {
                $q->orWhere(function (Builder $nested) use ($coveredRideIds) {
                    $nested->where('module', Invoice::MODULE_TAXI)
                        ->whereNotIn('module_reference_id', $coveredRideIds);
                });
            }
        });

        return $query;
    }
}

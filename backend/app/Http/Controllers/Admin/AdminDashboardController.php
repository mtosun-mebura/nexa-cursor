<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\Skillmatching\Models\Interview;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Modules\Skillmatching\Models\Vacancy;
use App\Services\AdminDashboardModuleContext;
use App\Services\AdminPaymentOverviewService;
use App\Services\EnvService;
use App\Services\ModuleDatabaseService;
use App\Services\SystemStackSnapshotService;
use App\Support\ModuleSchemaAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use TenantFilter;

    protected $envService;

    public function __construct(
        EnvService $envService,
        protected AdminPaymentOverviewService $paymentOverview,
        protected AdminDashboardModuleContext $dashboardModuleContext,
        protected ModuleDatabaseService $moduleDatabaseService,
        protected SystemStackSnapshotService $stackSnapshots,
    ) {
        $this->envService = $envService;
    }

    public function index()
    {
        $tenantId = $this->getTenantId();
        $modules = $this->dashboardModuleContext->resolve($tenantId);
        $showSkillmatching = $modules['show_skillmatching'];
        $showTaxi = $modules['show_taxi'];

        if (auth()->user()->hasRole('super-admin') && ! session('selected_tenant')) {
            $tenantId = null;
            $stats = $this->buildSuperAdminStats($showSkillmatching);
            $financials = $this->paymentOverview->dashboardFinancials(null);
            $recent_users = User::with('company')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            $recent_companies = Company::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            $recent_vacancies = $this->recentVacancies($tenantId, $showSkillmatching);
        } else {
            if (! $tenantId) {
                $stats = array_merge(
                    $this->baseStats(null),
                    $showSkillmatching ? $this->skillmatchingStats(null) : $this->emptySkillmatchingStats(),
                );
                $financials = $this->paymentOverview->dashboardFinancials(null);
                $recent_users = User::with('company')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_companies = Company::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_vacancies = $this->recentVacancies(null, $showSkillmatching);
            } else {
                $stats = array_merge(
                    $this->baseStats($tenantId),
                    $showSkillmatching ? $this->skillmatchingStats($tenantId) : $this->emptySkillmatchingStats(),
                );
                $financials = $this->paymentOverview->dashboardFinancials($tenantId);
                $recent_users = User::where('company_id', $tenantId)
                    ->with('company')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_companies = Company::where('id', $tenantId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_vacancies = $this->recentVacancies($tenantId, $showSkillmatching);
            }
        }

        $recent_matches = $showSkillmatching
            ? JobMatch::with(['candidate', 'vacancy.company'])
                ->when($tenantId !== null, function ($query) use ($tenantId) {
                    $query->whereHas('vacancy', function ($q) use ($tenantId) {
                        $q->where('company_id', $tenantId);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
            : collect();

        $upcoming_interviews = $showSkillmatching
            ? Interview::with(['match.candidate', 'match.vacancy'])
                ->when($tenantId !== null, function ($query) use ($tenantId) {
                    $query->where('company_id', $tenantId);
                })
                ->where('scheduled_at', '>=', now()->startOfDay())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
            : collect();

        $taxiDashboard = $showTaxi
            ? $this->buildTaxiDashboardData($tenantId)
            : ['stats' => $this->emptyTaxiStats(), 'recent_rides' => collect()];

        $recent_payments = $financials['recent_payments'];
        $revenue_trend = $financials['revenue_trend'];
        $tenantPaymentRows = $financials['tenant_rows'] ?? null;
        $paymentStats = $financials['payment_stats'] ?? null;

        $selectedCompany = null;
        $isCompanyView = false;
        if ($tenantId) {
            $companyWith = [
                'locations',
                'mainLocation',
                'users' => function ($q) {
                    $q->limit(8);
                },
            ];
            if ($showSkillmatching) {
                $companyWith['vacancies'] = function ($q) {
                    $q->where('status', 'active');
                };
            }
            $selectedCompany = Company::with($companyWith)->find($tenantId);

            if (! $selectedCompany && $tenantId) {
                $selectedCompany = Company::find($tenantId);
            }

            $isCompanyView = $selectedCompany !== null;
        }

        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        $systemStack = null;
        $releaseVersion = null;
        if (auth()->user()->hasRole('super-admin') && ! session('selected_tenant')) {
            $systemStack = $this->stackSnapshots->labeledStack();
            $releaseVersion = $this->stackSnapshots->currentReleaseVersion();
        }

        return view('admin.dashboard', [
            'stats' => $stats,
            'recent_users' => $recent_users,
            'recent_companies' => $recent_companies,
            'recent_vacancies' => $recent_vacancies,
            'financials' => $financials,
            'recent_matches' => $recent_matches,
            'upcoming_interviews' => $upcoming_interviews,
            'recent_payments' => $recent_payments,
            'revenue_trend' => $revenue_trend,
            'selectedCompany' => $selectedCompany,
            'isCompanyView' => $isCompanyView,
            'tenantId' => $tenantId,
            'googleMapsApiKey' => $googleMapsApiKey,
            'googleMapsZoom' => $googleMapsZoom,
            'googleMapsCenterLat' => $googleMapsCenterLat,
            'googleMapsCenterLng' => $googleMapsCenterLng,
            'googleMapsType' => $googleMapsType,
            'tenantPaymentRows' => $tenantPaymentRows,
            'paymentStats' => $paymentStats,
            'showSkillmatching' => $showSkillmatching,
            'showTaxi' => $showTaxi,
            'taxiStats' => $taxiDashboard['stats'],
            'recent_rides' => $taxiDashboard['recent_rides'],
            'systemStack' => $systemStack,
            'releaseVersion' => $releaseVersion,
        ]);
    }

    public function switchTenant(Request $request)
    {
        $tenantId = $request->input('tenant_id');

        if ($tenantId) {
            session(['selected_tenant' => $tenantId]);
        } else {
            session()->forget('selected_tenant');
        }

        $afterUrl = $this->sanitizeTenantSwitchRedirect($request->input('redirect'))
            ?? route('admin.dashboard');
        $afterUrl = $this->syncTenantCompanyInAdminRedirect(
            $afterUrl,
            $tenantId !== null && $tenantId !== '' ? (int) $tenantId : null
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.',
                'redirect' => $afterUrl,
            ]);
        }

        return redirect()->to($afterUrl)
            ->with('success', $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.');
    }

    private function sanitizeTenantSwitchRedirect(mixed $raw): ?string
    {
        if (! is_string($raw)) {
            return null;
        }
        $path = trim($raw);
        if ($path === '' || strlen($path) > 2048) {
            return null;
        }
        if (str_contains($path, "\r") || str_contains($path, "\n")) {
            return null;
        }
        if (str_starts_with($path, '//')) {
            return null;
        }
        if (! str_starts_with($path, '/admin')) {
            return null;
        }

        return $path;
    }

    private function syncTenantCompanyInAdminRedirect(string $path, ?int $tenantCompanyId): string
    {
        $queryString = '';
        $pathname = $path;
        if (str_contains($path, '?')) {
            [$pathname, $queryString] = explode('?', $path, 2);
        }

        $query = [];
        if ($queryString !== '') {
            parse_str($queryString, $query);
        }

        $shouldSync = array_key_exists('tenant_company', $query)
            || preg_match('#^/admin/website-pages(?:/|$)#', $pathname) === 1;

        if (! $shouldSync) {
            return $path;
        }

        if ($tenantCompanyId !== null) {
            $query['tenant_company'] = $tenantCompanyId;
        } else {
            unset($query['tenant_company']);
        }

        $newQuery = http_build_query($query);

        return $pathname.($newQuery !== '' ? '?'.$newQuery : '');
    }

    /**
     * @return array<string, int|float|Collection>
     */
    protected function buildTaxiDashboardData(?int $tenantId): array
    {
        if (! ModuleSchemaAvailability::rideRequestsTableExists()) {
            return ['stats' => $this->emptyTaxiStats(), 'recent_rides' => collect()];
        }

        $this->moduleDatabaseService->ensureModuleStorageReady('taxi');
        $conn = $this->moduleDatabaseService->getModuleConnectionName('taxi');

        $baseQuery = RideRequest::on($conn);
        if ($tenantId !== null) {
            $baseQuery->where('company_id', $tenantId);
        }

        $pendingStatuses = [
            RideRequest::STATUS_DRAFT,
            RideRequest::STATUS_QUOTED,
            RideRequest::STATUS_PENDING_PAYMENT,
            RideRequest::STATUS_PENDING_DISPATCH,
            RideRequest::STATUS_OFFERED,
        ];

        $activeStatuses = [
            RideRequest::STATUS_ACCEPTED,
            RideRequest::STATUS_ASSIGNED,
        ];

        return [
            'stats' => [
                'total_rides' => (clone $baseQuery)->count(),
                'pending_rides' => (clone $baseQuery)->whereIn('status', $pendingStatuses)->count(),
                'active_rides' => (clone $baseQuery)->whereIn('status', $activeStatuses)->count(),
                'completed_rides' => (clone $baseQuery)->where('status', RideRequest::STATUS_COMPLETED)->count(),
            ],
            'recent_rides' => (clone $baseQuery)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ];
    }

    protected function recentVacancies(?int $tenantId, bool $showSkillmatching): Collection
    {
        if (! $showSkillmatching) {
            return collect();
        }

        $query = Vacancy::with(['company', 'category'])
            ->orderBy('created_at', 'desc')
            ->limit(5);

        if ($tenantId !== null) {
            $query->where('company_id', $tenantId);
        }

        return $query->get();
    }

    /**
     * @return array<string, int>
     */
    protected function baseStats(?int $tenantId): array
    {
        if ($tenantId === null) {
            return [
                'total_users' => User::count(),
                'total_companies' => Company::count(),
            ];
        }

        return [
            'total_users' => User::where('company_id', $tenantId)->count(),
            'total_companies' => Company::where('id', $tenantId)->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function skillmatchingStats(?int $tenantId): array
    {
        if ($tenantId === null) {
            return [
                'total_vacancies' => Vacancy::count(),
                'total_matches' => JobMatch::count(),
                'total_interviews' => Interview::count(),
                'active_vacancies' => Vacancy::where('status', 'active')->count(),
                'pending_matches' => JobMatch::where('status', 'pending')->count(),
                'completed_interviews' => Interview::where('scheduled_at', '<', now())->count(),
            ];
        }

        return [
            'total_vacancies' => Vacancy::where('company_id', $tenantId)->count(),
            'total_matches' => JobMatch::whereHas('vacancy', function ($q) use ($tenantId) {
                $q->where('company_id', $tenantId);
            })->count(),
            'total_interviews' => Interview::where('company_id', $tenantId)->count(),
            'active_vacancies' => Vacancy::where('company_id', $tenantId)->where('status', 'active')->count(),
            'pending_matches' => JobMatch::whereHas('vacancy', function ($q) use ($tenantId) {
                $q->where('company_id', $tenantId);
            })->where('status', 'pending')->count(),
            'completed_interviews' => Interview::where('company_id', $tenantId)->where('scheduled_at', '<', now())->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function emptySkillmatchingStats(): array
    {
        return [
            'total_vacancies' => 0,
            'total_matches' => 0,
            'total_interviews' => 0,
            'active_vacancies' => 0,
            'pending_matches' => 0,
            'completed_interviews' => 0,
            'candidates' => 0,
            'accepted_matches' => 0,
            'rejected_matches' => 0,
            'interview_matches' => 0,
            'hired_matches' => 0,
            'interviews_leading_to_match' => 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function emptyTaxiStats(): array
    {
        return [
            'total_rides' => 0,
            'pending_rides' => 0,
            'active_rides' => 0,
            'completed_rides' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildSuperAdminStats(bool $includeSkillmatching): array
    {
        $totalUsers = User::count();
        $totalCompanies = Company::count();

        $usersPerCompany = User::select('company_id', DB::raw('count(*) as user_count'))
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'company_name' => $item->company->name ?? 'Onbekend',
                    'user_count' => $item->user_count,
                ];
            });

        $candidates = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['super-admin', 'company-admin', 'company-staff']);
        })->count();

        $invoicesPerYear = Invoice::select(
            DB::raw('EXTRACT(YEAR FROM invoice_date)::integer as year'),
            DB::raw('count(*) as invoice_count'),
            DB::raw('COALESCE(sum(total_amount), 0) as total_revenue')
        )
            ->whereNotNull('invoice_date')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM invoice_date)'))
            ->orderBy('year', 'desc')
            ->get();

        $currentYearInvoices = Invoice::whereYear('invoice_date', now()->year)->count();
        $currentYearRevenue = Invoice::whereYear('invoice_date', now()->year)
            ->where('status', 'paid')
            ->sum('total_amount') ?? 0;

        $base = [
            'total_users' => $totalUsers,
            'total_companies' => $totalCompanies,
            'candidates' => $candidates,
            'users_per_company' => $usersPerCompany,
            'invoices_per_year' => $invoicesPerYear,
            'current_year_invoices' => $currentYearInvoices,
            'current_year_revenue' => $currentYearRevenue,
        ];

        if (! $includeSkillmatching || ! ModuleSchemaAvailability::vacanciesTableExists()) {
            return array_merge($base, $this->emptySkillmatchingStats(), [
                'vacancies_per_company' => collect(),
            ]);
        }

        $matchStatuses = JobMatch::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $vacanciesPerCompany = Vacancy::select('company_id', DB::raw('count(*) as vacancy_count'))
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'company_name' => $item->company->name ?? 'Onbekend',
                    'vacancy_count' => $item->vacancy_count,
                ];
            });

        $interviewsLeadingToMatch = Interview::whereHas('match', function ($q) {
            $q->whereIn('status', ['hired', 'accepted']);
        })->count();

        return array_merge($base, [
            'total_vacancies' => Vacancy::count(),
            'total_matches' => JobMatch::count(),
            'total_interviews' => Interview::count(),
            'active_vacancies' => Vacancy::where('status', 'active')->count(),
            'pending_matches' => $matchStatuses['pending'] ?? 0,
            'accepted_matches' => $matchStatuses['accepted'] ?? 0,
            'rejected_matches' => $matchStatuses['rejected'] ?? 0,
            'interview_matches' => $matchStatuses['interview'] ?? 0,
            'hired_matches' => $matchStatuses['hired'] ?? 0,
            'completed_interviews' => Interview::where('scheduled_at', '<', now())->count(),
            'interviews_leading_to_match' => $interviewsLeadingToMatch,
            'vacancies_per_company' => $vacanciesPerCompany,
        ]);
    }
}

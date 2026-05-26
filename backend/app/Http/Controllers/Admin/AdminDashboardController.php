<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use App\Services\AdminPaymentOverviewService;
use App\Modules\Skillmatching\Models\Interview;
use App\Modules\Skillmatching\Models\JobMatch;
use App\Modules\Skillmatching\Models\Vacancy;
use App\Services\EnvService;
use App\Support\ModuleSchemaAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    use TenantFilter;

    protected $envService;

    public function __construct(
        EnvService $envService,
        protected AdminPaymentOverviewService $paymentOverview,
    ) {
        $this->envService = $envService;
    }

    public function index()
    {
        $tenantId = $this->getTenantId();
        $skillmatchingOnDefault = ModuleSchemaAvailability::vacanciesTableExists();

        // Voor Super Admin, toon alle data als geen tenant geselecteerd
        if (auth()->user()->hasRole('super-admin') && ! session('selected_tenant')) {
            $tenantId = null; // Reset voor super admin zonder tenant
            $stats = $this->buildSuperAdminStats();

            $financials = $this->paymentOverview->dashboardFinancials(null);
            $recent_users = User::with('company')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recent_companies = Company::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recent_vacancies = $skillmatchingOnDefault
                ? Vacancy::with(['company', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get()
                : collect();
        } else {
            // Tenant-specifieke data
            if (! $tenantId) {
                // Fallback naar super admin view als geen tenantId
                $stats = $skillmatchingOnDefault ? [
                    'total_users' => User::count(),
                    'total_companies' => Company::count(),
                    'total_vacancies' => Vacancy::count(),
                    'total_matches' => JobMatch::count(),
                    'total_interviews' => Interview::count(),
                    'active_vacancies' => Vacancy::where('status', 'active')->count(),
                    'pending_matches' => JobMatch::where('status', 'pending')->count(),
                    'completed_interviews' => Interview::where('scheduled_at', '<', now())->count(),
                ] : [
                    'total_users' => User::count(),
                    'total_companies' => Company::count(),
                    'total_vacancies' => 0,
                    'total_matches' => 0,
                    'total_interviews' => 0,
                    'active_vacancies' => 0,
                    'pending_matches' => 0,
                    'completed_interviews' => 0,
                ];
                $financials = $this->paymentOverview->dashboardFinancials(null);
                $recent_users = User::with('company')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_companies = Company::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_vacancies = $skillmatchingOnDefault
                    ? Vacancy::with(['company', 'category'])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                    : collect();
            } else {
                $stats = $skillmatchingOnDefault ? [
                    'total_users' => User::where('company_id', $tenantId)->count(),
                    'total_companies' => Company::where('id', $tenantId)->count(),
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
                ] : [
                    'total_users' => User::where('company_id', $tenantId)->count(),
                    'total_companies' => Company::where('id', $tenantId)->count(),
                    'total_vacancies' => 0,
                    'total_matches' => 0,
                    'total_interviews' => 0,
                    'active_vacancies' => 0,
                    'pending_matches' => 0,
                    'completed_interviews' => 0,
                ];

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

                $recent_vacancies = $skillmatchingOnDefault
                    ? Vacancy::where('company_id', $tenantId)
                        ->with(['company', 'category'])
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                    : collect();
            }
        }

        $recent_matches = $skillmatchingOnDefault
            ? JobMatch::with(['candidate', 'vacancy.company'])
                ->when(isset($tenantId), function ($query) use ($tenantId) {
                    $query->whereHas('vacancy', function ($q) use ($tenantId) {
                        $q->where('company_id', $tenantId);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
            : collect();

        $upcoming_interviews = $skillmatchingOnDefault
            ? Interview::with(['match.candidate', 'match.vacancy'])
                ->when(isset($tenantId), function ($query) use ($tenantId) {
                    $query->where('company_id', $tenantId);
                })
                ->where('scheduled_at', '>=', now()->startOfDay())
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get()
            : collect();

        $recent_payments = $financials['recent_payments'];
        $revenue_trend = $financials['revenue_trend'];
        $tenantPaymentRows = $financials['tenant_rows'] ?? null;
        $paymentStats = $financials['payment_stats'] ?? null;

        // Als een tenant geselecteerd is, toon de company profile opzet
        $selectedCompany = null;
        $isCompanyView = false;
        if ($tenantId) {
            $companyWith = [
                'locations',
                'mainLocation',
                'users' => function ($q) {
                    $q->limit(8); // Limit for avatar display
                },
            ];
            if ($skillmatchingOnDefault) {
                $companyWith['vacancies'] = function ($q) {
                    $q->where('status', 'active');
                };
            }
            $selectedCompany = Company::with($companyWith)->find($tenantId);

            // Als company niet gevonden wordt, maar tenantId bestaat, probeer zonder eager loading
            if (! $selectedCompany && $tenantId) {
                $selectedCompany = Company::find($tenantId);
            }

            // Alleen company view tonen als company gevonden is
            $isCompanyView = $selectedCompany !== null;
        }

        $googleMapsApiKey = $this->envService->getGoogleMapsApiKey();
        $googleMapsZoom = $this->envService->get('GOOGLE_MAPS_ZOOM', '12');
        $googleMapsCenterLat = $this->envService->get('GOOGLE_MAPS_CENTER_LAT', '52.3676');
        $googleMapsCenterLng = $this->envService->get('GOOGLE_MAPS_CENTER_LNG', '4.9041');
        $googleMapsType = $this->envService->get('GOOGLE_MAPS_TYPE', 'roadmap');

        return view('admin.dashboard', compact(
            'stats',
            'recent_users',
            'recent_companies',
            'recent_vacancies',
            'financials',
            'recent_matches',
            'upcoming_interviews',
            'recent_payments',
            'revenue_trend',
            'selectedCompany',
            'isCompanyView',
            'tenantId',
            'googleMapsApiKey',
            'googleMapsZoom',
            'googleMapsCenterLat',
            'googleMapsCenterLng',
            'googleMapsType',
            'tenantPaymentRows',
            'paymentStats'
        ));
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

    /**
     * Alleen interne admin-paden toestaan (geen open redirect).
     */
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

    /**
     * Bouw uitgebreide statistieken voor Super Admin dashboard.
     */
    protected function buildSuperAdminStats(): array
    {
        $totalUsers = User::count();
        $totalCompanies = Company::count();

        // Gebruikers per bedrijf
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

        // Kandidaten (gebruikers zonder company_id of met specifieke rollen)
        $candidates = User::whereDoesntHave('roles', function ($q) {
            $q->whereIn('name', ['super-admin', 'company-admin', 'company-staff']);
        })->count();

        // Facturen per jaar (PostgreSQL compatible)
        $invoicesPerYear = Invoice::select(
            DB::raw('EXTRACT(YEAR FROM invoice_date)::integer as year'),
            DB::raw('count(*) as invoice_count'),
            DB::raw('COALESCE(sum(total_amount), 0) as total_revenue')
        )
            ->whereNotNull('invoice_date')
            ->groupBy(DB::raw('EXTRACT(YEAR FROM invoice_date)'))
            ->orderBy('year', 'desc')
            ->get();

        // Facturen van dit jaar
        $currentYearInvoices = Invoice::whereYear('invoice_date', now()->year)->count();
        $currentYearRevenue = Invoice::whereYear('invoice_date', now()->year)
            ->where('status', 'paid')
            ->sum('total_amount') ?? 0;

        if (! ModuleSchemaAvailability::vacanciesTableExists()) {
            return [
                'total_users' => $totalUsers,
                'total_companies' => $totalCompanies,
                'total_vacancies' => 0,
                'total_matches' => 0,
                'total_interviews' => 0,
                'active_vacancies' => 0,
                'pending_matches' => 0,
                'accepted_matches' => 0,
                'rejected_matches' => 0,
                'interview_matches' => 0,
                'hired_matches' => 0,
                'completed_interviews' => 0,
                'interviews_leading_to_match' => 0,
                'candidates' => $candidates,
                'users_per_company' => $usersPerCompany,
                'vacancies_per_company' => collect(),
                'invoices_per_year' => $invoicesPerYear,
                'current_year_invoices' => $currentYearInvoices,
                'current_year_revenue' => $currentYearRevenue,
            ];
        }

        $totalVacancies = Vacancy::count();
        $totalMatches = JobMatch::count();
        $totalInterviews = Interview::count();

        // Vacatures per bedrijf
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

        // Match statussen
        $matchStatuses = JobMatch::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Interviews die tot match hebben geleid (interviews waarvan de match status 'hired' of 'accepted' is)
        $interviewsLeadingToMatch = Interview::whereHas('match', function ($q) {
            $q->whereIn('status', ['hired', 'accepted']);
        })->count();

        return [
            'total_users' => $totalUsers,
            'total_companies' => $totalCompanies,
            'total_vacancies' => $totalVacancies,
            'total_matches' => $totalMatches,
            'total_interviews' => $totalInterviews,
            'active_vacancies' => Vacancy::where('status', 'active')->count(),
            'pending_matches' => $matchStatuses['pending'] ?? 0,
            'accepted_matches' => $matchStatuses['accepted'] ?? 0,
            'rejected_matches' => $matchStatuses['rejected'] ?? 0,
            'interview_matches' => $matchStatuses['interview'] ?? 0,
            'hired_matches' => $matchStatuses['hired'] ?? 0,
            'completed_interviews' => Interview::where('scheduled_at', '<', now())->count(),
            'interviews_leading_to_match' => $interviewsLeadingToMatch,
            'candidates' => $candidates,
            'users_per_company' => $usersPerCompany,
            'vacancies_per_company' => $vacanciesPerCompany,
            'invoices_per_year' => $invoicesPerYear,
            'current_year_invoices' => $currentYearInvoices,
            'current_year_revenue' => $currentYearRevenue,
        ];
    }

}

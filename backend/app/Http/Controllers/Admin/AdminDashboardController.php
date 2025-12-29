<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\User;
use App\Models\Company;
use App\Models\Vacancy;
use App\Models\JobMatch;
use App\Models\Interview;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\EnvService;

class AdminDashboardController extends Controller
{
    use TenantFilter;

    protected $envService;

    public function __construct(EnvService $envService)
    {
        $this->envService = $envService;
    }
    
    public function index()
    {
        $tenantId = $this->getTenantId();
        
        // Voor Super Admin, toon alle data als geen tenant geselecteerd
        if (auth()->user()->hasRole('super-admin') && !session('selected_tenant')) {
            $tenantId = null; // Reset voor super admin zonder tenant
            $stats = $this->buildSuperAdminStats();

            $financials = $this->buildFinancialStats(Payment::query());
            $recent_users = User::with('company')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recent_companies = Company::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            $recent_vacancies = Vacancy::with(['company', 'category'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } else {
            // Tenant-specifieke data
            if (!$tenantId) {
                // Fallback naar super admin view als geen tenantId
                $stats = [
                    'total_users' => User::count(),
                    'total_companies' => Company::count(),
                    'total_vacancies' => Vacancy::count(),
                    'total_matches' => \App\Models\JobMatch::count(),
                    'total_interviews' => Interview::count(),
                    'active_vacancies' => Vacancy::where('status', 'active')->count(),
                    'pending_matches' => \App\Models\JobMatch::where('status', 'pending')->count(),
                    'completed_interviews' => Interview::where('scheduled_at', '<', now())->count(),
                ];
                $financials = $this->buildFinancialStats(Payment::query());
                $recent_users = User::with('company')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_companies = Company::orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
                $recent_vacancies = Vacancy::with(['company', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            } else {
                $stats = [
                    'total_users' => User::where('company_id', $tenantId)->count(),
                    'total_companies' => Company::where('id', $tenantId)->count(),
                    'total_vacancies' => Vacancy::where('company_id', $tenantId)->count(),
                    'total_matches' => \App\Models\JobMatch::whereHas('vacancy', function($q) use ($tenantId) {
                        $q->where('company_id', $tenantId);
                    })->count(),
                    'total_interviews' => Interview::where('company_id', $tenantId)->count(),
                    'active_vacancies' => Vacancy::where('company_id', $tenantId)->where('status', 'active')->count(),
                    'pending_matches' => \App\Models\JobMatch::whereHas('vacancy', function($q) use ($tenantId) {
                        $q->where('company_id', $tenantId);
                    })->where('status', 'pending')->count(),
                    'completed_interviews' => Interview::where('company_id', $tenantId)->where('scheduled_at', '<', now())->count(),
                ];

                $financials = $this->buildFinancialStats(Payment::where('company_id', $tenantId));
                $recent_users = User::where('company_id', $tenantId)
                    ->with('company')
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                $recent_companies = Company::where('id', $tenantId)
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                $recent_vacancies = Vacancy::where('company_id', $tenantId)
                    ->with(['company', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();
            }
        }

        $recent_matches = JobMatch::with(['candidate', 'vacancy.company'])
            ->when(isset($tenantId), function ($query) use ($tenantId) {
                $query->whereHas('vacancy', function ($q) use ($tenantId) {
                    $q->where('company_id', $tenantId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $upcoming_interviews = Interview::with(['match.candidate', 'match.vacancy'])
            ->when(isset($tenantId), function ($query) use ($tenantId) {
                $query->where('company_id', $tenantId);
            })
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $recent_payments = $financials['recent_payments'];
        $revenue_trend = $financials['revenue_trend'];

        // Als een tenant geselecteerd is, toon de company profile opzet
        $selectedCompany = null;
        $isCompanyView = false;
        if ($tenantId) {
            $selectedCompany = Company::with([
                'locations', 
                'mainLocation', 
                'vacancies' => function($q) {
                    $q->where('status', 'active');
                },
                'users' => function($q) {
                    $q->limit(8); // Limit for avatar display
                }
            ])->find($tenantId);
            
            // Als company niet gevonden wordt, maar tenantId bestaat, probeer zonder eager loading
            if (!$selectedCompany && $tenantId) {
                $selectedCompany = Company::find($tenantId);
            }
            
            // Alleen company view tonen als company gevonden is
            $isCompanyView = $selectedCompany !== null;
        }

        $googleMapsApiKey = $this->envService->get('GOOGLE_MAPS_API_KEY', '');
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
            'googleMapsType'
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
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.',
                'redirect' => route('admin.dashboard') // Altijd naar dashboard redirecten
            ]);
        }
        
        // Redirect naar dashboard om company-profile te tonen
        return redirect()->route('admin.dashboard')->with('success', $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.');
    }

    /**
     * Bouw uitgebreide statistieken voor Super Admin dashboard.
     */
    protected function buildSuperAdminStats(): array
    {
        // Basis statistieken
        $totalUsers = User::count();
        $totalCompanies = Company::count();
        $totalVacancies = Vacancy::count();
        $totalMatches = JobMatch::count();
        $totalInterviews = Interview::count();
        
        // Gebruikers per bedrijf
        $usersPerCompany = User::select('company_id', DB::raw('count(*) as user_count'))
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get()
            ->map(function($item) {
                return [
                    'company_name' => $item->company->name ?? 'Onbekend',
                    'user_count' => $item->user_count
                ];
            });
        
        // Vacatures per bedrijf
        $vacanciesPerCompany = Vacancy::select('company_id', DB::raw('count(*) as vacancy_count'))
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->with('company:id,name')
            ->get()
            ->map(function($item) {
                return [
                    'company_name' => $item->company->name ?? 'Onbekend',
                    'vacancy_count' => $item->vacancy_count
                ];
            });
        
        // Kandidaten (gebruikers zonder company_id of met specifieke rollen)
        $candidates = User::whereDoesntHave('roles', function($q) {
            $q->whereIn('name', ['super-admin', 'company-admin', 'company-staff']);
        })->count();
        
        // Match statussen
        $matchStatuses = JobMatch::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Interviews die tot match hebben geleid (interviews waarvan de match status 'hired' of 'accepted' is)
        $interviewsLeadingToMatch = Interview::whereHas('match', function($q) {
            $q->whereIn('status', ['hired', 'accepted']);
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

    /**
     * Bouw financiële statistieken op voor de dashboard-weergave.
     */
    protected function buildFinancialStats($query): array
    {
        $paidQuery = (clone $query)->where('status', 'paid');
        $pendingQuery = (clone $query)->where('status', 'pending');
        $startDate = now()->startOfMonth()->subMonths(5);

        $revenueTrend = (clone $paidQuery)
            ->where(function ($q) {
                $q->whereNotNull('paid_at')->orWhereNotNull('created_at');
            })
            ->where(function ($q) use ($startDate) {
                $q->whereDate('paid_at', '>=', $startDate)
                  ->orWhere(function ($nested) use ($startDate) {
                      $nested->whereNull('paid_at')->whereDate('created_at', '>=', $startDate);
                  });
            })
            ->get(['amount', 'paid_at', 'created_at'])
            ->groupBy(function ($payment) {
                return optional($payment->paid_at ?? $payment->created_at)->format('Y-m');
            })
            ->sortKeys()
            ->map(function ($items, $month) {
                return [
                    'month' => $month,
                    'label' => \Carbon\Carbon::createFromFormat('Y-m', $month)->translatedFormat('M'),
                    'total' => $items->sum('amount'),
                ];
            })
            ->values();

        return [
            'total_revenue' => (clone $paidQuery)->sum('amount'),
            'pending_revenue' => (clone $pendingQuery)->sum('amount'),
            'paid_payments' => (clone $paidQuery)->count(),
            'pending_payments' => (clone $pendingQuery)->count(),
            'average_ticket' => round((clone $paidQuery)->avg('amount'), 2),
            'revenue_trend' => $revenueTrend,
            'recent_payments' => (clone $query)
                ->with('company')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}

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
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use TenantFilter;
    
    public function index()
    {
        $tenantId = null;
        // Voor Super Admin, toon alle data als geen tenant geselecteerd
        if (auth()->user()->hasRole('super-admin') && !session('selected_tenant')) {
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
            // Tenant-specifieke data
            $tenantId = $this->getTenantId();
            
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

        $recent_matches = JobMatch::with(['user', 'vacancy.company'])
            ->when(isset($tenantId), function ($query) use ($tenantId) {
                $query->whereHas('vacancy', function ($q) use ($tenantId) {
                    $q->where('company_id', $tenantId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $upcoming_interviews = Interview::with(['match.user', 'match.vacancy'])
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
            $isCompanyView = true;
        }

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
            'tenantId'
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
                'message' => $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.'
            ]);
        }
        
        // Redirect terug naar de vorige pagina of dashboard
        return redirect()->back()->with('success', $tenantId ? 'Tenant succesvol geselecteerd.' : 'Alle tenants geselecteerd.');
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Models\User;
use App\Models\Company;
use App\Models\Vacancy;
use App\Models\JobMatch;
use App\Models\Interview;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use TenantFilter;
    
    public function index()
    {
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

        return view('admin.dashboard', compact('stats', 'recent_users', 'recent_companies', 'recent_vacancies'));
    }

    public function switchTenant(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        
        if ($tenantId) {
            session(['selected_tenant' => $tenantId]);
        } else {
            session()->forget('selected_tenant');
        }
        
        return response()->json(['success' => true]);
    }
}

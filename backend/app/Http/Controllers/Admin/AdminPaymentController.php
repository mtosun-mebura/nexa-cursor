<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Models\JobMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function index()
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot betalingen.');
        }

        $stats = [
            'total_payments' => Payment::count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'paid_payments' => Payment::where('status', 'paid')->count(),
            'total_revenue' => Payment::where('status', 'paid')->sum('amount'),
            'pending_revenue' => Payment::where('status', 'pending')->sum('amount'),
        ];
        
        $paymentStats = [
            'pending' => $stats['pending_payments'],
            'paid' => $stats['paid_payments'],
            'total' => $stats['total_payments'],
        ];

        return view('admin.payments.index', compact('stats', 'paymentStats'));
    }

    public function openstaand(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot betalingen.');
        }

        $query = Payment::with(['company', 'jobMatch', 'invoice'])
            ->where('status', 'pending');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(25);

        return view('admin.payments.openstaand', compact('payments'));
    }

    public function voldaan(Request $request)
    {
        if (!auth()->user()->hasRole('super-admin')) {
            abort(403, 'Alleen super-admin heeft toegang tot betalingen.');
        }

        $query = Payment::with(['company', 'jobMatch', 'invoice'])
            ->where('status', 'paid');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(25);

        // Revenue chart data (last 12 months)
        $revenueData = Payment::where('status', 'paid')
            ->select(
                DB::raw("TO_CHAR(paid_at, 'YYYY-MM') as month"),
                DB::raw('SUM(amount) as total')
            )
            ->where('paid_at', '>=', now()->subMonths(12))
            ->groupBy(DB::raw("TO_CHAR(paid_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get();

        $chartLabels = $revenueData->pluck('month')->toArray();
        $chartData = $revenueData->pluck('total')->toArray();

        return view('admin.payments.voldaan', compact('payments', 'chartLabels', 'chartData'));
    }
}

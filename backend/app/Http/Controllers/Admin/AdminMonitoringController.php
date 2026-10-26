<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\Monitoring\AdminMonitoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMonitoringController extends Controller
{
    public function __construct(private AdminMonitoringService $monitoring) {}

    public function index(Request $request): View
    {
        $this->ensureSuperAdmin();

        $section = (string) $request->query('sectie', 'jobs');
        if (! in_array($section, ['jobs', 'fouten', 'processen', 'inkomsten'], true)) {
            $section = 'jobs';
        }

        $period = $this->monitoring->normalizePeriod((string) $request->query('periode', 'maand'));
        $queue = $this->monitoring->queueOverview();
        $processes = $this->monitoring->processes();

        return view('admin.monitoring.index', [
            'section' => $section,
            'period' => $period,
            'queue' => $queue,
            'errorLogs' => $this->monitoring->recentErrors(),
            'unresolvedErrors' => $this->monitoring->unresolvedErrorCount(),
            'processes' => $processes,
            'income' => $this->monitoring->income($period),
        ]);
    }

    public function status(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json($this->monitoring->liveStatus());
    }

    public function retryFailed(Request $request, string $uuid): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $result = $this->monitoring->retryFailedJob($uuid);

        return $this->redirectTo($request, 'jobs')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function releaseStuck(Request $request, int $id): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $result = $this->monitoring->releaseStuckJob($id);

        return $this->redirectTo($request, 'jobs')->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function restartStuck(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $result = $this->monitoring->restartStuckAndRecentFailures();
        $message = $this->monitoring->restartSummary($result['released'], $result['retried'], $result['skipped']);
        $flash = ($result['released'] + $result['retried']) > 0 ? 'success' : 'warning';

        return $this->redirectTo($request, 'jobs')->with($flash, $message);
    }

    public function resolveError(Request $request, ErrorLog $errorLog): RedirectResponse
    {
        $this->ensureSuperAdmin();
        $this->monitoring->resolveError($errorLog);

        return $this->redirectTo($request, 'fouten')->with('success', 'Fout gemarkeerd als opgelost.');
    }

    private function redirectTo(Request $request, string $section): RedirectResponse
    {
        return redirect()->route('admin.monitoring.index', [
            'sectie' => $section,
            'periode' => $this->monitoring->normalizePeriod((string) $request->input('periode', 'maand')),
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        $user = auth()->user();
        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            abort(403, 'Alleen een super-admin heeft toegang tot monitoring.');
        }
    }
};

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\Monitoring\AdminMonitoringService;
use App\Services\Monitoring\MonitoringBulkResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AdminMonitoringController extends Controller
{
    private const BULK_ERROR_LIMIT = 200;

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
            'aiConfigured' => app(\App\Services\Monitoring\MonitoringAiDiagnoser::class)->configured(),
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

    public function resolveErrors(Request $request, MonitoringBulkResolver $resolver): RedirectResponse
    {
        $this->ensureSuperAdmin();

        $validator = Validator::make($request->all(), [
            'all' => ['nullable', 'boolean'],
            'ids' => ['nullable', 'array', 'max:'.self::BULK_ERROR_LIMIT],
            'ids.*' => ['integer', 'exists:error_logs,id'],
        ], [
            'ids.max' => 'Selecteer maximaal '.self::BULK_ERROR_LIMIT.' fouten tegelijk.',
            'ids.*.exists' => 'Een geselecteerde fout bestaat niet meer.',
        ]);

        $ids = array_values(array_unique(array_map('intval', (array) $request->input('ids', []))));
        $all = $request->boolean('all');
        if ($validator->fails() || (! $all && $ids === [])) {
            $message = $validator->errors()->first() ?: 'Selecteer eerst minstens één fout.';

            return $this->redirectTo($request, 'fouten')->with('error', $message);
        }

        $logs = ErrorLog::query()
            ->where('resolved', false)
            ->when(! $all, fn ($query) => $query->whereIn('id', $ids))
            ->orderBy('id')
            ->limit(self::BULK_ERROR_LIMIT)
            ->get();

        $summary = $resolver->resolve($logs);
        $message = $summary['resolved'].' opgelost, '.$summary['manual'].' vereisen handmatige actie, '.$summary['failed'].' mislukt.';
        if ($summary['remaining'] > 0) {
            $message .= ' Tijdslimiet bereikt: nog '.$summary['remaining'].' niet verwerkt, probeer opnieuw.';
        }
        if (! $summary['ai_configured']) {
            $message .= ' AI niet geconfigureerd: alleen bekende reparaties en vaste diagnoses zijn gebruikt.';
        }

        $flash = $summary['resolved'] > 0 ? 'success' : ($summary['failed'] > 0 ? 'error' : 'warning');

        return $this->redirectTo($request, 'fouten')->with($flash, $message);
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

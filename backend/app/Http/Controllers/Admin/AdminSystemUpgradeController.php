<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpgradeLog;
use App\Services\SystemStackSnapshotService;
use App\Services\SystemUpgradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSystemUpgradeController extends Controller
{
    public function __construct(
        protected SystemStackSnapshotService $snapshots,
        protected SystemUpgradeService $upgrades,
    ) {}

    public function index()
    {
        $this->ensureSuperAdmin();

        $stack = $this->snapshots->labeledStack();
        $releaseVersion = $this->snapshots->currentReleaseVersion();
        $upgradeHistory = SystemUpgradeLog::query()
            ->with('triggeredBy:id,first_name,last_name,email')
            ->orderByDesc('started_at')
            ->limit(50)
            ->get();

        return view('admin.settings.upgrade', [
            'stack' => $stack,
            'releaseVersion' => $releaseVersion,
            'upgradeHistory' => $upgradeHistory,
            'webUpgradeEnabled' => $this->upgrades->webUpgradeEnabled(),
        ]);
    }

    public function run(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        if (! $this->upgrades->webUpgradeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Web-upgrades zijn uitgeschakeld.',
            ], 422);
        }

        $wantsStream = $request->expectsJson()
            && $request->header('X-System-Upgrade-Stream') === '1';

        if ($wantsStream) {
            return $this->streamUpgradeRun();
        }

        $result = $this->upgrades->runUpgrade($request->user());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'],
        ], $result['success'] ? 200 : 500);
    }

    private function streamUpgradeRun(): StreamedResponse
    {
        return response()->stream(function (): void {
            $this->flushStream();

            $emit = function (array $event): void {
                echo json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n";
                $this->flushStream();
            };

            try {
                $result = $this->upgrades->runUpgrade(auth()->user(), $emit);
                $emit([
                    'type' => 'complete',
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'log' => $result['log'],
                ]);
            } catch (\Throwable $e) {
                $emit([
                    'type' => 'complete',
                    'success' => false,
                    'message' => $e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function flushStream(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    protected function ensureSuperAdmin(): void
    {
        if (! auth()->check() || ! auth()->user()->hasRole('super-admin')) {
            abort(403, 'Je hebt geen rechten om deze pagina te bekijken. Alleen super-admins hebben toegang.');
        }
    }
}

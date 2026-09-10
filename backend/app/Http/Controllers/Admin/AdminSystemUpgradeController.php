<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpgradeLog;
use App\Services\SystemLaravelUpgradeService;
use App\Services\SystemPhpDockerUpgradeService;
use App\Services\SystemStackSnapshotService;
use App\Services\SystemUpgradePreviewService;
use App\Services\SystemUpgradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminSystemUpgradeController extends Controller
{
    public function __construct(
        protected SystemStackSnapshotService $snapshots,
        protected SystemUpgradePreviewService $previewService,
        protected SystemUpgradeService $upgrades,
        protected SystemPhpDockerUpgradeService $phpDocker,
        protected SystemLaravelUpgradeService $laravel,
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

    public function preview(): JsonResponse
    {
        $this->ensureSuperAdmin();

        if (! $this->upgrades->webUpgradeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Web-upgrades zijn uitgeschakeld.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->previewService->preview(),
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

        $validated = $request->validate([
            'selections' => ['required', 'array', 'min:1'],
            'selections.*' => ['required', 'string', 'max:191'],
            'confirm_upgrade' => ['sometimes', 'boolean'],
        ]);

        $selections = $validated['selections'];
        $wantsStream = $request->expectsJson()
            && $request->header('X-System-Upgrade-Stream') === '1';

        if ($wantsStream) {
            return $this->streamUpgradeRun($selections);
        }

        $result = $this->upgrades->runUpgrade($request->user(), $selections);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'],
        ], $result['success'] ? 200 : 500);
    }

    public function phpStatus(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'data' => $this->phpDocker->status(),
        ]);
    }

    public function phpRun(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        if (! $this->upgrades->webUpgradeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Web-upgrades zijn uitgeschakeld.',
            ], 422);
        }

        $status = $this->phpDocker->status();
        if (! $status['can_run'] && ! $status['pending_finalize']) {
            return response()->json([
                'success' => false,
                'message' => $status['message'] !== '' ? $status['message'] : 'PHP-upgrade via Docker is niet beschikbaar.',
            ], 422);
        }

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit) use ($request): array {
                return $this->phpDocker->run($request->user(), $emit);
            });
        }

        $result = $this->phpDocker->run($request->user());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    public function phpFinalize(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit): array {
                return $this->phpDocker->finalize($emit);
            });
        }

        $result = $this->phpDocker->finalize();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    public function laravelStatus(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'data' => $this->laravel->status(),
        ]);
    }

    public function laravelRun(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        if (! $this->upgrades->webUpgradeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Web-upgrades zijn uitgeschakeld.',
            ], 422);
        }

        $validated = $request->validate([
            'channel' => ['required', 'in:minor,major'],
        ]);

        $channel = $validated['channel'];

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit) use ($request, $channel): array {
                return $this->laravel->run($request->user(), $channel, $emit);
            });
        }

        $result = $this->laravel->run($request->user(), $channel);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    public function laravelFinalize(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit): array {
                return $this->laravel->finalize($emit);
            });
        }

        $result = $this->laravel->finalize();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    /**
     * @param  callable(callable): array{success: bool, message: string, log?: mixed, reconnect?: bool}  $handler
     */
    private function streamEvents(callable $handler): StreamedResponse
    {
        return response()->stream(function () use ($handler): void {
            ignore_user_abort(true);
            set_time_limit(0);
            $this->flushStream();

            $emit = function (array $event): void {
                echo json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n";
                $this->flushStream();
            };

            try {
                $result = $handler($emit);
                $emit([
                    'type' => 'complete',
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'log' => $result['log'] ?? null,
                    'reconnect' => $result['reconnect'] ?? false,
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

    /**
     * @param  list<string>  $selections
     */
    private function streamUpgradeRun(array $selections): StreamedResponse
    {
        return response()->stream(function () use ($selections): void {
            $this->flushStream();

            $emit = function (array $event): void {
                echo json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n";
                $this->flushStream();
            };

            try {
                $result = $this->upgrades->runUpgrade(auth()->user(), $selections, $emit);
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

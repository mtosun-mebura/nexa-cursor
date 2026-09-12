<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemUpgradeLog;
use App\Services\SystemDockerComposeService;
use App\Services\SystemLaravelUpgradeService;
use App\Services\SystemPhpDockerUpgradeService;
use App\Services\SystemPostgresDockerUpgradeService;
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
        protected SystemDockerComposeService $docker,
        protected SystemPostgresDockerUpgradeService $postgres,
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

    public function dockerStatus(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'data' => $this->docker->status(),
        ]);
    }

    public function dockerRun(Request $request): JsonResponse|StreamedResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'action' => ['required', 'in:restart,rebuild'],
            'services' => ['sometimes', 'array'],
            'services.*' => ['string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/'],
        ]);
        $action = $validated['action'];
        $services = $action === 'restart'
            ? array_values($validated['services'] ?? [])
            : [];
        if (! $this->docker->ready()) {
            return response()->json([
                'success' => false,
                'message' => 'Docker is in deze omgeving niet beschikbaar.',
            ], 422);
        }
        if ($action === 'rebuild' && ! $this->upgrades->webUpgradeEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Web-upgrades zijn uitgeschakeld.',
            ], 422);
        }

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit) use ($request, $action, $services): array {
                return $this->docker->run($request->user(), $action, $emit, $services);
            });
        }

        $result = $this->docker->run($request->user(), $action, null, $services);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'reconnect' => $result['reconnect'] ?? false,
        ], $result['success'] ? 200 : 500);
    }

    public function dockerExec(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'service' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9][A-Za-z0-9_.-]*$/'],
            'command' => ['required', 'string', 'max:4000'],
        ]);

        if (! $this->docker->ready()) {
            return response()->json([
                'success' => false,
                'message' => 'Docker is in deze omgeving niet beschikbaar.',
            ], 422);
        }

        try {
            $argv = $this->docker->parseExecCommand($validated['command']);
            $result = $this->docker->execInService($validated['service'], $validated['command'], $argv, 60);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $output = $result['output'] !== ''
            ? $result['output']
            : '(geen uitvoer, exit '.$result['exit_code'].')';

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Commando uitgevoerd op '.$result['service'].'.'
                : 'Commando eindigde met exit '.$result['exit_code'].'.',
            'data' => [
                'exit_code' => $result['exit_code'],
                'output' => $output,
                'service' => $result['service'],
                'container' => $result['container'],
            ],
        ], $result['success'] ? 200 : 422);
    }

    public function postgresStatus(): JsonResponse
    {
        $this->ensureSuperAdmin();

        return response()->json([
            'success' => true,
            'data' => $this->postgres->status(),
        ]);
    }

    public function postgresRun(Request $request): JsonResponse|StreamedResponse
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
        $status = $this->postgres->status();
        $can = $channel === 'major' ? $status['can_major'] : $status['can_minor'];
        if (! $can) {
            return response()->json([
                'success' => false,
                'message' => $status['message'] !== ''
                    ? $status['message']
                    : 'PostgreSQL-upgrade via Docker is niet beschikbaar.',
            ], 422);
        }

        if ($request->expectsJson() && $request->header('X-System-Upgrade-Stream') === '1') {
            return $this->streamEvents(function (callable $emit) use ($request, $channel): array {
                return $this->postgres->run($request->user(), $channel, $emit);
            });
        }

        $result = $this->postgres->run($request->user(), $channel);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'log' => $result['log'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    public function destroyHistory(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin();

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:50'],
            'ids.*' => ['integer', 'distinct', 'min:1'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $deleted = SystemUpgradeLog::query()
            ->whereIn('id', $ids)
            ->where('status', '!=', SystemUpgradeLog::STATUS_RUNNING)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Geen regels verwijderd. Lopende upgrades blijven staan.',
                'deleted' => 0,
            ], 422);
        }

        $message = $deleted === 1
            ? '1 regel verwijderd.'
            : $deleted.' regels verwijderd.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'deleted' => $deleted,
        ]);
    }

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

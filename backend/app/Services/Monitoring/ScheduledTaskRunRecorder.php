<?php

namespace App\Services\Monitoring;

use Illuminate\Console\Events\ScheduledBackgroundTaskFinished;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event as EventFacade;
use Throwable;

class ScheduledTaskRunRecorder
{
    private const TTL_DAYS = 30;

    public function listen(): void
    {
        EventFacade::listen(ScheduledTaskStarting::class, function (ScheduledTaskStarting $event): void {
            $this->markStarted((string) $event->task->description);
        });

        EventFacade::listen(ScheduledTaskFinished::class, function (ScheduledTaskFinished $event): void {
            if ($event->task->skippedBecauseOverlapping) {
                $this->markSkipped((string) $event->task->description);

                return;
            }

            if ($event->task->runInBackground) {
                return;
            }

            $this->markFinished((string) $event->task->description, $this->succeeded($event->task));
        });

        EventFacade::listen(ScheduledTaskFailed::class, function (ScheduledTaskFailed $event): void {
            $this->markFinished((string) $event->task->description, false);
        });

        EventFacade::listen(ScheduledBackgroundTaskFinished::class, function (ScheduledBackgroundTaskFinished $event): void {
            $this->markFinished((string) $event->task->description, $this->succeeded($event->task));
        });
    }

    public function markStarted(string $key): void
    {
        $key = trim($key);
        if ($key === '') {
            return;
        }

        $previous = $this->find($key) ?? [];
        $this->put($key, [
            'running' => true,
            'started_at' => now()->toIso8601String(),
            'finished_at' => $previous['finished_at'] ?? null,
            'result' => $previous['result'] ?? null,
            'last_success_at' => $previous['last_success_at'] ?? null,
        ]);
    }

    public function markFinished(string $key, bool $success): void
    {
        $key = trim($key);
        if ($key === '') {
            return;
        }

        $previous = $this->find($key) ?? [];
        $finishedAt = now()->toIso8601String();
        $this->put($key, [
            'running' => false,
            'started_at' => $previous['started_at'] ?? $finishedAt,
            'finished_at' => $finishedAt,
            'result' => $success ? 'gelukt' : 'mislukt',
            'last_success_at' => $success ? $finishedAt : ($previous['last_success_at'] ?? null),
        ]);
    }

    public function markSkipped(string $key): void
    {
        $key = trim($key);
        $run = $key === '' ? null : $this->find($key);
        if ($run === null) {
            return;
        }

        $run['running'] = false;
        $this->put($key, $run);
    }

    /**
     * @return array{running: bool, started_at: ?string, finished_at: ?string, result: ?string, last_success_at: ?string}|null
     */
    public function find(string $key): ?array
    {
        $key = trim($key);
        if ($key === '') {
            return null;
        }

        $run = Cache::get($this->cacheKey($key));
        if (! is_array($run)) {
            return null;
        }

        return [
            'running' => (bool) ($run['running'] ?? false),
            'started_at' => isset($run['started_at']) ? (string) $run['started_at'] : null,
            'finished_at' => isset($run['finished_at']) ? (string) $run['finished_at'] : null,
            'result' => isset($run['result']) ? (string) $run['result'] : null,
            'last_success_at' => isset($run['last_success_at']) ? (string) $run['last_success_at'] : null,
        ];
    }

    /**
     * A start without a matching finish (killed process) must not stay "bezig" forever.
     *
     * @param  array{running?: bool, started_at?: ?string}|null  $run
     */
    public function isActive(?array $run): bool
    {
        if (! is_array($run) || empty($run['running']) || empty($run['started_at'])) {
            return false;
        }

        try {
            $started = Carbon::parse((string) $run['started_at']);
        } catch (Throwable) {
            return false;
        }

        return $started->greaterThan(now()->subHours(3));
    }

    private function succeeded(Event $task): bool
    {
        return (int) ($task->exitCode ?? 0) === 0;
    }

    /**
     * @param  array{running: bool, started_at: ?string, finished_at: ?string, result: ?string, last_success_at: ?string}  $run
     */
    private function put(string $key, array $run): void
    {
        Cache::put($this->cacheKey($key), $run, now()->addDays(self::TTL_DAYS));
    }

    private function cacheKey(string $key): string
    {
        return 'monitoring.schedule-run.'.sha1($key);
    }
}

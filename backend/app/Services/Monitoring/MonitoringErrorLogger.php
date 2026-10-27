<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class MonitoringErrorLogger
{
    private bool $recording = false;

    public function fromThrowable(Throwable $e): void
    {
        if ($this->shouldIgnore($e)) {
            return;
        }

        $this->record($e->getMessage() !== '' ? $e->getMessage() : $e::class, 'exception', [
            'exception' => $e::class,
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'frames' => $this->appFrames($e),
        ]);
    }

    /**
     * De eerste drie stackframes uit de eigen code (zonder vendor), voor een compacte AI-diagnose.
     *
     * @return list<string>
     */
    private function appFrames(Throwable $e): array
    {
        $base = base_path().DIRECTORY_SEPARATOR;
        $frames = [];
        foreach ($e->getTrace() as $frame) {
            $file = (string) ($frame['file'] ?? '');
            if ($file === '' || str_contains($file, DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR) || ! str_starts_with($file, $base)) {
                continue;
            }
            $frames[] = substr($file, strlen($base)).':'.($frame['line'] ?? 0);
            if (count($frames) === 3) {
                break;
            }
        }

        return $frames;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(string $message, string $source, array $context = []): void
    {
        if ($this->recording) {
            return;
        }

        $this->recording = true;

        try {
            if (! $this->tableExists()) {
                return;
            }

            $message = trim($message);
            if ($message === '') {
                $message = 'Onbekende fout';
            }
            $message = mb_substr($message, 0, 2000);
            $source = mb_substr(trim($source) !== '' ? trim($source) : 'app', 0, 80);

            $duplicate = ErrorLog::query()
                ->where('source', $source)
                ->where('message', $message)
                ->where('resolved', false)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->exists();

            if ($duplicate) {
                return;
            }

            ErrorLog::query()->create([
                'message' => $message,
                'context' => $context === [] ? null : $context,
                'source' => $source,
                'resolved' => false,
                'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Logging mag de applicatie nooit laten omvallen.
        } finally {
            $this->recording = false;
        }
    }

    public function unresolvedCount(): int
    {
        try {
            if (! $this->tableExists()) {
                return 0;
            }

            return (int) ErrorLog::query()->where('resolved', false)->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function shouldIgnore(Throwable $e): bool
    {
        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof TokenMismatchException
            || $e instanceof ModelNotFoundException
            || $e instanceof RecordsNotFoundException) {
            return true;
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return true;
        }

        return false;
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        try {
            $exists = Schema::hasTable('error_logs');
        } catch (Throwable) {
            $exists = false;
        }

        return $exists;
    }
};

<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use App\Models\GeneralSetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Services\DatabaseBackupSettingsService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminMonitoringService
{
    private const TIMEZONE = 'Europe/Amsterdam';

    private const FINANCIAL_JOB_PATTERN = '/(Billing|Dunning|Invoice|Payment|Capture|Charge|Mollie|Refund|Settlement|Payout|CancelUnaccepted)/i';

    /** @var array<string, string> */
    private const SCHEDULE_LABELS = [
        'nexa-reset-demo' => 'Demo-accounts resetten',
        'taxi-cancel-unaccepted-rides' => 'Niet-geaccepteerde ritten annuleren',
        'taxi-generate-contract-occurrences' => 'Contractritten genereren',
        'taxi-generate-contract-invoices' => 'Contractfacturen genereren',
        'platform-billing-monthly' => 'Platformfacturatie',
        'platform-saas-trial-notice' => 'Proefperiode-herinnering',
        'platform-billing-dunning' => 'Platform-incasso',
        'nexa-suite-marketplace-billing' => 'Marketplace-facturatie',
        'nexa-suite-marketplace-dunning' => 'Marketplace-incasso',
        'taxi-release-settlement-holds' => 'Settlement-reserveringen vrijgeven',
        'taxi-process-platform-settlements' => 'Platform-settlements verwerken',
        'database-backup-scheduled' => 'Databaseback-up',
        'taxi-partner-finder-knv-weekly' => 'KNV-leden synchroniseren',
    ];

    public function __construct(
        private MonitoringErrorLogger $errors,
        private ScheduledTaskRunRecorder $runs,
    ) {}

    public function normalizePeriod(string $period): string
    {
        return in_array($period, ['dag', 'maand', 'jaar'], true) ? $period : 'maand';
    }

    /**
     * @return array{
     *     connection: string,
     *     retry_after: int,
     *     pending: int,
     *     running: int,
     *     stuck: int,
     *     failed: int,
     *     delayed: int,
     *     worker_stalled: bool,
     *     summary: string,
     *     running_banner: ?string,
     *     reserved: list<array{id: int, name: string, reserved_at: string}>,
     *     rows: list<array<string, mixed>>
     * }
     */
    public function queueOverview(): array
    {
        $retryAfter = $this->retryAfterSeconds();
        $cutoff = now()->timestamp - $retryAfter;
        $now = now()->timestamp;
        $pending = 0;
        $running = 0;
        $stuck = 0;
        $delayed = 0;
        $rows = [];

        if ($this->jobsTableExists()) {
            foreach ($this->jobsTable()->orderByDesc('id')->limit(40)->get() as $job) {
                $reservedAt = $job->reserved_at !== null ? (int) $job->reserved_at : null;
                $availableAt = (int) $job->available_at;
                $financial = $this->isFinancialPayload((string) $job->payload);

                if ($reservedAt === null && $availableAt > $now) {
                    $delayed++;
                    $kind = 'delayed';
                    $status = 'Uitgesteld';
                    $since = $availableAt;
                } elseif ($reservedAt === null) {
                    $pending++;
                    $kind = 'pending';
                    $status = 'In wachtrij';
                    $since = $availableAt;
                } elseif ($reservedAt < $cutoff) {
                    $stuck++;
                    $kind = 'stuck';
                    $status = 'Vastgelopen';
                    $since = $reservedAt;
                } else {
                    $running++;
                    $kind = 'running';
                    $status = 'Bezig';
                    $since = $reservedAt;
                }

                $rows[] = [
                    'kind' => $kind,
                    'label' => $this->jobLabel((string) $job->payload),
                    'queue' => (string) $job->queue,
                    'status' => $status,
                    'since' => $this->formatTimestamp($since),
                    'sort' => $since,
                    'attempts' => (int) $job->attempts,
                    'financial' => $financial,
                    'retry_uuid' => null,
                    'release_id' => $kind === 'stuck' ? (int) $job->id : null,
                ];
            }

            $pending = (int) $this->jobsTable()->whereNull('reserved_at')->where('available_at', '<=', $now)->count();
            $delayed = (int) $this->jobsTable()->whereNull('reserved_at')->where('available_at', '>', $now)->count();
            $running = (int) $this->jobsTable()->whereNotNull('reserved_at')->where('reserved_at', '>=', $cutoff)->count();
            $stuck = (int) $this->jobsTable()->whereNotNull('reserved_at')->where('reserved_at', '<', $cutoff)->count();
        }

        $failed = 0;
        if ($this->failedTableExists()) {
            $failed = (int) $this->failedTable()->count();
            foreach ($this->failedTable()->orderByDesc('id')->limit(40)->get() as $job) {
                $failedAt = $job->failed_at ? Carbon::parse($job->failed_at)->timestamp : $now;
                $rows[] = [
                    'kind' => 'failed',
                    'label' => $this->jobLabel((string) $job->payload),
                    'queue' => (string) $job->queue,
                    'status' => 'Mislukt',
                    'since' => $this->formatTimestamp($failedAt),
                    'sort' => $failedAt,
                    'attempts' => null,
                    'financial' => $this->isFinancialPayload((string) $job->payload),
                    'retry_uuid' => (string) $job->uuid,
                    'release_id' => null,
                ];
            }
        }

        usort($rows, fn (array $a, array $b) => $b['sort'] <=> $a['sort']);
        $rows = array_slice($rows, 0, 50);

        $oldestPending = null;
        if ($this->jobsTableExists()) {
            $oldestPending = $this->jobsTable()->whereNull('reserved_at')->where('available_at', '<=', $now)->min('available_at');
        }
        $workerStalled = $pending > 0
            && $running === 0
            && $oldestPending !== null
            && (int) $oldestPending <= $cutoff;

        if ($stuck > 0) {
            $summary = 'Er staan jobs vast: de reservering duurt langer dan '.$retryAfter.' seconden.';
        } elseif ($workerStalled) {
            $summary = 'Er staan jobs klaar, maar er wordt niets opgepakt. De worker lijkt stil te staan.';
        } elseif ($pending + $running > 0) {
            $summary = 'De wachtrij loopt.';
        } elseif ($failed > 0) {
            $summary = 'Geen openstaande jobs. Er zijn wel mislukte jobs.';
        } else {
            $summary = 'Geen openstaande of mislukte jobs.';
        }

        $reserved = $running > 0 ? $this->reservedRunningJobs() : [];
        $runningLabels = array_map(fn (array $job): string => $job['name'], $reserved);

        return [
            'connection' => (string) config('queue.default'),
            'retry_after' => $retryAfter,
            'pending' => $pending,
            'running' => $running,
            'stuck' => $stuck,
            'failed' => $failed,
            'delayed' => $delayed,
            'worker_stalled' => $workerStalled,
            'summary' => $summary,
            'running_banner' => $this->runningJobsBanner($running, $runningLabels),
            'reserved' => $reserved,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{
     *     tasks: list<array<string, mixed>>,
     *     jobs: array{running_count: int, banner: ?string, reserved: list<array{id: int, name: string, reserved_at: string}>}
     * }
     */
    public function liveStatus(): array
    {
        $queue = $this->queueOverview();

        return [
            'tasks' => array_map(fn (array $task): array => [
                'key' => $task['key'],
                'name' => $task['name'],
                'running' => (bool) $task['running'],
                'running_text' => $task['running_text'],
                'finished_text' => $task['finished_text'],
                'status' => $task['status'],
                'badge' => $task['badge'],
                'last_success' => $task['last_success'],
                'stalled' => (bool) $task['stalled'],
                'started_at' => array_key_exists('started_at', $task) ? $task['started_at'] : null,
                'finished_at' => array_key_exists('finished_at', $task) ? $task['finished_at'] : null,
                'run_status' => $task['run_status'],
            ], $this->scheduledTasks()),
            'jobs' => [
                'running_count' => (int) $queue['running'],
                'banner' => $queue['running_banner'],
                'reserved' => $queue['reserved'],
            ],
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function retryFailedJob(string $uuid): array
    {
        if (! $this->failedTableExists()) {
            return ['ok' => false, 'message' => 'De tabel met mislukte jobs ontbreekt.'];
        }

        $job = $this->failedTable()->where('uuid', $uuid)->first();
        if ($job === null) {
            return ['ok' => false, 'message' => 'Mislukte job niet gevonden.'];
        }

        if ($this->isFinancialPayload((string) $job->payload)) {
            return ['ok' => false, 'message' => 'Deze job raakt een betaling, incasso, factuur of terugbetaling en is niet herstart.'];
        }

        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Herstarten is mislukt: '.$e->getMessage()];
        }

        return ['ok' => true, 'message' => 'Job staat opnieuw in de wachtrij.'];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function releaseStuckJob(int $id): array
    {
        if (! $this->jobsTableExists()) {
            return ['ok' => false, 'message' => 'De jobtabel ontbreekt.'];
        }

        $job = $this->jobsTable()->where('id', $id)->first();
        if ($job === null || $job->reserved_at === null) {
            return ['ok' => false, 'message' => 'Deze job is niet vastgelopen.'];
        }

        $cutoff = now()->timestamp - $this->retryAfterSeconds();
        if ((int) $job->reserved_at >= $cutoff) {
            return ['ok' => false, 'message' => 'Deze job is nog bezig en is niet vrijgegeven.'];
        }

        if ($this->isFinancialPayload((string) $job->payload)) {
            return ['ok' => false, 'message' => 'Deze job raakt een betaling, incasso, factuur of terugbetaling en is niet herstart.'];
        }

        $this->jobsTable()->where('id', $id)->update([
            'reserved_at' => null,
            'available_at' => now()->timestamp,
        ]);

        return ['ok' => true, 'message' => 'Vastgelopen job is vrijgegeven voor de worker.'];
    }

    /**
     * @return array{released: int, retried: int, skipped: int}
     */
    public function restartStuckAndRecentFailures(): array
    {
        $released = 0;
        $skipped = 0;
        $retryIds = [];

        if ($this->jobsTableExists()) {
            $cutoff = now()->timestamp - $this->retryAfterSeconds();
            $stuckJobs = $this->jobsTable()->whereNotNull('reserved_at')->where('reserved_at', '<', $cutoff)->get();
            foreach ($stuckJobs as $job) {
                if ($this->isFinancialPayload((string) $job->payload)) {
                    $skipped++;

                    continue;
                }
                $this->jobsTable()->where('id', $job->id)->update([
                    'reserved_at' => null,
                    'available_at' => now()->timestamp,
                ]);
                $released++;
            }
        }

        if ($this->failedTableExists()) {
            $failedJobs = $this->failedTable()->where('failed_at', '>=', now()->subDays(7))->get();
            foreach ($failedJobs as $job) {
                if ($this->isFinancialPayload((string) $job->payload)) {
                    $skipped++;

                    continue;
                }
                $retryIds[] = (string) $job->uuid;
            }
        }

        if ($retryIds !== []) {
            Artisan::call('queue:retry', ['id' => $retryIds]);
        }

        return [
            'released' => $released,
            'retried' => count($retryIds),
            'skipped' => $skipped,
        ];
    }

    public function restartSummary(int $released, int $retried, int $skipped): string
    {
        if ($released === 0 && $retried === 0 && $skipped === 0) {
            return 'Geen vastgelopen of recente mislukte jobs om te herstarten.';
        }

        $parts = [];
        if ($released > 0) {
            $parts[] = $released === 1 ? '1 vastgelopen job vrijgegeven' : $released.' vastgelopen jobs vrijgegeven';
        }
        if ($retried > 0) {
            $parts[] = $retried === 1 ? '1 mislukte job opnieuw in de wachtrij' : $retried.' mislukte jobs opnieuw in de wachtrij';
        }
        if ($skipped > 0) {
            $parts[] = $skipped === 1
                ? '1 job overgeslagen (betaling, incasso, factuur of terugbetaling)'
                : $skipped.' jobs overgeslagen (betaling, incasso, factuur of terugbetaling)';
        }

        return ucfirst(implode(', ', $parts)).'.';
    }

    /**
     * @return \Illuminate\Support\Collection<int, ErrorLog>
     */
    public function recentErrors()
    {
        if (! Schema::hasTable('error_logs')) {
            return collect();
        }

        $this->runKnownErrorHealing();

        return ErrorLog::query()
            ->orderBy('resolved')
            ->orderByDesc('id')
            ->limit(40)
            ->get();
    }

    public function unresolvedErrorCount(): int
    {
        $this->runKnownErrorHealing();

        return $this->errors->unresolvedCount();
    }

    private function runKnownErrorHealing(): void
    {
        try {
            app(MonitoringKnownErrorHealer::class)->heal();
        } catch (Throwable) {
            // Herstel mag de monitoringpagina niet laten omvallen.
        }
    }

    public function resolveError(ErrorLog $errorLog): void
    {
        if ($errorLog->resolved) {
            return;
        }

        $errorLog->forceFill([
            'resolved' => true,
            'resolved_at' => now(),
        ])->save();
    }

    /**
     * @return array{
     *     worker_stalled: bool,
     *     stalled_count: int,
     *     tasks: list<array<string, mixed>>,
     *     batches: list<array<string, mixed>>
     * }
     */
    public function processes(): array
    {
        $queue = $this->queueOverview();
        $tasks = $this->scheduledTasks();
        $batches = $this->jobBatches();
        $stalledTasks = count(array_filter($tasks, fn (array $task) => $task['stalled']));
        $stalledBatches = count(array_filter($batches, fn (array $batch) => $batch['stalled']));
        $stalled = $stalledTasks + $stalledBatches + ($queue['worker_stalled'] ? 1 : 0) + ($queue['stuck'] > 0 ? 1 : 0);

        return [
            'worker_stalled' => $queue['worker_stalled'],
            'stalled_count' => $stalled,
            'scheduled_count' => count($tasks),
            'tasks' => $tasks,
            'batches' => $batches,
        ];
    }

    /**
     * @return array{period: string, label: string, total: float, labels: list<string>, data: list<float>, source: string}
     */
    public function income(string $period): array
    {
        $period = $this->normalizePeriod($period);
        $now = now(self::TIMEZONE);
        if ($period === 'dag') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
            $step = 'hour';
            $label = 'Vandaag';
        } elseif ($period === 'jaar') {
            $start = $now->copy()->startOfYear();
            $end = $now->copy()->endOfYear();
            $step = 'month';
            $label = 'Dit jaar';
        } else {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $step = 'day';
            $label = 'Deze maand';
        }

        $buckets = $this->emptyBuckets($start, $end, $step);
        $this->addPaidPayments($buckets, $start, $end, $step);
        $this->addPaidRidePayments($buckets, $start, $end, $step);
        $this->addStandalonePaidInvoices($buckets, $start, $end, $step);

        return [
            'period' => $period,
            'label' => $label,
            'total' => round(array_sum(array_column($buckets, 'total')), 2),
            'labels' => array_values(array_map(fn (array $bucket) => $bucket['label'], $buckets)),
            'data' => array_values(array_map(fn (array $bucket) => round($bucket['total'], 2), $buckets)),
            'source' => 'Betaalde modulebetalingen, betaalde ritbetalingen en betaalde facturen die daar nog niet in zitten (zelfde bronnen als de dashboard-inkomsten, platformbreed).',
        ];
    }

    public function isFinancialPayload(string $payload): bool
    {
        $decoded = json_decode($payload, true);
        $names = [];
        if (is_array($decoded)) {
            $names[] = (string) ($decoded['displayName'] ?? '');
            $names[] = (string) data_get($decoded, 'data.commandName', '');
        }

        $haystack = trim(implode(' ', $names));
        if ($haystack === '') {
            return false;
        }

        return preg_match(self::FINANCIAL_JOB_PATTERN, $haystack) === 1;
    }

    public function isFinancialText(string $text): bool
    {
        return preg_match(self::FINANCIAL_JOB_PATTERN, $text) === 1;
    }

    /**
     * Ids van vastgelopen, niet-financiële jobs.
     *
     * @return list<int>
     */
    public function stuckJobIds(): array
    {
        if (! $this->jobsTableExists()) {
            return [];
        }

        $cutoff = now()->timestamp - $this->retryAfterSeconds();
        $ids = [];
        foreach ($this->jobsTable()->whereNotNull('reserved_at')->where('reserved_at', '<', $cutoff)->get(['id', 'payload']) as $job) {
            if (! $this->isFinancialPayload((string) $job->payload)) {
                $ids[] = (int) $job->id;
            }
        }

        return $ids;
    }

    /**
     * Uuids van mislukte, niet-financiële jobs (laatste 7 dagen) waarvan de klassenaam in de tekst voorkomt.
     *
     * @return list<string>
     */
    public function failedJobUuidsMatching(string $text): array
    {
        if (! $this->failedTableExists() || trim($text) === '') {
            return [];
        }

        $uuids = [];
        $query = $this->failedTable()->where('failed_at', '>=', now()->subDays(7))->get(['uuid', 'payload']);
        foreach ($query as $job) {
            $label = $this->jobLabel((string) $job->payload);
            if ($label === 'Onbekende job' || $this->isFinancialPayload((string) $job->payload)) {
                continue;
            }
            if (stripos($text, $label) !== false) {
                $uuids[] = (string) $job->uuid;
            }
        }

        return $uuids;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduledTasks(): array
    {
        $tasks = [];

        try {
            $this->ensureScheduleLoaded();
            $events = app(Schedule::class)->events();
        } catch (Throwable) {
            return [[
                'key' => '',
                'name' => 'Planner',
                'expression' => '—',
                'frequency' => '—',
                'next_run' => '—',
                'last_success' => '—',
                'status' => 'Schema niet leesbaar',
                'badge' => $this->taskBadgeClass('Schema niet leesbaar', true),
                'stalled' => true,
                'running' => false,
                'running_text' => '',
                'finished_text' => null,
                'started_at' => null,
                'finished_at' => null,
                'run_status' => null,
            ]];
        }

        foreach ($events as $event) {
            if (! $event instanceof Event) {
                continue;
            }

            $key = (string) $event->description;
            $name = self::SCHEDULE_LABELS[$key] ?? ($event->getSummaryForDisplay() ?: 'Geplande taak');
            $expression = $event->getExpression();
            $next = '—';
            try {
                $next = $event->nextRunDate()->timezone(self::TIMEZONE)->format('d-m-Y H:i');
            } catch (Throwable) {
                $next = '—';
            }

            $lastSuccess = 'Niet vastgelegd';
            $status = 'Niet bezig';
            $stalled = false;
            $finishedText = null;
            $startedAt = null;
            $finishedAt = null;
            $runStatus = null;

            if ($key === 'database-backup-scheduled') {
                $backup = $this->backupStatus();
                $lastSuccess = $backup['last_success'];
                $status = $backup['status'];
                $stalled = $backup['stalled'];
            }

            $run = $this->runs->find($key);
            $running = $this->runs->isActive($run);
            $result = is_array($run) ? ($run['result'] ?? null) : null;
            if (is_array($run)) {
                $startedAt = $run['started_at'] ?? null;
                $finishedAt = $run['finished_at'] ?? null;
            }

            if ($running) {
                $runStatus = 'running';
                $status = 'Bezig';
                $stalled = false;
                $finishedAt = null;
                $successAt = is_array($run) ? $this->formatIso($run['last_success_at'] ?? null) : null;
                if ($successAt !== null) {
                    $lastSuccess = $successAt;
                }
            } elseif (is_array($run) && in_array($result, ['gelukt', 'mislukt'], true)) {
                $runStatus = $result === 'gelukt' ? 'ok' : 'failed';
                $finishedDisplay = $this->formatIso($finishedAt);
                if ($finishedDisplay !== null) {
                    $finishedText = 'Afgerond om '.$finishedDisplay;
                }
                if ($result === 'gelukt') {
                    $status = 'Gelukt';
                    $stalled = false;
                } else {
                    $status = 'Mislukt';
                    $stalled = true;
                }
                $successAt = $this->formatIso($run['last_success_at'] ?? null);
                if ($successAt !== null) {
                    $lastSuccess = $successAt;
                }
            }

            $tasks[] = [
                'key' => $key,
                'name' => $name,
                'expression' => $expression,
                'frequency' => $this->frequencyLabel($expression),
                'next_run' => $next,
                'last_success' => $lastSuccess,
                'status' => $status,
                'badge' => $this->taskBadgeClass($status, $stalled),
                'stalled' => $stalled,
                'running' => $running,
                'running_text' => $name.' is gestart',
                'finished_text' => $running ? null : $finishedText,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'run_status' => $runStatus,
            ];
        }

        return $tasks;
    }

    /**
     * @return array{last_success: string, status: string, stalled: bool}
     */
    private function backupStatus(): array
    {
        try {
            $backup = app(DatabaseBackupSettingsService::class);
        } catch (Throwable) {
            return ['last_success' => '—', 'status' => 'Onbekend', 'stalled' => false];
        }

        if (! $backup->isEnabled()) {
            return ['last_success' => '—', 'status' => 'Uitgeschakeld', 'stalled' => false];
        }

        $raw = trim((string) GeneralSetting::get('database_backup_last_run_at', ''));
        if ($raw === '') {
            return ['last_success' => 'Nog niet', 'status' => 'Nog niet gelukt', 'stalled' => true];
        }

        try {
            $last = Carbon::parse($raw);
        } catch (Throwable) {
            return ['last_success' => 'Onleesbaar', 'status' => 'Onbekend', 'stalled' => true];
        }

        $limit = $backup->frequency() === DatabaseBackupSettingsService::FREQUENCY_WEEKLY
            ? now()->subDays(8)
            : now()->subHours(26);
        $stalled = $last->lt($limit);

        return [
            'last_success' => $last->timezone(self::TIMEZONE)->format('d-m-Y H:i'),
            'status' => $stalled ? 'Te laat' : 'Gelukt',
            'stalled' => $stalled,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobBatches(): array
    {
        $connection = config('queue.batching.database');
        $table = (string) config('queue.batching.table', 'job_batches');
        try {
            $schema = $connection ? Schema::connection($connection) : Schema::getFacadeRoot();
            if (! $schema->hasTable($table)) {
                return [];
            }
            $query = ($connection ? DB::connection($connection) : DB::connection())->table($table);
        } catch (Throwable) {
            return [];
        }

        $rows = [];
        $cutoff = now()->subHours(2)->timestamp;
        foreach ($query->orderByDesc('created_at')->limit(20)->get() as $batch) {
            $finished = $batch->finished_at !== null;
            $pending = (int) $batch->pending_jobs;
            $failed = (int) $batch->failed_jobs;
            $stalled = ! $finished && $pending > 0 && (int) $batch->created_at < $cutoff;
            if ($finished && $failed === 0) {
                $status = 'Afgerond';
            } elseif ($finished) {
                $status = 'Afgerond met fouten';
            } elseif ($stalled) {
                $status = 'Lijkt vastgelopen';
            } else {
                $status = 'Bezig';
            }

            $rows[] = [
                'name' => (string) $batch->name,
                'total' => (int) $batch->total_jobs,
                'pending' => $pending,
                'failed' => $failed,
                'started' => $this->formatTimestamp((int) $batch->created_at),
                'status' => $status,
                'stalled' => $stalled,
            ];
        }

        return $rows;
    }

    private function ensureScheduleLoaded(): void
    {
        $schedule = app(Schedule::class);
        foreach ($schedule->events() as $event) {
            if ($event instanceof Event && $event->description === 'database-backup-scheduled') {
                return;
            }
        }

        $path = base_path('routes/console.php');
        if (is_file($path)) {
            require $path;
        }
    }

    private function taskBadgeClass(string $status, bool $stalled): string
    {
        if ($status === 'Mislukt' || $stalled) {
            return 'kt-badge kt-badge-danger kt-badge-sm';
        }
        if ($status === 'Gelukt') {
            return 'kt-badge kt-badge-success kt-badge-sm';
        }
        if ($status === 'Bezig') {
            return 'kt-badge kt-badge-primary kt-badge-sm';
        }

        return 'kt-badge kt-badge-secondary kt-badge-sm';
    }

    private function formatIso(?string $iso): ?string
    {
        if ($iso === null || trim($iso) === '') {
            return null;
        }

        try {
            return Carbon::parse($iso)->timezone(self::TIMEZONE)->format('d-m-Y H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Newest reserved jobs that are still within the worker timeout.
     *
     * @return list<array{id: int, name: string, reserved_at: string}>
     */
    private function reservedRunningJobs(): array
    {
        if (! $this->jobsTableExists()) {
            return [];
        }

        $cutoff = now()->timestamp - $this->retryAfterSeconds();
        $jobs = $this->jobsTable()
            ->whereNotNull('reserved_at')
            ->where('reserved_at', '>=', $cutoff)
            ->orderByDesc('reserved_at')
            ->limit(5)
            ->get(['id', 'payload', 'reserved_at']);

        $rows = [];
        foreach ($jobs as $job) {
            $rows[] = [
                'id' => (int) $job->id,
                'name' => $this->jobLabel((string) $job->payload),
                'reserved_at' => Carbon::createFromTimestamp((int) $job->reserved_at)->toIso8601String(),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $labels
     */
    private function runningJobsBanner(int $count, array $labels): ?string
    {
        if ($count < 1) {
            return null;
        }

        if ($count === 1 && isset($labels[0]) && $labels[0] !== '') {
            return 'Er draait een job: '.$labels[0];
        }

        if ($count === 1) {
            return 'Er draait een job';
        }

        return 'Er draaien '.$count.' jobs';
    }

    private function frequencyLabel(string $expression): string
    {
        return match ($expression) {
            '* * * * *' => 'Elke minuut',
            '*/15 * * * *' => 'Elke 15 minuten',
            '0 * * * *' => 'Elk uur',
            default => $this->calendarFrequency($expression) ?? $expression,
        };
    }

    private function calendarFrequency(string $expression): ?string
    {
        if (preg_match('/^(\d+) (\d+) \* \* \*$/', $expression, $daily)) {
            return sprintf('Dagelijks om %02d:%02d', (int) $daily[2], (int) $daily[1]);
        }

        if (preg_match('/^(\d+) (\d+) \* \* (\d+)$/', $expression, $weekly)) {
            $days = [0 => 'zondag', 1 => 'maandag', 2 => 'dinsdag', 3 => 'woensdag', 4 => 'donderdag', 5 => 'vrijdag', 6 => 'zaterdag', 7 => 'zondag'];
            $day = $days[(int) $weekly[3]] ?? 'weekdag '.$weekly[3];

            return sprintf('Wekelijks op %s om %02d:%02d', $day, (int) $weekly[2], (int) $weekly[1]);
        }

        return null;
    }

    /**
     * @param  array<string, array{label: string, total: float}>  $buckets
     */
    private function addPaidPayments(array &$buckets, Carbon $start, Carbon $end, string $step): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $rows = Payment::query()
            ->where('status', 'paid')
            ->where(function ($query) use ($start) {
                $query->where('paid_at', '>=', $start->copy()->utc())
                    ->orWhere(function ($nested) use ($start) {
                        $nested->whereNull('paid_at')->where('created_at', '>=', $start->copy()->utc());
                    });
            })
            ->get(['amount', 'paid_at', 'created_at']);

        foreach ($rows as $row) {
            $at = $row->paid_at ?? $row->created_at;
            $this->addAmount($buckets, $at, (float) $row->amount, $start, $end, $step);
        }
    }

    /**
     * @param  array<string, array{label: string, total: float}>  $buckets
     */
    private function addPaidRidePayments(array &$buckets, Carbon $start, Carbon $end, string $step): void
    {
        if (! Schema::hasTable('ride_payments')) {
            return;
        }

        $rows = DB::table('ride_payments')
            ->where('status', RidePayment::STATUS_PAID)
            ->where('paid_at', '>=', $start->copy()->utc())
            ->where('paid_at', '<=', $end->copy()->utc())
            ->get(['amount', 'paid_at']);

        foreach ($rows as $row) {
            if ($row->paid_at === null) {
                continue;
            }
            $this->addAmount($buckets, Carbon::parse($row->paid_at), (float) $row->amount, $start, $end, $step);
        }
    }

    /**
     * @param  array<string, array{label: string, total: float}>  $buckets
     */
    private function addStandalonePaidInvoices(array &$buckets, Carbon $start, Carbon $end, string $step): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $coveredInvoiceIds = [];
        $coveredRideIds = [];
        if (Schema::hasTable('payments')) {
            $coveredInvoiceIds = Payment::query()
                ->where('status', 'paid')
                ->whereNotNull('invoice_id')
                ->pluck('invoice_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        if (Schema::hasTable('ride_payments')) {
            $coveredRideIds = DB::table('ride_payments')
                ->where('status', RidePayment::STATUS_PAID)
                ->pluck('ride_request_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $query = Invoice::query()->where('status', 'paid');
        if ($coveredInvoiceIds !== []) {
            $query->whereNotIn('id', $coveredInvoiceIds);
        }
        $query->where(function ($q) use ($coveredRideIds) {
            $q->where('module', '!=', Invoice::MODULE_TAXI)
                ->orWhereNull('module')
                ->orWhereNull('module_reference_id');
            if ($coveredRideIds === []) {
                $q->orWhere('module', Invoice::MODULE_TAXI);
            } else {
                $q->orWhere(function ($nested) use ($coveredRideIds) {
                    $nested->where('module', Invoice::MODULE_TAXI)
                        ->whereNotIn('module_reference_id', $coveredRideIds);
                });
            }
        });

        foreach ($query->get(['total_amount', 'paid_date', 'invoice_date', 'updated_at']) as $invoice) {
            $at = $invoice->paid_date ?? $invoice->invoice_date ?? $invoice->updated_at;
            $this->addAmount($buckets, $at, (float) $invoice->total_amount, $start, $end, $step);
        }
    }

    /**
     * @param  array<string, array{label: string, total: float}>  $buckets
     */
    private function addAmount(array &$buckets, mixed $at, float $amount, Carbon $start, Carbon $end, string $step): void
    {
        if ($at === null) {
            return;
        }

        $moment = $at instanceof Carbon ? $at->copy() : Carbon::parse($at);
        $local = $moment->timezone(self::TIMEZONE);
        if ($local->lt($start) || $local->gt($end)) {
            return;
        }

        $key = match ($step) {
            'hour' => $local->format('Y-m-d H'),
            'day' => $local->format('Y-m-d'),
            default => $local->format('Y-m'),
        };

        if (! isset($buckets[$key])) {
            return;
        }

        $buckets[$key]['total'] += $amount;
    }

    /**
     * @return array<string, array{label: string, total: float}>
     */
    private function emptyBuckets(Carbon $start, Carbon $end, string $step): array
    {
        $buckets = [];
        $cursor = $start->copy();
        $months = [1 => 'jan', 2 => 'feb', 3 => 'mrt', 4 => 'apr', 5 => 'mei', 6 => 'jun', 7 => 'jul', 8 => 'aug', 9 => 'sep', 10 => 'okt', 11 => 'nov', 12 => 'dec'];

        while ($cursor->lte($end)) {
            if ($step === 'hour') {
                $key = $cursor->format('Y-m-d H');
                $label = $cursor->format('H:00');
                $cursor->addHour();
            } elseif ($step === 'day') {
                $key = $cursor->format('Y-m-d');
                $label = $cursor->format('d-m');
                $cursor->addDay();
            } else {
                $key = $cursor->format('Y-m');
                $label = $months[(int) $cursor->format('n')].' '.$cursor->format('Y');
                $cursor->addMonth();
            }
            $buckets[$key] = ['label' => $label, 'total' => 0.0];
        }

        return $buckets;
    }

    private function jobLabel(string $payload): string
    {
        $decoded = json_decode($payload, true);
        $name = '';
        if (is_array($decoded)) {
            $name = (string) ($decoded['displayName'] ?? data_get($decoded, 'data.commandName', ''));
        }
        if ($name === '') {
            return 'Onbekende job';
        }

        return class_basename(str_replace('\\\\', '\\', $name));
    }

    private function formatTimestamp(int $timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp)->timezone(self::TIMEZONE)->format('d-m-Y H:i');
    }

    private function retryAfterSeconds(): int
    {
        $seconds = (int) config('queue.connections.database.retry_after', 90);

        return $seconds > 0 ? $seconds : 90;
    }

    private function jobsTableExists(): bool
    {
        try {
            return Schema::connection($this->queueConnectionName())->hasTable($this->jobsTableName());
        } catch (Throwable) {
            return false;
        }
    }

    private function failedTableExists(): bool
    {
        try {
            return Schema::connection($this->failedConnectionName())->hasTable($this->failedTableName());
        } catch (Throwable) {
            return false;
        }
    }

    private function jobsTable()
    {
        return DB::connection($this->queueConnectionName())->table($this->jobsTableName());
    }

    private function failedTable()
    {
        return DB::connection($this->failedConnectionName())->table($this->failedTableName());
    }

    private function queueConnectionName(): ?string
    {
        $name = config('queue.connections.database.connection');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function jobsTableName(): string
    {
        return (string) config('queue.connections.database.table', 'jobs');
    }

    private function failedConnectionName(): ?string
    {
        $name = config('queue.failed.database');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function failedTableName(): string
    {
        return (string) config('queue.failed.table', 'failed_jobs');
    }
};

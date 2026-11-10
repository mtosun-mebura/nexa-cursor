<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lost meerdere fouten in één keer op, zo zuinig mogelijk:
 * 1) bekende reparatie + hercontrole, 2) vaste diagnose, 3) één AI-vraag per unieke vingerafdruk
 * (alleen een whitelist-actie wordt uitgevoerd). Pas na een geslaagde hercontrole wordt een fout opgelost.
 */
class MonitoringBulkResolver
{
    public function __construct(
        private MonitoringKnownErrorHealer $healer,
        private MonitoringErrorFingerprint $fingerprints,
        private MonitoringAiDiagnoser $ai,
        private MonitoringSafeActions $actions,
        private AdminMonitoringService $monitoring,
    ) {}

    /**
     * @param  Collection<int, ErrorLog>  $logs
     * @return array{resolved: int, manual: int, failed: int, remaining: int, ai_configured: bool}
     */
    public function resolve(Collection $logs, int $budgetSeconds = 25): array
    {
        $summary = ['resolved' => 0, 'manual' => 0, 'failed' => 0, 'remaining' => 0, 'ai_configured' => $this->ai->configured()];
        $started = microtime(true);
        $byFingerprint = [];
        $byAction = [];
        $logs = $logs->values();

        foreach ($logs as $index => $log) {
            if (microtime(true) - $started > $budgetSeconds) {
                $summary['remaining'] = $logs->count() - $index;
                break;
            }
            if ($log->resolved) {
                continue;
            }

            $fingerprint = $this->fingerprints->for($log);
            try {
                if (! isset($byFingerprint[$fingerprint])) {
                    $byFingerprint[$fingerprint] = $this->solve($log, $fingerprint, $byAction);
                    $fix = $byFingerprint[$fingerprint];
                } else {
                    $fix = $byFingerprint[$fingerprint];
                    $fix['outcome'] = 'Zelfde oorzaak als een eerder verwerkte fout in deze run. '.$fix['outcome'];
                }
            } catch (Throwable $e) {
                $fix = $this->fix($fingerprint, 'mislukt', null, 'Verwerken mislukt: '.mb_substr($e->getMessage(), 0, 160));
            }

            $this->store($log, $fix);
            $summary[['opgelost' => 'resolved', 'handmatig' => 'manual', 'mislukt' => 'failed'][$fix['status']]]++;
        }

        return $summary;
    }

    /**
     * @param  array<string, array{state: string, outcome: string}>  $byAction
     * @return array<string, mixed>
     */
    private function solve(ErrorLog $log, string $fingerprint, array &$byAction): array
    {
        $healed = $this->healer->healLog($log);
        if ($healed === true) {
            return $this->fix($fingerprint, 'opgelost', 'bekende_reparatie', 'Bekende reparatie uitgevoerd; hercontrole geslaagd.');
        }
        if ($healed === false) {
            return $this->fix($fingerprint, 'mislukt', 'bekende_reparatie', 'Bekende reparatie uitgevoerd, maar de hercontrole is mislukt. De fout blijft open.');
        }

        $known = $this->healer->diagnose((string) $log->message);
        if ($known !== null) {
            return $this->fix($fingerprint, 'handmatig', null, 'Handmatige actie nodig.', $known);
        }

        if (! $this->ai->configured()) {
            return $this->fix($fingerprint, 'handmatig', null, 'AI niet geconfigureerd: geen automatische diagnose. Handmatige actie nodig.');
        }

        $diagnosis = $this->ai->diagnose($log, $fingerprint);
        if ($diagnosis === null) {
            return $this->fix($fingerprint, 'handmatig', null, 'AI-diagnose niet beschikbaar (aanvraag mislukt). Handmatige actie nodig.');
        }

        $proposed = $diagnosis['safe_action'];
        if ($proposed === null) {
            return $this->fix($fingerprint, 'handmatig', null, 'Handmatige actie nodig.', $diagnosis, true);
        }

        $action = MonitoringSafeAction::tryFrom($proposed);
        if ($action === null) {
            return $this->fix($fingerprint, 'handmatig', null, 'Voorgestelde actie "'.mb_substr($proposed, 0, 60).'" staat niet op de whitelist en is niet uitgevoerd: handmatige actie nodig.', $diagnosis, true);
        }

        $text = (string) $log->message.' '.json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE);
        if ($this->monitoring->isFinancialText($text)) {
            return $this->fix($fingerprint, 'handmatig', null, 'Deze fout raakt betalingen, facturen of incasso: niet automatisch uitgevoerd. Handmatige actie nodig.', $diagnosis, true);
        }

        $result = $action === MonitoringSafeAction::RetryFailedJob
            ? $this->actions->run($action, $log)
            : ($byAction[$action->value] ??= $this->actions->run($action, $log));

        $status = match ($result['state']) {
            'verified' => 'opgelost',
            'failed' => 'mislukt',
            default => 'handmatig',
        };

        return $this->fix($fingerprint, $status, $action->value, $result['outcome'], $diagnosis, true);
    }

    /**
     * @param  array<string, mixed>|null  $diagnosis
     * @return array<string, mixed>
     */
    private function fix(string $fingerprint, string $status, ?string $action, string $outcome, ?array $diagnosis = null, bool $ai = false): array
    {
        return [
            'status' => $status,
            'action' => $action,
            'outcome' => $outcome,
            'category' => $diagnosis['category'] ?? null,
            'diagnosis_nl' => $diagnosis['diagnosis_nl'] ?? null,
            'proposed_fix_nl' => $diagnosis['proposed_fix_nl'] ?? null,
            'ai' => $ai,
            'fingerprint' => $fingerprint,
        ];
    }

    /**
     * @param  array<string, mixed>  $fix
     */
    private function store(ErrorLog $log, array $fix): void
    {
        $context = is_array($log->context) ? $log->context : [];
        $context['monitoring_fix'] = $fix + ['at' => now()->toIso8601String()];

        $attributes = ['context' => $context];
        if ($fix['status'] === 'opgelost' && ! $log->resolved) {
            $attributes += ['resolved' => true, 'resolved_at' => now()];
        }
        $log->forceFill($attributes)->save();

        Log::info('monitoring.error_fix', [
            'error_log_id' => $log->id,
            'fingerprint' => $fix['fingerprint'],
            'status' => $fix['status'],
            'action' => $fix['action'],
            'ai' => $fix['ai'],
        ]);
    }
}

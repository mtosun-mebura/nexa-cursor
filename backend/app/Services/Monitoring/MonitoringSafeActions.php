<?php

namespace App\Services\Monitoring;

use App\Models\ErrorLog;
use App\Modules\NexaTaxi\Services\TaxiContractvervoerSchemaService;
use App\Services\ModuleDatabaseService;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Voert uitsluitend acties uit de MonitoringSafeAction-whitelist uit.
 *
 * state: verified (hercontrole geslaagd), executed (uitgevoerd, niet te bevestigen),
 * nothing (niets passends gevonden), failed (fout of hercontrole mislukt).
 */
class MonitoringSafeActions
{
    public function __construct(
        private AdminMonitoringService $monitoring,
        private TaxiContractvervoerSchemaService $schema,
        private ModuleDatabaseService $modules,
    ) {}

    /**
     * @return array{state: string, outcome: string}
     */
    public function run(MonitoringSafeAction $action, ErrorLog $log): array
    {
        try {
            return match ($action) {
                MonitoringSafeAction::OptimizeClear => $this->artisan('optimize:clear', $action),
                MonitoringSafeAction::CacheClear => $this->artisan('cache:clear', $action),
                MonitoringSafeAction::ConfigClear => $this->artisan('config:clear', $action),
                MonitoringSafeAction::ViewClear => $this->artisan('view:clear', $action),
                MonitoringSafeAction::ClearSchedulerLocks => $this->artisan('schedule:clear-cache', $action),
                MonitoringSafeAction::EnsureTaxiSchema => $this->ensureTaxiSchema($action),
                MonitoringSafeAction::RetryFailedJob => $this->retryFailedJobs($action, $log),
                MonitoringSafeAction::ReleaseStuckJobs => $this->releaseStuckJobs($action),
            };
        } catch (Throwable $e) {
            return ['state' => 'failed', 'outcome' => $action->label().' mislukt: '.mb_substr($e->getMessage(), 0, 160)];
        }
    }

    /**
     * @return array{state: string, outcome: string}
     */
    private function artisan(string $command, MonitoringSafeAction $action): array
    {
        $exit = Artisan::call($command);
        if ($exit !== 0) {
            return ['state' => 'failed', 'outcome' => $command.' gaf exitcode '.$exit.'.'];
        }

        return [
            'state' => 'executed',
            'outcome' => $action->label().'. Of de fout hiermee weg is kan niet automatisch worden gecontroleerd: markeer hem zelf als opgelost als hij niet terugkomt.',
        ];
    }

    /**
     * @return array{state: string, outcome: string}
     */
    private function ensureTaxiSchema(MonitoringSafeAction $action): array
    {
        $this->modules->registerConnection('taxi');
        $connection = $this->modules->getModuleConnectionName('taxi');
        $this->schema->ensureTablesExist($connection);

        if (! $this->schema->transportCustomersTableExists($connection)) {
            return ['state' => 'failed', 'outcome' => 'Hercontrole mislukt: tabel transport_customers ontbreekt nog.'];
        }

        return ['state' => 'verified', 'outcome' => $action->label().'; hercontrole geslaagd.'];
    }

    /**
     * @return array{state: string, outcome: string}
     */
    private function retryFailedJobs(MonitoringSafeAction $action, ErrorLog $log): array
    {
        $text = trim((string) $log->message.' '.json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE));
        $uuids = $this->monitoring->failedJobUuidsMatching($text);
        if ($uuids === []) {
            return ['state' => 'nothing', 'outcome' => 'Geen passende mislukte (niet-financiële) job gevonden om te herstarten.'];
        }

        $retried = 0;
        foreach ($uuids as $uuid) {
            if ($this->monitoring->retryFailedJob($uuid)['ok']) {
                $retried++;
            }
        }

        if ($retried === 0 || $this->monitoring->failedJobUuidsMatching($text) !== []) {
            return ['state' => 'failed', 'outcome' => 'Herstarten van de mislukte job is niet gelukt.'];
        }

        return ['state' => 'verified', 'outcome' => $action->label().' ('.$retried.'x); staat niet meer bij mislukte jobs.'];
    }

    /**
     * @return array{state: string, outcome: string}
     */
    private function releaseStuckJobs(MonitoringSafeAction $action): array
    {
        $ids = $this->monitoring->stuckJobIds();
        if ($ids === []) {
            return ['state' => 'nothing', 'outcome' => 'Geen vastgelopen (niet-financiële) jobs gevonden.'];
        }

        foreach ($ids as $id) {
            $this->monitoring->releaseStuckJob($id);
        }

        if ($this->monitoring->stuckJobIds() !== []) {
            return ['state' => 'failed', 'outcome' => 'Niet alle vastgelopen jobs zijn vrijgegeven.'];
        }

        return ['state' => 'verified', 'outcome' => $action->label().' ('.count($ids).'x); hercontrole geslaagd.'];
    }
}

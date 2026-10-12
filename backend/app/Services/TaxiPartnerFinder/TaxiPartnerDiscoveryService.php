<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\TaxiDiscoveryRun;
use App\Models\TaxiProspectSource;
use Throwable;

class TaxiPartnerDiscoveryService
{
    public function __construct(
        protected KnvTaxiSourceAdapter $knv,
        protected KnvCsvImportAdapter $knvCsv,
        protected TaxiProspectUpsertService $upsert,
        protected TaxiProspectNewsletterBridge $newsletterBridge,
    ) {}

    /**
     * @return array{run: TaxiDiscoveryRun, created: int, updated: int, duplicates: int, failed: int}
     */
    public function syncKnv(?int $userId = null, bool $pushToNewsletter = false): array
    {
        $run = TaxiDiscoveryRun::query()->create([
            'source_type' => TaxiProspectSource::TYPE_KNV,
            'status' => TaxiDiscoveryRun::STATUS_RUNNING,
            'started_at' => now(),
            'started_by' => $userId,
        ]);

        try {
            $candidates = $this->knv->fetchCandidates();
            $stats = $this->ingest($candidates, $pushToNewsletter);
            $run->fill([
                'status' => TaxiDiscoveryRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'discovered_count' => $stats['created'],
                'updated_count' => $stats['updated'],
                'duplicate_count' => $stats['duplicates'],
                'failed_count' => $stats['failed'],
                'meta' => ['candidate_count' => count($candidates)],
            ])->save();

            return ['run' => $run->fresh() ?? $run, ...$stats];
        } catch (Throwable $e) {
            $run->fill([
                'status' => TaxiDiscoveryRun::STATUS_FAILED,
                'finished_at' => now(),
                'failed_count' => 1,
                'error_summary' => $e->getMessage(),
            ])->save();
            throw $e;
        }
    }

    /**
     * @return array{run: TaxiDiscoveryRun, created: int, updated: int, duplicates: int, failed: int}
     */
    public function importKnvCsv(string $csvContents, ?int $userId = null, bool $pushToNewsletter = false): array
    {
        $run = TaxiDiscoveryRun::query()->create([
            'source_type' => TaxiProspectSource::TYPE_KNV_CSV,
            'status' => TaxiDiscoveryRun::STATUS_RUNNING,
            'started_at' => now(),
            'started_by' => $userId,
        ]);

        try {
            $candidates = $this->knvCsv->parse($csvContents);
            $stats = $this->ingest($candidates, $pushToNewsletter);
            $run->fill([
                'status' => TaxiDiscoveryRun::STATUS_COMPLETED,
                'finished_at' => now(),
                'discovered_count' => $stats['created'],
                'updated_count' => $stats['updated'],
                'duplicate_count' => $stats['duplicates'],
                'failed_count' => $stats['failed'],
                'meta' => ['candidate_count' => count($candidates)],
            ])->save();

            return ['run' => $run->fresh() ?? $run, ...$stats];
        } catch (Throwable $e) {
            $run->fill([
                'status' => TaxiDiscoveryRun::STATUS_FAILED,
                'finished_at' => now(),
                'failed_count' => 1,
                'error_summary' => $e->getMessage(),
            ])->save();
            throw $e;
        }
    }

    /**
     * @param  list<TaxiProspectCandidate>  $candidates
     * @return array{created: int, updated: int, duplicates: int, failed: int}
     */
    public function ingest(array $candidates, bool $pushToNewsletter = false): array
    {
        $created = 0;
        $updated = 0;
        $duplicates = 0;
        $failed = 0;

        foreach ($candidates as $candidate) {
            try {
                $result = $this->upsert->upsert($candidate);
                match ($result['action']) {
                    'created' => $created++,
                    'updated' => $updated++,
                    'fuzzy', 'duplicate' => $duplicates++,
                    default => $duplicates++,
                };
                if ($pushToNewsletter && $result['action'] !== 'skipped_opt_out') {
                    $this->newsletterBridge->pushIfEligible($result['prospect']);
                }
            } catch (Throwable) {
                $failed++;
            }
        }

        return compact('created', 'updated', 'duplicates', 'failed');
    }
}

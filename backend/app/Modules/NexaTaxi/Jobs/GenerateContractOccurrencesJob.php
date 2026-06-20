<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Services\ContractOccurrenceGeneratorService;
use App\Services\ModuleDatabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateContractOccurrencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $horizonDays = 14,
    ) {}

    public function handle(ModuleDatabaseService $moduleDb, ContractOccurrenceGeneratorService $generator): void
    {
        $moduleDb->ensureModuleStorageReady('taxi');
        $conn = $moduleDb->getModuleConnectionName('taxi');

        try {
            $stats = $generator->generateAll($conn, $this->horizonDays);
            Log::info('GenerateContractOccurrencesJob voltooid.', $stats);
        } catch (\Throwable $e) {
            Log::error('GenerateContractOccurrencesJob mislukt.', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

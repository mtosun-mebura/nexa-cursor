<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Services\TaxiRideCancellationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CancelUnacceptedTaxiRidesJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        ModuleDatabaseService $moduleDb,
        TaxiRideCancellationService $cancellation
    ): void {
        try {
            $moduleDb->ensureModuleStorageReady('taxi');
        } catch (\Throwable $e) {
            Log::warning('CancelUnacceptedTaxiRidesJob: module storage niet klaar', [
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $conn = $moduleDb->getModuleConnectionName('taxi');
        $stats = $cancellation->processDueAutoCancels($conn);

        if ($stats['cancelled'] > 0) {
            Log::info('Niet-geaccepteerde ritten automatisch geannuleerd', $stats);
        }
    }
}

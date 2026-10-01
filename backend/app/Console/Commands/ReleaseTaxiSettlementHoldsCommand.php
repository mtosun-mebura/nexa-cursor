<?php

namespace App\Console\Commands;

use App\Modules\NexaTaxi\Services\RideSettlementEligibilityService;
use App\Services\ModuleDatabaseService;
use Illuminate\Console\Command;

class ReleaseTaxiSettlementHoldsCommand extends Command
{
    protected $signature = 'taxi:release-settlement-holds';

    protected $description = 'Promote completed rides past settlement hold_until to settlement_eligible (Phase 4 gate).';

    public function handle(ModuleDatabaseService $moduleDb, RideSettlementEligibilityService $settlement): int
    {
        $moduleDb->ensureModuleStorageReady('taxi');
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $released = $settlement->releaseDueHolds($conn);

        $this->info("Settlement holds released: {$released}");

        return self::SUCCESS;
    }
}

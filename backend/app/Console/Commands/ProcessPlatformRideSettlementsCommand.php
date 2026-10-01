<?php

namespace App\Console\Commands;

use App\Services\ModuleDatabaseService;
use App\Services\Payout\PlatformRidePayoutService;
use App\Services\Payout\PlatformRideSettlementService;
use Illuminate\Console\Command;

class ProcessPlatformRideSettlementsCommand extends Command
{
    protected $signature = 'taxi:process-platform-settlements {--limit=100 : Max pending payouts}';

    protected $description = 'Sync eligible marketplace/network rides into platform settlement ledgers and process payouts.';

    public function handle(
        ModuleDatabaseService $moduleDb,
        PlatformRideSettlementService $settlements,
        PlatformRidePayoutService $payouts,
    ): int {
        $moduleDb->ensureModuleStorageReady('taxi');
        $conn = $moduleDb->getModuleConnectionName('taxi');

        $sync = $settlements->syncEligibleRides($conn, max(1, (int) $this->option('limit') * 2));
        $this->info("Settlement ledgers created: {$sync['created']} (skipped: {$sync['skipped']})");

        $stats = $payouts->processPending(max(1, (int) $this->option('limit')));
        $this->info("Payouts processed={$stats['processed']} paid={$stats['paid']} failed={$stats['failed']} manual={$stats['manual']}");

        return self::SUCCESS;
    }
}

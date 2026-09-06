<?php

namespace App\Jobs;

use App\Services\NexaSuiteMarketplaceBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessNexaSuiteMarketplaceBillingJob implements ShouldQueue
{
    use Queueable;

    public function handle(NexaSuiteMarketplaceBillingService $billing): void
    {
        $billing->runMonthlyBilling();
    }
}

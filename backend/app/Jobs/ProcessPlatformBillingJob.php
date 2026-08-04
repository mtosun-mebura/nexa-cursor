<?php

namespace App\Jobs;

use App\Services\PlatformBilling\PlatformBillingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPlatformBillingJob implements ShouldQueue
{
    use Queueable;

    public function handle(PlatformBillingService $billing): void
    {
        $billing->runMonthlyBilling();
    }
}

<?php

namespace App\Jobs;

use App\Services\PlatformBilling\SaasTrialNoticeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSaasTrialNoticeJob implements ShouldQueue
{
    use Queueable;

    public function handle(SaasTrialNoticeService $notices): void
    {
        $notices->run();
    }
}

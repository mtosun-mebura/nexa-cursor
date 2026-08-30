<?php

namespace App\Jobs;

use App\Services\PlatformBilling\PlatformDunningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPlatformDunningJob implements ShouldQueue
{
    use Queueable;

    public function handle(PlatformDunningService $dunning): void
    {
        $dunning->run();
    }
}

<?php

namespace App\Jobs;

use App\Services\NexaSuiteMarketplaceDunningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessNexaSuiteMarketplaceDunningJob implements ShouldQueue
{
    use Queueable;

    public function handle(NexaSuiteMarketplaceDunningService $dunning): void
    {
        $dunning->run();
    }
}

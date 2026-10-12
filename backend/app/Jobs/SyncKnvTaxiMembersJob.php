<?php

namespace App\Jobs;

use App\Services\TaxiPartnerFinder\TaxiPartnerDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncKnvTaxiMembersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120, 300];

    public function __construct(
        public ?int $userId = null,
        public bool $pushToNewsletter = false,
    ) {}

    public function handle(TaxiPartnerDiscoveryService $discovery): void
    {
        if (! (bool) config('taxi-partner-finder.sources.knv.enabled', true)) {
            Log::info('SyncKnvTaxiMembersJob skipped: KNV source disabled');

            return;
        }

        $discovery->syncKnv($this->userId, $this->pushToNewsletter);
    }

    public function failed(?Throwable $e): void
    {
        Log::error('SyncKnvTaxiMembersJob failed', [
            'error' => $e?->getMessage(),
            'user_id' => $this->userId,
        ]);
    }
}

<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Services\ModuleDatabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StartRideDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $rideRequestId,
        public int $companyId
    ) {}

    public function handle(ModuleDatabaseService $moduleDb, RideDispatchService $dispatch): void
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $ride = RideRequest::on($conn)->find($this->rideRequestId);
        if (! $ride || $ride->driver_id) {
            return;
        }

        try {
            $dispatch->startDispatch($conn, $ride, $this->companyId);
        } catch (\Throwable $e) {
            Log::warning('StartRideDispatchJob mislukt.', [
                'ride_request_id' => $this->rideRequestId,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

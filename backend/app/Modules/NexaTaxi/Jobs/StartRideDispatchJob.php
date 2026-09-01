<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\RideDispatchService;
use App\Models\Company;
use App\Services\CompanyEntitlementService;
use App\Services\ModuleDatabaseService;
use App\Support\TenantPackageCapability;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Start rit-dispatch na de HTTP-response.
 * Geen ShouldQueue: moet via ->afterResponse() in hetzelfde PHP-proces lopen
 * wanneer er geen queue-worker draait (Coolify/PROD).
 */
class StartRideDispatchJob
{
    use Dispatchable, SerializesModels;

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
        if (! app(CompanyEntitlementService::class)->allows(
            Company::query()->find($this->companyId),
            TenantPackageCapability::DISPATCH
        )) {
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

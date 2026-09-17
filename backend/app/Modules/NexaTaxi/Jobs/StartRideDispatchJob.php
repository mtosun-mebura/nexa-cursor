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

    /**
     * @param  list<int>  $companyIds
     */
    public function __construct(
        public int $rideRequestId,
        public int $companyId,
        public array $companyIds = [],
        public bool $assignCompany = true,
    ) {}

    public function handle(ModuleDatabaseService $moduleDb, RideDispatchService $dispatch): void
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $ride = RideRequest::on($conn)->find($this->rideRequestId);
        if (! $ride || $ride->driver_id) {
            return;
        }

        $companyIds = $this->companyIds !== []
            ? $this->companyIds
            : ($this->companyId > 0 ? [$this->companyId] : []);
        $companyIds = array_values(array_unique(array_filter(
            array_map('intval', $companyIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($companyIds === []) {
            return;
        }

        $entitlements = app(CompanyEntitlementService::class);
        $allowedIds = [];
        foreach ($companyIds as $companyId) {
            if ($entitlements->allows(Company::query()->find($companyId), TenantPackageCapability::DISPATCH)) {
                $allowedIds[] = $companyId;
            }
        }
        if ($allowedIds === []) {
            return;
        }

        try {
            $dispatch->startDispatchForCompanies($conn, $ride, $allowedIds, $this->assignCompany);
        } catch (\Throwable $e) {
            Log::warning('StartRideDispatchJob mislukt.', [
                'ride_request_id' => $this->rideRequestId,
                'company_id' => $this->companyId,
                'company_ids' => $allowedIds,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

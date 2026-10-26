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
 * Start rit-dispatch in hetzelfde PHP-proces.
 * Geen ShouldQueue: op Coolify/TEST draait vaak geen queue-worker.
 * Roep dispatchWhenCommitted() aan. Het aanbod wordt meteen weggeschreven,
 * ook midden in een open transactie. afterCommit() hangt de callback aan de
 * laatst geopende transactie van élke connectie; een latere transactie op de
 * standaardconnectie slikt het aanbod dan in. afterResponse() is alleen de vangnet.
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

    /**
     * Aanbod meteen wegschrijven, ook als $connection nog een transactie open heeft.
     * Faalt de sync-run, dan alsnog afterResponse als vangnet.
     *
     * @param  list<int>  $companyIds
     */
    public static function dispatchWhenCommitted(
        string $connection,
        int $rideId,
        int $companyId,
        array $companyIds = [],
        bool $assignCompany = true,
    ): void {
        try {
            static::dispatchSync($rideId, $companyId, $companyIds, $assignCompany);
        } catch (\Throwable $e) {
            Log::warning('StartRideDispatchJob sync mislukt, opnieuw na de response.', [
                'connection' => $connection,
                'ride_request_id' => $rideId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            try {
                static::dispatch($rideId, $companyId, $companyIds, $assignCompany)->afterResponse();
            } catch (\Throwable) {
                // boeking/betaling mag hier niet op stuklopen
            }
        }
    }

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
            $company = Company::query()->find($companyId);
            // Chauffeur-app is genoeg voor een marktplaats-aanbod. Het admin-recht
            // "dispatch" (centrale toewijzing) mag een online chauffeur niet uitsluiten.
            // Zelfde poort als de boeking: website-boeking, dispatch of chauffeur-app.
            // Een Start-pakket heeft website_booking wel en dispatch/driver_app niet;
            // die chauffeurs kregen daardoor nooit een aanbod.
            if ($company instanceof Company && (
                $entitlements->allows($company, TenantPackageCapability::DISPATCH)
                || $entitlements->allows($company, TenantPackageCapability::DRIVER_APP)
                || $entitlements->allows($company, TenantPackageCapability::WEBSITE_BOOKING)
            )) {
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

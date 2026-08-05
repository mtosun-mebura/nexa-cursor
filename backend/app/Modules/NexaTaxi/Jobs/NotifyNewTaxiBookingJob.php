<?php

namespace App\Modules\NexaTaxi\Jobs;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Services\ModuleDatabaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyNewTaxiBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array{stopovers?: list<string>, return_at?: string|null, section_config?: array<string, mixed>, settings_company_id?: int|null}  $context
     */
    public function __construct(
        public int $rideRequestId,
        public array $context = []
    ) {}

    public function handle(ModuleDatabaseService $moduleDb, TaxiBookingNotificationService $notifications): void
    {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $ride = RideRequest::on($conn)->find($this->rideRequestId);
        if (! $ride) {
            return;
        }

        try {
            $notifications->notifyNewRide($conn, $ride, $this->context);
        } catch (\Throwable $e) {
            Log::warning('NotifyNewTaxiBookingJob mislukt.', [
                'ride_request_id' => $this->rideRequestId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

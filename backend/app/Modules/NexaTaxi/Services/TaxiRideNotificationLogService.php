<?php

namespace App\Modules\NexaTaxi\Services;

use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Support\TaxiNotificationLogSchema;
use Illuminate\Support\Facades\Log;

class TaxiRideNotificationLogService
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function record(
        string $conn,
        int $rideRequestId,
        string $channel,
        string $status,
        ?string $recipientName = null,
        ?string $recipientAddress = null,
        ?int $driverId = null,
        ?string $detail = null,
        ?array $meta = null
    ): void {
        if ($rideRequestId <= 0 || ! TaxiNotificationLogSchema::tableExists($conn)) {
            return;
        }

        try {
            RideRequestNotificationLog::on($conn)->create([
                'ride_request_id' => $rideRequestId,
                'channel' => $channel,
                'status' => $status,
                'recipient_name' => $recipientName !== '' ? $recipientName : null,
                'recipient_address' => $recipientAddress !== '' ? $recipientAddress : null,
                'driver_id' => $driverId,
                'detail' => $detail,
                'meta' => $meta,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Rit-notificatielog kon niet worden opgeslagen.', [
                'ride_request_id' => $rideRequestId,
                'channel' => $channel,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function recordWhatsappSent(
        string $conn,
        int $rideRequestId,
        string $recipientNumber,
        ?array $meta = null
    ): void {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            RideRequestNotificationLog::STATUS_SENT,
            'Dispatch / centrale',
            $recipientNumber,
            null,
            null,
            $meta
        );
    }

    public function recordWhatsappFailed(
        string $conn,
        int $rideRequestId,
        string $recipientNumber,
        string $error
    ): void {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            RideRequestNotificationLog::STATUS_FAILED,
            'Dispatch / centrale',
            $recipientNumber,
            null,
            $error
        );
    }

    public function recordWhatsappSkipped(string $conn, int $rideRequestId, string $reason): void
    {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_WHATSAPP,
            RideRequestNotificationLog::STATUS_SKIPPED,
            null,
            null,
            null,
            $reason
        );
    }

    public function recordDriverEmailSent(
        string $conn,
        int $rideRequestId,
        int $driverId,
        string $driverName,
        string $email
    ): void {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_EMAIL,
            RideRequestNotificationLog::STATUS_SENT,
            $driverName,
            $email,
            $driverId
        );
    }

    public function recordDriverEmailFailed(
        string $conn,
        int $rideRequestId,
        int $driverId,
        string $driverName,
        string $email,
        string $error
    ): void {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_EMAIL,
            RideRequestNotificationLog::STATUS_FAILED,
            $driverName,
            $email,
            $driverId,
            $error
        );
    }

    public function recordDriverEmailsSkipped(string $conn, int $rideRequestId, string $reason): void
    {
        $this->record(
            $conn,
            $rideRequestId,
            RideRequestNotificationLog::CHANNEL_EMAIL,
            RideRequestNotificationLog::STATUS_SKIPPED,
            null,
            null,
            null,
            $reason
        );
    }
}

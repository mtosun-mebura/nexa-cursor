<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\DriverCompletedRideArchiveService;
use Tests\TestCase;

class DriverCompletedRideArchiveServiceTest extends TestCase
{
    public function test_archive_unarchive_and_delete_flags_in_booking_payload(): void
    {
        $ride = new RideRequest([
            'status' => RideRequest::STATUS_COMPLETED,
            'booking_payload' => ['channel' => 'test'],
        ]);
        $ride->id = 42;

        $service = new DriverCompletedRideArchiveService();

        // Zonder save naar DB: mirror de payload-mutaties op het model.
        $payload = ['channel' => 'test'];
        $ride->booking_payload = $payload;

        $ride->booking_payload = array_merge($payload, [
            DriverCompletedRideArchiveService::PAYLOAD_KEY => [
                'archived_at' => '2026-11-09T10:00:00+01:00',
                'driver_id' => 7,
            ],
        ]);
        $this->assertTrue($service->isArchived($ride, 7));
        $this->assertFalse($service->isDeleted($ride, 7));
        $this->assertFalse($service->isArchived($ride, 8));

        $ride->booking_payload = array_merge($payload, [
            DriverCompletedRideArchiveService::PAYLOAD_KEY => [
                'archived_at' => '2026-11-09T10:00:00+01:00',
                'driver_id' => 7,
                'deleted_at' => '2026-11-09T11:00:00+01:00',
            ],
        ]);
        $this->assertTrue($service->isArchived($ride, 7));
        $this->assertTrue($service->isDeleted($ride, 7));
    }
}

<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\MarketplacePickupConfirmationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarketplacePickupConfirmationDistanceTest extends TestCase
{
    #[Test]
    public function format_approx_distance_keeps_meters_under_one_km(): void
    {
        $svc = app(MarketplacePickupConfirmationService::class);

        $this->assertSame('400 m', $svc->formatApproxDistance(400));
        $this->assertSame('999 m', $svc->formatApproxDistance(999));
    }

    #[Test]
    public function format_approx_distance_splits_km_and_meters(): void
    {
        $svc = app(MarketplacePickupConfirmationService::class);

        $this->assertSame('1 km', $svc->formatApproxDistance(1000));
        $this->assertSame('10 km 564 m', $svc->formatApproxDistance(10564));
        $this->assertSame('2 km 1 m', $svc->formatApproxDistance(2001));
    }
}

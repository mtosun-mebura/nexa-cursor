<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiMolliePaymentService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiMolliePaymentServiceTest extends TestCase
{
    #[Test]
    public function cancel_url_differs_from_redirect_and_keeps_query(): void
    {
        $redirect = 'https://nexasuite.online/taxi/boeking/betaling/terug?ride=159';
        $cancel = TaxiMolliePaymentService::cancelUrlFromRedirect($redirect);

        $this->assertNotSame($redirect, $cancel);
        $this->assertStringContainsString('ride=159', $cancel);
        $this->assertStringContainsString('mollie_cancel=1', $cancel);
    }

    #[Test]
    public function cancel_url_appends_query_when_redirect_has_none(): void
    {
        $redirect = 'https://nexasuite.online/taxi/boeking/betaling/terug';
        $cancel = TaxiMolliePaymentService::cancelUrlFromRedirect($redirect);

        $this->assertSame($redirect.'?mollie_cancel=1', $cancel);
    }
}

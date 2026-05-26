<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiBookingSummaryText;
use Tests\TestCase;

class TaxiBookingSummaryTextTest extends TestCase
{
    public function test_build_includes_core_fields_and_reference(): void
    {
        $ride = new RideRequest([
            'id' => 42,
            'customer_name' => 'Jan Jansen',
            'customer_phone' => '+31612345678',
            'customer_email' => 'jan@example.com',
            'pickup_address' => 'Stationsplein 1, Amsterdam',
            'dropoff_address' => 'Dam 1, Amsterdam',
            'pickup_at' => '2026-05-20 14:30:00',
            'passengers' => 2,
            'customer_note' => 'Bij ingang',
            'selected_offer_payload' => ['title' => 'Standaard', 'price' => 35.5],
            'booking_payload' => [
                'step_data' => [
                    'baggage' => ['koffer' => 1],
                    'return_trip' => true,
                ],
            ],
        ]);
        $ride->id = 42;

        $text = (new TaxiBookingSummaryText)->build($ride, [
            'stopovers' => ['Utrecht CS'],
            'return_at' => '2026-05-20 18:00:00',
            'section_config' => [
                'baggage_items' => [['key' => 'koffer', 'title' => 'Koffer']],
            ],
        ]);

        $this->assertStringContainsString('Nieuwe taxiboeking', $text);
        $this->assertStringContainsString('Jan Jansen', $text);
        $this->assertStringContainsString('Tussenstops: Utrecht CS', $text);
        $this->assertStringContainsString('Retour: Ja', $text);
        $this->assertStringContainsString('Referentie: rit #42', $text);
        $this->assertStringContainsString('Koffer x 1', $text);
        $this->assertStringContainsString('Prijsindicatie: € 35,50', $text);
    }
}

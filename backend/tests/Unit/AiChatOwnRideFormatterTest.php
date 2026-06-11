<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Models\RideRequest;
use App\Services\AiChat\AiChatOwnRideFormatter;
use Tests\TestCase;

class AiChatOwnRideFormatterTest extends TestCase
{
    private AiChatOwnRideFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new AiChatOwnRideFormatter();
    }

    public function test_confirmed_reservation_returns_yes_answer(): void
    {
        $answer = $this->formatter->format([
            [
                'status' => RideRequest::STATUS_ACCEPTED,
                'status_label' => 'Geaccepteerd',
                'pickup_at' => '2026-06-10 14:30:00',
                'pickup_address' => 'Damrak 1',
                'dropoff_address' => 'Schiphol',
            ],
        ], 'status');

        $this->assertStringContainsString('Ja, je reservering is bevestigd', $answer);
        $this->assertStringContainsString('Geaccepteerd', $answer);
    }

    public function test_empty_rows_returns_helpful_message(): void
    {
        $answer = $this->formatter->format([], 'status');

        $this->assertStringContainsString('geen actieve reservering', $answer);
    }

    public function test_gepland_count_answer(): void
    {
        $answer = $this->formatter->format([
            ['status' => RideRequest::STATUS_ACCEPTED, 'pickup_at' => now()->addDay()],
            ['status' => RideRequest::STATUS_ASSIGNED, 'pickup_at' => now()->addDays(2)],
        ], 'gepland');

        $this->assertSame('Je hebt 2 ritten gepland.', $answer);
    }

    public function test_volgende_rit_answer(): void
    {
        $answer = $this->formatter->format([
            [
                'pickup_at' => '2026-06-10 14:30:00',
                'pickup_address' => 'Damrak 1',
                'dropoff_address' => 'Schiphol',
            ],
        ], 'volgende');

        $this->assertStringContainsString('Je volgende rit is op', $answer);
        $this->assertStringContainsString('Damrak 1', $answer);
        $this->assertStringContainsString('Schiphol', $answer);
    }

    public function test_prijs_answer(): void
    {
        $answer = $this->formatter->format([
            [
                'pickup_at' => '2026-06-10 14:30:00',
                'display_price' => 45.50,
            ],
        ], 'prijs');

        $this->assertStringContainsString('€ 45,50', $answer);
    }

    public function test_vandaag_lists_all_rides_for_today(): void
    {
        $answer = $this->formatter->format([
            [
                'pickup_at' => '2026-06-10 09:00:00',
                'pickup_address' => 'Damrak 1',
                'dropoff_address' => 'Schiphol',
                'status_label' => 'Geaccepteerd',
            ],
            [
                'pickup_at' => '2026-06-10 18:00:00',
                'pickup_address' => 'Centraal',
                'dropoff_address' => 'Arena',
                'status_label' => 'Ingepland',
            ],
        ], 'vandaag');

        $this->assertStringContainsString('Je hebt vandaag 2 ritten', $answer);
        $this->assertStringContainsString('Damrak 1', $answer);
        $this->assertStringContainsString('Centraal', $answer);
    }

    public function test_factuur_answer_with_pdf_link(): void
    {
        $answer = $this->formatter->format([
            [
                'pickup_at' => '2026-06-09 10:00:00',
                'invoice_number' => 'INV-2026-001',
                'invoice_pdf_url' => 'https://example.test/factuur.pdf',
            ],
        ], 'factuur');

        $this->assertStringContainsString('INV-2026-001', $answer);
        $this->assertStringContainsString('[Factuur downloaden](https://example.test/factuur.pdf)', $answer);
    }
}

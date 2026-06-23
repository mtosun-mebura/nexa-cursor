<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatRouteQuoteParser;
use Tests\TestCase;

class AiChatRouteQuoteParserTest extends TestCase
{
    private AiChatRouteQuoteParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new AiChatRouteQuoteParser();
    }

    public function test_parses_destination_only_question(): void
    {
        $route = $this->parser->parseRouteFromQuestion('Wat kost een rit naar Schiphol?');

        $this->assertNull($route['pickup_address']);
        $this->assertSame('Schiphol', $route['dropoff_address']);
    }

    public function test_parses_travel_intent_destination(): void
    {
        $route = $this->parser->parseRouteFromQuestion('Ik wil naar Schiphol');

        $this->assertNull($route['pickup_address']);
        $this->assertSame('Schiphol', $route['dropoff_address']);
    }

    public function test_parses_full_route_question(): void
    {
        $route = $this->parser->parseRouteFromQuestion('Wat kost een rit van Enschede naar Düsseldorf Airport?');

        $this->assertSame('Enschede', $route['pickup_address']);
        $this->assertSame('Düsseldorf Airport', $route['dropoff_address']);
    }

    public function test_parses_passengers_baggage_and_datetime(): void
    {
        $this->assertSame(3, $this->parser->parsePassengers('3 personen'));
        $this->assertSame(2, $this->parser->parseBaggagePieces('2 koffers'));
        $this->assertNotNull($this->parser->parsePickupDatetime('morgen 10:00'));
        $this->assertNotNull($this->parser->parsePickupDatetime('2026-12-15T14:30'));
    }

    public function test_validates_contact_fields(): void
    {
        $this->assertTrue($this->parser->isValidContactName('Jan'));
        $this->assertFalse($this->parser->isValidContactName('J'));
        $this->assertTrue($this->parser->isValidPhone('0612345678'));
        $this->assertTrue($this->parser->isValidPhone('+31612345678'));
        $this->assertFalse($this->parser->isValidPhone('123'));
        $this->assertTrue($this->parser->isValidEmail('jan@example.com'));
        $this->assertFalse($this->parser->isValidEmail('geen-email'));
    }
}

<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatPublicRatesFormatter;
use Tests\TestCase;

class AiChatPublicRatesFormatterTest extends TestCase
{
    public function test_formats_primary_rate_row_in_dutch(): void
    {
        $formatter = new AiChatPublicRatesFormatter();

        $answer = $formatter->format([
            [
                'person_range' => '1-4',
                'base_fare' => '3.60',
                'price_per_km' => '2.65',
                'price_per_min' => '0.44',
                'min_fare' => '0.00',
            ],
        ], 'Nexa Taxi');

        $this->assertStringContainsString('• Instaptarief: €3,60', $answer);
        $this->assertStringContainsString('• Kilometertarief: €2,65 per km', $answer);
        $this->assertStringContainsString('• Minuuttarief: €0,44 per minuut', $answer);
        $this->assertStringContainsString('• Wachttarief: €26,40 per uur', $answer);
        $this->assertStringContainsString("De actuele tarieven van Nexa Taxi:\n\n", $answer);
        $this->assertStringNotContainsString(', ', $answer);
    }

    public function test_formats_multiple_person_ranges_as_separate_sections(): void
    {
        $formatter = new AiChatPublicRatesFormatter();

        $answer = $formatter->format([
            [
                'person_range' => '1-4',
                'base_fare' => '3.60',
                'price_per_km' => '2.65',
                'price_per_min' => '0.44',
                'min_fare' => '49.64',
            ],
            [
                'person_range' => '5-8',
                'base_fare' => '7.33',
                'price_per_km' => '7.33',
                'price_per_min' => '0.49',
                'min_fare' => '49.64',
            ],
        ], 'Nexa Taxi');

        $this->assertStringContainsString("1-4 personen\n• Instaptarief: €3,60", $answer);
        $this->assertStringContainsString("5-8 personen\n• Instaptarief: €7,33", $answer);
    }
}

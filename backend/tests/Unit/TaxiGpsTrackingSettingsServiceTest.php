<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Services\TaxiGpsRoadPathService;
use App\Modules\NexaTaxi\Services\TaxiGpsTrackingSettingsService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiGpsTrackingSettingsServiceTest extends TestCase
{
    #[Test]
    public function appearance_falls_back_to_readable_defaults(): void
    {
        $settings = app(TaxiGpsTrackingSettingsService::class);
        $appearance = $settings->normalizeAppearance([
            'car_style' => 'unknown',
            'car_color' => 'nope',
            'refresh_seconds' => 99,
        ]);

        $this->assertSame('sedan', $appearance['car_style']);
        $this->assertSame('single', $appearance['car_color_mode']);
        $this->assertSame('#ea580c', $appearance['car_color']);
        $this->assertSame('#ea580c', $appearance['type_colors']['sedan']);
        $this->assertSame('#ea580c', $appearance['type_colors']['van']);
        $this->assertSame('#ea580c', $appearance['type_colors']['bus']);
        $this->assertArrayNotHasKey('hatchback', $appearance['type_colors']);
        $this->assertSame([], $appearance['vehicle_colors']);
        $this->assertSame('#f7e125', $appearance['plate_background']);
        $this->assertSame('#111827', $appearance['plate_text_color']);
        $this->assertSame(30, $appearance['refresh_seconds']);
    }

    #[Test]
    public function appearance_keeps_chosen_colors_and_refresh(): void
    {
        $settings = app(TaxiGpsTrackingSettingsService::class);
        $appearance = $settings->normalizeAppearance([
            'car_style' => 'van',
            'car_color_mode' => 'single',
            'car_color' => '1d4ed8',
            'plate_background' => '#FFFFFF',
            'plate_text_color' => '#000000',
            'plate_border_color' => '#111111',
            'refresh_seconds' => 1,
        ]);

        $this->assertSame('van', $appearance['car_style']);
        $this->assertSame('single', $appearance['car_color_mode']);
        $this->assertSame('#1d4ed8', $appearance['car_color']);
        $this->assertSame('#1d4ed8', $appearance['type_colors']['sedan']);
        $this->assertSame('#1d4ed8', $appearance['type_colors']['bus']);
        $this->assertSame('#ffffff', $appearance['plate_background']);
        $this->assertSame(1, $appearance['refresh_seconds']);
    }

    #[Test]
    public function appearance_keeps_per_vehicle_colors(): void
    {
        $settings = app(TaxiGpsTrackingSettingsService::class);
        $appearance = $settings->normalizeAppearance([
            'car_color_mode' => 'per_vehicle',
            'vehicle_colors' => [
                '14' => '1d4ed8',
                0 => '#dc2626',
                'abc' => '#15803d',
                '15' => 'not-a-color',
            ],
        ]);

        $this->assertSame('per_vehicle', $appearance['car_color_mode']);
        $this->assertArrayHasKey('14', $appearance['vehicle_colors']);
        $this->assertSame('#1d4ed8', $appearance['vehicle_colors']['14']);
        $this->assertArrayHasKey('15', $appearance['vehicle_colors']);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $appearance['vehicle_colors']['15']);
        $this->assertArrayNotHasKey('0', $appearance['vehicle_colors']);
        $this->assertArrayNotHasKey('abc', $appearance['vehicle_colors']);
    }

    #[Test]
    public function appearance_keeps_a_color_per_vehicle_type(): void
    {
        $settings = app(TaxiGpsTrackingSettingsService::class);
        $appearance = $settings->normalizeAppearance([
            'car_style' => 'hatchback',
            'car_color_mode' => 'single',
            'car_color' => '#ea580c',
            'type_colors' => [
                'sedan' => '1d4ed8',
                'van' => '#15803d',
                'bus' => '#dc2626',
                'hatchback' => '#ffffff',
            ],
        ]);

        $this->assertSame('sedan', $appearance['car_style']);
        $this->assertSame('#1d4ed8', $appearance['car_color']);
        $this->assertSame('#1d4ed8', $appearance['type_colors']['sedan']);
        $this->assertSame('#15803d', $appearance['type_colors']['van']);
        $this->assertSame('#dc2626', $appearance['type_colors']['bus']);
        $this->assertArrayNotHasKey('hatchback', $appearance['type_colors']);
        $this->assertSame('sedan', TaxiGpsTrackingSettingsService::styleFromVehicleType('car'));
        $this->assertSame('van', TaxiGpsTrackingSettingsService::styleFromVehicleType('van'));
        $this->assertSame('bus', TaxiGpsTrackingSettingsService::styleFromVehicleType('bus'));
    }

    #[Test]
    public function polyline_decoder_reads_google_sample(): void
    {
        $points = app(TaxiGpsRoadPathService::class)->decodePolyline('_p~iF~ps|U_ulLnnqC_mqNvxq`@');

        $this->assertCount(3, $points);
        $this->assertEqualsWithDelta(38.5, $points[0][0], 0.001);
        $this->assertEqualsWithDelta(-120.2, $points[0][1], 0.001);
        $this->assertEqualsWithDelta(43.252, $points[2][0], 0.001);
    }
}

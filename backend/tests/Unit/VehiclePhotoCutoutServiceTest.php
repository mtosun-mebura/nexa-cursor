<?php

namespace Tests\Unit;

use App\Services\VehiclePhotoCutoutService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehiclePhotoCutoutServiceTest extends TestCase
{
    public function test_without_backends_returns_original_file(): void
    {
        config([
            'services.vehicle_cutout.driver' => 'auto',
            'services.vehicle_cutout.rembg.url' => '',
            'services.vehicle_cutout.rembg.bin' => 'rembg-does-not-exist-xyz',
            'services.vehicle_cutout.rembg.docker' => false,
            'services.vehicle_cutout.removebg.key' => '',
            'services.removebg.key' => '',
        ]);

        $original = UploadedFile::fake()->image('car.jpg', 640, 360);
        $result = app(VehiclePhotoCutoutService::class)->process($original);

        $this->assertFalse($result['cutout']);
        $this->assertNull($result['driver']);
        $this->assertSame($original, $result['file']);
        $this->assertFalse(app(VehiclePhotoCutoutService::class)->enabled());
    }

    public function test_rembg_http_produces_png_cutout(): void
    {
        config([
            'services.vehicle_cutout.driver' => 'rembg',
            'services.vehicle_cutout.rembg.url' => 'http://rembg.test',
            'services.vehicle_cutout.rembg.bin' => 'rembg-does-not-exist-xyz',
            'services.vehicle_cutout.rembg.docker' => false,
            'services.vehicle_cutout.removebg.key' => '',
        ]);

        $pngBinary = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        Http::fake([
            'http://rembg.test/api/remove' => Http::response($pngBinary, 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $original = UploadedFile::fake()->image('car.jpg', 320, 180);
        $result = app(VehiclePhotoCutoutService::class)->process($original);

        $this->assertTrue($result['cutout']);
        $this->assertSame('rembg', $result['driver']);
        $this->assertSame('image/png', $result['file']->getMimeType());
        $this->assertTrue(app(VehiclePhotoCutoutService::class)->enabled());
    }

    public function test_removebg_fallback_when_rembg_unavailable(): void
    {
        config([
            'services.vehicle_cutout.driver' => 'auto',
            'services.vehicle_cutout.rembg.url' => '',
            'services.vehicle_cutout.rembg.bin' => 'rembg-does-not-exist-xyz',
            'services.vehicle_cutout.rembg.docker' => false,
            'services.vehicle_cutout.removebg.key' => 'test-key',
        ]);

        $pngBinary = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
        );
        Http::fake([
            'https://api.remove.bg/v1.0/removebg' => Http::response($pngBinary, 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $original = UploadedFile::fake()->image('car.jpg', 320, 180);
        $result = app(VehiclePhotoCutoutService::class)->process($original);

        $this->assertTrue($result['cutout']);
        $this->assertSame('removebg', $result['driver']);
    }
}

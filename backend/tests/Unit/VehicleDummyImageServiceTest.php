<?php

namespace Tests\Unit;

use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Services\VehicleDummyImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleDummyImageServiceTest extends TestCase
{
    private VehicleDummyImageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->service = app(VehicleDummyImageService::class);
        try {
            GeneralSetting::set(VehicleDummyImageService::SETTING_BACKGROUND, '');
            GeneralSetting::set(VehicleDummyImageService::SETTING_SELECTED, '');
            GeneralSetting::set(VehicleDummyImageService::SETTING_CUSTOM, '[]');
            GeneralSetting::clearRequestCache();
        } catch (\RuntimeException $e) {
            $this->markTestSkipped($e->getMessage());
        }
    }

    #[Test]
    public function builtins_are_grouped_per_type_with_five_defaults(): void
    {
        $builtins = $this->service->builtins();
        $this->assertCount(5, $builtins);

        $byType = collect($builtins)->groupBy('type');
        $this->assertCount(2, $byType->get(Vehicle::TYPE_CAR));
        $this->assertCount(2, $byType->get(Vehicle::TYPE_VAN));
        $this->assertCount(1, $byType->get(Vehicle::TYPE_BUS));

        $this->assertStringContainsString('vehicle-dummy-car-yellow.png', $this->service->publicUrlForType(Vehicle::TYPE_CAR));
        $this->assertStringContainsString('vehicle-dummy-van-yellow.png', $this->service->publicUrlForType(Vehicle::TYPE_VAN));
        $this->assertStringContainsString('vehicle-dummy-bus.png', $this->service->publicUrlForType(Vehicle::TYPE_BUS));
    }

    #[Test]
    public function background_defaults_to_transparent(): void
    {
        $this->assertNull($this->service->backgroundColor());
        $this->service->setBackgroundColor('#112233');
        GeneralSetting::clearRequestCache();
        $this->assertSame('#112233', $this->service->backgroundColor());
        $this->service->setBackgroundColor('');
        GeneralSetting::clearRequestCache();
        $this->assertNull($this->service->backgroundColor());
    }

    #[Test]
    public function can_select_alternate_builtin_per_type(): void
    {
        $ok = $this->service->select(Vehicle::TYPE_CAR, 'builtin-car-black');
        $this->assertTrue($ok);
        GeneralSetting::clearRequestCache();
        $this->assertStringContainsString('vehicle-dummy-car-black.png', $this->service->publicUrlForType(Vehicle::TYPE_CAR));
    }

    #[Test]
    public function upload_requires_type_and_auto_selects(): void
    {
        $file = UploadedFile::fake()->image('busje.png', 200, 100);
        $item = $this->service->addUpload($file, Vehicle::TYPE_VAN);
        $this->assertFalse($item['builtin']);
        $this->assertSame(Vehicle::TYPE_VAN, $item['type']);
        GeneralSetting::clearRequestCache();
        $this->assertSame($item['id'], $this->service->selectedIds()[Vehicle::TYPE_VAN]);
        Storage::disk('public')->assertExists($item['path']);
    }
}

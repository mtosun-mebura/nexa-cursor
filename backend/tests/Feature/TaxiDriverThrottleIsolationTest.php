<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDriverThrottleIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function () {
            Route::put('/_test/taxi-driver-location', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-location');
            Route::post('/_test/taxi-driver-accept', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-action');
            Route::post('/_test/taxi-driver-complete', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-action');
        });
    }

    #[Test]
    public function gps_polling_does_not_block_accept_or_complete(): void
    {
        $driver = User::factory()->create();
        $this->actingAs($driver);

        for ($i = 0; $i < 40; $i++) {
            $this->putJson('/_test/taxi-driver-location')->assertOk();
        }

        $this->postJson('/_test/taxi-driver-accept')->assertOk();
        $this->postJson('/_test/taxi-driver-complete')->assertOk();
    }
}

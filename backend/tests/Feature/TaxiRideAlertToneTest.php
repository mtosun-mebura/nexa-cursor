<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiRideAlertToneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
    }

    #[Test]
    public function driver_can_save_ride_alert_tone(): void
    {
        $user = User::factory()->create(['ride_alert_tone' => 'classic']);
        $request = Request::create('/api/taxi/v1/driver/ride-alert-tone', 'PUT', [
            'tone' => 'siren',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');

        $response = app(DriverAuthController::class)->updateRideAlertTone($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('siren', $response->getData(true)['ride_alert_tone'] ?? null);
        $this->assertSame('siren', $user->fresh()->ride_alert_tone);
    }

    #[Test]
    public function invalid_ride_alert_tone_is_rejected(): void
    {
        $user = User::factory()->create(['ride_alert_tone' => 'classic']);
        $request = Request::create('/api/taxi/v1/driver/ride-alert-tone', 'PUT', [
            'tone' => 'klaxon',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        app(DriverAuthController::class)->updateRideAlertTone($request);
    }

    #[Test]
    public function chauffeur_profile_shows_five_ride_tones(): void
    {
        $html = view('taxi::driver-app.index', [
            'apiBase' => 'http://localhost/api/taxi/v1/driver',
            'pollMs' => 2000,
            'streamEnabled' => false,
            'appUrl' => 'http://localhost/taxi/chauffeur',
            'guideUrl' => 'http://localhost/taxi/chauffeur/handleiding',
            'faviconUrl' => '/favicon.ico',
            'faviconType' => 'image/x-icon',
            'notificationIcon' => '/favicon.ico',
        ])->render();

        $this->assertStringContainsString('Ritgeluid', $html);
        $this->assertStringContainsString('data-ride-tone="classic"', $html);
        $this->assertStringContainsString('data-ride-tone="chime"', $html);
        $this->assertStringContainsString('data-ride-tone="alert"', $html);
        $this->assertStringContainsString('data-ride-tone="soft"', $html);
        $this->assertStringContainsString('data-ride-tone="siren"', $html);
        $this->assertStringContainsString('Klassiek', $html);
        $this->assertStringContainsString('Bel', $html);
        $this->assertStringContainsString('Sirene', $html);
    }
}

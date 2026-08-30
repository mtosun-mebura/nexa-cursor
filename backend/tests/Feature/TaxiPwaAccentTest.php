<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\NexaTaxi\Controllers\Api\ContractPortalAuthController;
use App\Modules\NexaTaxi\Controllers\Api\DriverAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiPwaAccentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        View::addNamespace('taxi', app_path('Modules/NexaTaxi/Resources/views'));
    }

    #[Test]
    public function driver_can_save_pwa_accent(): void
    {
        $user = User::factory()->create(['pwa_accent' => 'orange']);
        $request = Request::create('/api/taxi/v1/driver/accent', 'PUT', [
            'accent' => 'pink',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');

        $response = app(DriverAuthController::class)->updateAccent($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('pink', $response->getData(true)['pwa_accent'] ?? null);
        $this->assertSame('pink', $user->fresh()->pwa_accent);
    }

    #[Test]
    public function contract_can_save_pwa_accent(): void
    {
        $user = User::factory()->create(['pwa_accent' => 'orange']);
        $request = Request::create('/api/taxi/v1/contract/accent', 'PUT', [
            'accent' => 'yellow',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');

        $response = app(ContractPortalAuthController::class)->updateAccent($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('yellow', $response->getData(true)['pwa_accent'] ?? null);
        $this->assertSame('yellow', $user->fresh()->pwa_accent);
    }

    #[Test]
    public function invalid_accent_is_rejected(): void
    {
        $user = User::factory()->create(['pwa_accent' => 'orange']);
        $request = Request::create('/api/taxi/v1/driver/accent', 'PUT', [
            'accent' => 'purple',
        ]);
        $request->setUserResolver(fn () => $user);
        $request->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        app(DriverAuthController::class)->updateAccent($request);
    }

    #[Test]
    public function chauffeur_and_contract_profile_show_accent_picker(): void
    {
        $driverHtml = view('taxi::driver-app.index', [
            'apiBase' => 'http://localhost/api/taxi/v1/driver',
            'pollMs' => 2000,
            'streamEnabled' => false,
            'appUrl' => 'http://localhost/taxi/chauffeur',
            'guideUrl' => 'http://localhost/taxi/chauffeur/handleiding',
            'faviconUrl' => '/favicon.ico',
            'faviconType' => 'image/x-icon',
            'notificationIcon' => '/favicon.ico',
        ])->render();

        $contractHtml = view('taxi::contract-portal.index', [
            'apiBase' => 'http://localhost/api/taxi/v1/contract',
            'pollMs' => 8000,
            'appUrl' => 'http://localhost/taxi/contract',
            'guideUrl' => 'http://localhost/taxi/contract/handleiding',
            'faviconUrl' => '/favicon.ico',
            'faviconType' => 'image/x-icon',
        ])->render();

        foreach ([$driverHtml, $contractHtml] as $html) {
            $this->assertStringContainsString('data-pwa-accent="orange"', $html);
            $this->assertStringContainsString('data-pwa-accent="yellow"', $html);
            $this->assertStringContainsString('data-pwa-accent="blue"', $html);
            $this->assertStringContainsString('data-pwa-accent="red"', $html);
            $this->assertStringContainsString('data-pwa-accent="green"', $html);
            $this->assertStringContainsString('data-pwa-accent="pink"', $html);
            $this->assertStringContainsString('Themakleur', $html);
        }
    }
}

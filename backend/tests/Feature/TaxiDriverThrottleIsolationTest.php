<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiDriverThrottleIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->routeUriExists('_test/taxi-driver-accept')) {
            return;
        }

        Route::middleware('web')->group(function () {
            Route::put('/_test/taxi-driver-location', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-location');
            Route::get('/_test/taxi-driver-inbox', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-poll');
            Route::post('/_test/taxi-driver-accept', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-accept');
            Route::post('/_test/taxi-driver-cancel', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-cancel');
            Route::put('/_test/taxi-driver-availability', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-availability');
            Route::post('/_test/taxi-driver-complete', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-action');
            Route::post('/_test/taxi-driver-payment', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-action');
            Route::post('/_test/taxi-driver-login', fn () => response()->json(['ok' => true]))
                ->middleware('throttle:taxi-driver-login');
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

    #[Test]
    public function driver_can_accept_twice_after_poll_location_and_action_limits_are_full(): void
    {
        $driver = User::factory()->create();
        $this->actingAs($driver);

        for ($i = 0; $i < 180; $i++) {
            $this->putJson('/_test/taxi-driver-location')->assertOk();
            $this->getJson('/_test/taxi-driver-inbox')->assertOk();
        }
        $this->putJson('/_test/taxi-driver-location')->assertStatus(429);
        $this->getJson('/_test/taxi-driver-inbox')->assertStatus(429);

        for ($i = 0; $i < 90; $i++) {
            $this->postJson('/_test/taxi-driver-payment')->assertOk();
        }
        $this->postJson('/_test/taxi-driver-payment')->assertStatus(429);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/_test/taxi-driver-login', ['email' => 'chauffeur@example.test'])->assertOk();
        }
        $this->postJson('/_test/taxi-driver-login', ['email' => 'chauffeur@example.test'])->assertStatus(429);

        $this->postJson('/_test/taxi-driver-accept')->assertOk();
        $this->postJson('/_test/taxi-driver-accept')->assertOk();
    }

    #[Test]
    public function driver_can_cancel_twice_after_poll_location_and_action_limits_are_full(): void
    {
        $driver = User::factory()->create();
        $this->actingAs($driver);
        $this->fillSharedDriverLimits();

        $this->postJson('/_test/taxi-driver-cancel')->assertOk();
        $this->postJson('/_test/taxi-driver-cancel')->assertOk();
    }

    #[Test]
    public function driver_can_go_online_after_the_shared_action_limit_is_full(): void
    {
        $driver = User::factory()->create();
        $this->actingAs($driver);
        $this->fillSharedDriverLimits();

        $this->putJson('/_test/taxi-driver-availability', ['is_online' => true])->assertOk();
        $this->putJson('/_test/taxi-driver-availability', ['is_online' => false])->assertOk();
    }

    #[Test]
    public function availability_route_does_not_use_the_shared_driver_action_limiter(): void
    {
        if ($this->driverAvailabilityRoute() === null) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/taxi')
                ->group(app_path('Modules/NexaTaxi/Routes/api.php'));
        }

        $route = $this->driverAvailabilityRoute();
        $this->assertNotNull($route);
        $middleware = implode(' ', array_map('strval', $route->middleware()));
        $this->assertStringContainsString('taxi-driver-availability', $middleware);
        $this->assertStringNotContainsString('taxi-driver-action', $middleware);
    }

    #[Test]
    public function accept_route_does_not_use_the_shared_driver_action_limiter(): void
    {
        if ($this->driverAcceptRoute() === null) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/taxi')
                ->group(app_path('Modules/NexaTaxi/Routes/api.php'));
        }

        $route = $this->driverAcceptRoute();
        $this->assertNotNull($route);
        $middleware = implode(' ', array_map('strval', $route->middleware()));
        $this->assertStringContainsString('taxi-driver-accept', $middleware);
        $this->assertStringNotContainsString('taxi-driver-action', $middleware);
    }

    #[Test]
    public function cancel_route_does_not_use_the_shared_driver_action_limiter(): void
    {
        if ($this->driverCancelRoute() === null) {
            Route::middleware(['api', 'auth:sanctum'])
                ->prefix('api/taxi')
                ->group(app_path('Modules/NexaTaxi/Routes/api.php'));
        }

        $route = $this->driverCancelRoute();
        $this->assertNotNull($route);
        $middleware = implode(' ', array_map('strval', $route->middleware()));
        $this->assertStringContainsString('taxi-driver-cancel', $middleware);
        $this->assertStringNotContainsString('taxi-driver-action', $middleware);
    }

    #[Test]
    public function accept_too_many_attempts_is_dutch_and_login_throttle_stays_english(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);

        $accept = $handler->render(
            Request::create('/api/taxi/v1/driver/dispatch/offers/5/accept', 'POST', server: [
                'HTTP_ACCEPT' => 'application/json',
            ]),
            new ThrottleRequestsException('Too Many Attempts.')
        );
        $acceptBody = json_decode($accept->getContent(), true);
        $this->assertSame(429, $accept->getStatusCode());
        $this->assertSame(
            'Te veel pogingen om deze rit te accepteren. Wacht even en probeer het opnieuw.',
            $acceptBody['message'] ?? null
        );
        $this->assertStringNotContainsString('Too Many Attempts', (string) ($acceptBody['message'] ?? ''));

        $cancel = $handler->render(
            Request::create('/api/taxi/v1/driver/dispatch/rides/5/cancel', 'POST', server: [
                'HTTP_ACCEPT' => 'application/json',
            ]),
            new ThrottleRequestsException('Too Many Attempts.')
        );
        $cancelBody = json_decode($cancel->getContent(), true);
        $this->assertSame(429, $cancel->getStatusCode());
        $this->assertSame(
            'Te veel pogingen om deze rit te annuleren. Wacht even en probeer het opnieuw.',
            $cancelBody['message'] ?? null
        );
        $this->assertStringNotContainsString('Too Many Attempts', (string) ($cancelBody['message'] ?? ''));

        $availability = $handler->render(
            Request::create('/api/taxi/v1/driver/availability', 'PUT', server: [
                'HTTP_ACCEPT' => 'application/json',
            ]),
            new ThrottleRequestsException('Too Many Attempts.')
        );
        $availabilityBody = json_decode($availability->getContent(), true);
        $this->assertSame(429, $availability->getStatusCode());
        $this->assertSame(
            'Te veel pogingen om online te gaan. Wacht even en probeer het opnieuw.',
            $availabilityBody['message'] ?? null
        );
        $this->assertStringNotContainsString('Too Many Attempts', (string) ($availabilityBody['message'] ?? ''));

        $location = $handler->render(
            Request::create('/api/taxi/v1/driver/availability/location', 'PUT', server: [
                'HTTP_ACCEPT' => 'application/json',
            ]),
            new ThrottleRequestsException('Too Many Attempts.')
        );
        $this->assertStringContainsString('Too Many Attempts', $location->getContent());

        $login = $handler->render(
            Request::create('/api/taxi/v1/driver/login', 'POST', server: [
                'HTTP_ACCEPT' => 'application/json',
            ]),
            new ThrottleRequestsException('Too Many Attempts.')
        );
        $this->assertStringContainsString('Too Many Attempts', $login->getContent());
    }

    private function fillSharedDriverLimits(): void
    {
        for ($i = 0; $i < 180; $i++) {
            $this->putJson('/_test/taxi-driver-location')->assertOk();
            $this->getJson('/_test/taxi-driver-inbox')->assertOk();
        }
        $this->putJson('/_test/taxi-driver-location')->assertStatus(429);
        $this->getJson('/_test/taxi-driver-inbox')->assertStatus(429);

        for ($i = 0; $i < 90; $i++) {
            $this->postJson('/_test/taxi-driver-payment')->assertOk();
        }
        $this->postJson('/_test/taxi-driver-payment')->assertStatus(429);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/_test/taxi-driver-login', ['email' => 'chauffeur@example.test'])->assertOk();
        }
        $this->postJson('/_test/taxi-driver-login', ['email' => 'chauffeur@example.test'])->assertStatus(429);
    }

    private function routeUriExists(string $uri): bool
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri) {
                return true;
            }
        }

        return false;
    }

    private function driverAcceptRoute(): ?\Illuminate\Routing\Route
    {
        return $this->driverPostRoute('api/taxi/v1/driver/dispatch/offers/{offer}/accept');
    }

    private function driverCancelRoute(): ?\Illuminate\Routing\Route
    {
        return $this->driverPostRoute('api/taxi/v1/driver/dispatch/rides/{ride}/cancel');
    }

    private function driverAvailabilityRoute(): ?\Illuminate\Routing\Route
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === 'api/taxi/v1/driver/availability' && in_array('PUT', $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }

    private function driverPostRoute(string $uri): ?\Illuminate\Routing\Route
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === $uri && in_array('POST', $route->methods(), true)) {
                return $route;
            }
        }

        return null;
    }
}

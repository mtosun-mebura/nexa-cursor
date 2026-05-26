<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Smoke tests: belangrijke guest-routes moeten een geldige response geven (geen 500).
 */
class GuestRoutesSmokeTest extends TestCase
{
    #[Test]
    public function home_route_returns_200_or_redirect(): void
    {
        $response = $this->get(route('home'));
        $this->assertContains($response->status(), [200, 302]);
    }

    #[Test]
    public function admin_login_page_returns_200(): void
    {
        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
    }
}

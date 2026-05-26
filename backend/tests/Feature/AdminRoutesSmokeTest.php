<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Smoke tests: belangrijke admin-routes moeten voor guest naar login redirecten,
 * en voor super-admin een geldige response geven (niet 500).
 */
class AdminRoutesSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function admin_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }

    #[Test]
    public function admin_website_pages_index_redirects_guest_to_login(): void
    {
        $response = $this->get(route('admin.website-pages.index'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }

    #[Test]
    public function admin_website_pages_create_redirects_guest_to_login(): void
    {
        $response = $this->get(route('admin.website-pages.create'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }

    #[Test]
    public function admin_dashboard_returns_200_for_super_admin(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('vacancies')) {
            $this->markTestSkipped('vacancies table required (e.g. run module migrations)');
        }
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $response = $this->actingAs($user)->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    #[Test]
    public function admin_dashboard_returns_200_for_super_admin_when_default_guard_is_api(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('vacancies')) {
            $this->markTestSkipped('vacancies table required (e.g. run module migrations)');
        }
        config(['auth.defaults.guard' => 'api']);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $response = $this->actingAs($user, 'web')->get(route('admin.dashboard'));
        $response->assertStatus(200);
    }

    #[Test]
    public function admin_settings_redirects_guest_to_login(): void
    {
        $response = $this->get(route('admin.settings.index'));
        $this->assertTrue(in_array($response->status(), [302, 303], true));
        $target = $response->headers->get('Location', '');
        $this->assertTrue(
            str_contains($target, 'login') || str_contains($target, 'meld/sessie-verlopen'),
            "Guest should be redirected to login or sessie-verlopen, got: {$target}"
        );
    }
}

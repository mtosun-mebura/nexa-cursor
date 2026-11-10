<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminRestartReadiness;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRestartReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    #[Test]
    public function a_guest_admin_request_redirects_to_login_and_the_login_page_is_ok(): void
    {
        $admin = $this->get('http://localhost:8085/admin');
        $admin->assertStatus(302);
        $this->assertStringContainsString(
            '/admin/login',
            (string) $admin->headers->get('Location')
        );

        $this->get('http://localhost:8085/admin/login')->assertOk();
    }

    #[Test]
    public function the_json_upgrade_status_without_a_session_is_a_login_challenge(): void
    {
        $this->getJson(route('admin.settings.upgrade.php-status'))
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->assertTrue(AdminRestartReadiness::isUp(401));
        $this->assertFalse(AdminRestartReadiness::isUp(500));
    }

    #[Test]
    public function the_upgrade_page_probes_the_login_url_with_the_shared_status_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('function probeAdminUp', false)
            ->assertSee('var adminUpStatuses = '.json_encode(AdminRestartReadiness::upStatuses()), false)
            ->assertSee('opaqueredirect', false)
            ->assertSee('De admin kwam niet terug na de herstart.', false);
    }
}

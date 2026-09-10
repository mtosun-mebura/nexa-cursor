<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSystemPhpLaravelUpgradeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Http::fake([
            'www.php.net/*' => Http::response([
                '8.5.1' => ['date' => '2026-01-15'],
                '8.4.10' => ['date' => '2026-01-10'],
            ]),
            'hub.docker.com/*' => Http::response(['name' => '8.5-cli'], 200),
            'repo.packagist.org/*' => Http::response([
                'packages' => [
                    'laravel/framework' => [
                        [
                            'version' => 'v13.2.0',
                            'require' => ['php' => '^8.3'],
                        ],
                        [
                            'version' => 'v12.99.0',
                            'require' => ['php' => '^8.2'],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    #[Test]
    public function upgrade_page_shows_laravel_and_php_buttons_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('Laravel bijwerken', false)
            ->assertSee('PHP in Docker bijwerken', false)
            ->assertSee('Minor-update', false)
            ->assertSee('Major-update', false)
            ->assertSee('kt-btn-success', false)
            ->assertSee('btn-php-docker-upgrade', false);
    }

    #[Test]
    public function php_and_laravel_status_are_available_to_super_admin(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->getJson(route('admin.settings.upgrade.php-status'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.latest_tag', '8.5-cli')
            ->assertJsonPath('data.can_run', false);

        $this->actingAs($admin)
            ->getJson(route('admin.settings.upgrade.laravel-status'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.major_target', '13.2.0')
            ->assertJsonPath('data.pending_finalize', false)
            ->assertJsonPath('data.docker_ready', false);
    }

    #[Test]
    public function guests_cannot_access_runtime_upgrade_endpoints(): void
    {
        $this->getJson(route('admin.settings.upgrade.php-status'))
            ->assertUnauthorized();

        $this->getJson(route('admin.settings.upgrade.laravel-status'))
            ->assertUnauthorized();
    }

    #[Test]
    public function php_run_is_rejected_when_docker_is_unavailable(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.settings.upgrade.php-run'))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function laravel_run_requires_minor_or_major_channel(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.settings.upgrade.laravel-run'), [])
            ->assertStatus(422);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }
}

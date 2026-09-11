<?php

namespace Tests\Feature;

use App\Models\SystemUpgradeLog;
use App\Models\User;
use Illuminate\Foundation\Application;
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
            ->assertSee('ki-laravel', false)
            ->assertSee('upgrade-php-icon', false)
            ->assertSee('btn-php-docker-upgrade', false)
            ->assertSee('Docker-containers', false)
            ->assertSee('Containers herstarten', false)
            ->assertSee('Images opnieuw bouwen', false)
            ->assertSee('docker-container-select-all', false)
            ->assertSee('admin-table__check-col', false)
            ->assertSee('Dit wijzigt Laravel, PHP of de Nexa-release niet.', false)
            ->assertSee('queueAdminHeaderFlash', false)
            ->assertSee('announceUpgradeSuccess', false)
            ->assertSee('emphasizeVersions', false)
            ->assertSee('font-semibold text-foreground', false)
            ->assertSee('Upgradegeschiedenis', false)
            ->assertSee('upgrade-history-select-all', false)
            ->assertSee('btn-upgrade-history-delete', false)
            ->assertSee('upgrade-history-selected-count', false);
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

        $laravel = $this->actingAs($admin)
            ->getJson(route('admin.settings.upgrade.laravel-status'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pending_finalize', false)
            ->assertJsonPath('data.docker_ready', false);

        $currentMajor = (int) explode('.', ltrim((string) Application::VERSION, 'v'))[0];
        if ($currentMajor === 12) {
            $laravel
                ->assertJsonPath('data.major_target', '13.2.0')
                ->assertJsonPath('data.incompatible_packages.0', 'laravel/tinker');
        } else {
            $laravel->assertJsonPath('data.major_target', null);
        }
    }

    #[Test]
    public function guests_cannot_access_runtime_upgrade_endpoints(): void
    {
        $this->getJson(route('admin.settings.upgrade.php-status'))
            ->assertUnauthorized();

        $this->getJson(route('admin.settings.upgrade.laravel-status'))
            ->assertUnauthorized();

        $this->getJson(route('admin.settings.upgrade.docker-status'))
            ->assertUnauthorized();
    }

    #[Test]
    public function docker_status_is_available_to_super_admin_without_compose(): void
    {
        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.settings.upgrade.docker-status'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ready', false)
            ->assertJsonPath('data.can_restart', false)
            ->assertJsonPath('data.can_rebuild', false)
            ->assertJsonPath('data.flash', null);
    }

    #[Test]
    public function docker_run_is_rejected_when_docker_is_unavailable(): void
    {
        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.settings.upgrade.docker-run'), ['action' => 'restart'])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
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

    #[Test]
    public function upgrade_history_page_shows_row_checkboxes_for_completed_logs(): void
    {
        $admin = $this->superAdmin();
        $success = $this->upgradeLog($admin, ['status' => 'success']);
        $this->upgradeLog($admin, ['status' => 'running', 'to_release' => null, 'completed_at' => null]);

        $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('upgrade-history-row-check', false)
            ->assertSee('value="'.$success->id.'"', false)
            ->assertSee('title="Upgrade is nog bezig"', false);
    }

    #[Test]
    public function super_admin_can_delete_completed_upgrade_history_rows(): void
    {
        $admin = $this->superAdmin();
        $success = $this->upgradeLog($admin, ['status' => 'success']);
        $failed = $this->upgradeLog($admin, ['status' => 'failed', 'error_message' => 'Composer conflict']);
        $running = $this->upgradeLog($admin, ['status' => 'running', 'to_release' => null, 'completed_at' => null]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.settings.upgrade.history.destroy'), [
                'ids' => [$success->id, $failed->id, $running->id],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted', 2)
            ->assertJsonPath('message', '2 regels verwijderd.');

        $this->assertDatabaseMissing('system_upgrade_logs', ['id' => $success->id]);
        $this->assertDatabaseMissing('system_upgrade_logs', ['id' => $failed->id]);
        $this->assertDatabaseHas('system_upgrade_logs', ['id' => $running->id, 'status' => 'running']);
    }

    #[Test]
    public function guests_cannot_delete_upgrade_history(): void
    {
        $this->deleteJson(route('admin.settings.upgrade.history.destroy'), ['ids' => [1]])
            ->assertUnauthorized();
    }

    #[Test]
    public function running_upgrade_history_rows_cannot_be_deleted(): void
    {
        $admin = $this->superAdmin();
        $running = $this->upgradeLog($admin, ['status' => 'running', 'to_release' => null, 'completed_at' => null]);

        $this->actingAs($admin)
            ->deleteJson(route('admin.settings.upgrade.history.destroy'), [
                'ids' => [$running->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('system_upgrade_logs', ['id' => $running->id]);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function upgradeLog(User $admin, array $overrides = []): SystemUpgradeLog
    {
        return SystemUpgradeLog::query()->create(array_merge([
            'from_release' => '1.0.0',
            'to_release' => '1.0.1',
            'status' => 'success',
            'triggered_by_user_id' => $admin->id,
            'started_at' => now(),
            'completed_at' => now(),
        ], $overrides));
    }
}

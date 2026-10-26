<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Notification;
use App\Models\SystemUpgradeLog;
use App\Models\User;
use App\Services\SystemDockerComposeService;
use App\Services\SystemPhpCriticalUpdateService;
use App\Services\SystemPhpDockerUpgradeService;
use App\Services\SystemUpgradeService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPhpCriticalUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        $this->withoutMiddleware(ValidateCsrfToken::class);
        config(['nexa.php_critical_update_check_in_tests' => true]);
    }

    #[Test]
    public function super_admin_is_notified_once_per_critical_php_update(): void
    {
        $lockMtime = filemtime(base_path('composer.lock'));
        [$security, $plainNewer, $otherMinor] = $this->fakeReleaseFeeds(true);
        $admin = $this->superAdmin();
        $otherAdmin = $this->superAdmin();
        $companyAdmin = $this->companyAdmin();

        $page = $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('Er is een kritieke PHP-update', false)
            ->assertSee($security, false)
            ->assertSee('Voorbeeldlek in het framework', false)
            ->assertSee('id="php-critical-alert" class="kt-alert kt-alert-danger mt-4', false)
            ->assertSee('Security-release uitvoeren', false)
            ->assertSee('btn-php-security-release', false)
            ->assertDontSee('Lage ernst', false)
            ->assertDontSee('Raakt deze versie niet', false);
        $this->assertDoesNotMatchRegularExpression(
            '/id="btn-php-security-release"[^>]*\bhidden\b/',
            $page->getContent()
        );

        $this->assertSame(1, $this->recordedUrlCount('www.php.net'));
        $this->assertSame(1, $this->recordedUrlCount('packagist.org/api/security-advisories'));
        $this->assertSame(2, $this->openCount($admin));
        $this->assertSame(2, $this->openCount($otherAdmin));
        $this->assertSame(0, $this->openCount($companyAdmin));
        $this->assertSame(0, SystemUpgradeLog::query()->count());
        $this->assertSame($lockMtime, filemtime(base_path('composer.lock')));

        $messages = Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->pluck('message')
            ->implode("\n");
        $this->assertStringContainsString($security, $messages);
        $this->assertStringNotContainsString($plainNewer, $messages);
        $this->assertStringNotContainsString($otherMinor, $messages);

        $this->actingAs($admin)
            ->getJson(route('admin.settings.upgrade.php-status'))
            ->assertOk()
            ->assertJsonPath('data.critical_runtime_version', $security)
            ->assertJsonCount(2, 'data.critical_updates');

        $this->assertSame(2, $this->recordedUrlCount('www.php.net'));
        $this->assertSame(2, $this->recordedUrlCount('packagist.org/api/security-advisories'));
        $this->assertSame(2, $this->openCount($admin));
        $this->assertSame(2, Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->count());
    }

    #[Test]
    public function critical_php_notification_is_hidden_when_the_update_is_gone_and_reused_when_it_returns(): void
    {
        $this->fakeReleaseFeeds(true);
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('Er is een kritieke PHP-update', false);

        $this->assertSame(2, $this->openCount($admin));

        $this->fakeReleaseFeeds(false);

        $gone = $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertDontSee('Er is een kritieke PHP-update', false)
            ->assertDontSee('Voorbeeldlek in het framework', false);
        $this->assertMatchesRegularExpression(
            '/id="btn-php-security-release"[^>]*\bhidden\b/',
            $gone->getContent()
        );

        $this->assertSame(0, $this->openCount($admin));
        $this->assertSame(2, Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->whereNotNull('archived_at')
            ->whereNotNull('read_at')
            ->count());

        $this->fakeReleaseFeeds(true);

        $this->actingAs($admin)
            ->get(route('admin.settings.upgrade.index'))
            ->assertOk()
            ->assertSee('Er is een kritieke PHP-update', false);

        $this->assertSame(2, $this->openCount($admin));
        $this->assertSame(2, Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->count());
    }

    #[Test]
    public function security_release_on_the_current_tag_enables_pull_and_recreate(): void
    {
        [$security, $tag] = $this->fakeSameMinorFeed(true);
        $root = $this->projectWithCliTag($tag);
        $compose = $this->bindReadyCompose($root);
        $admin = $this->superAdmin();

        try {
            $status = $this->actingAs($admin)
                ->getJson(route('admin.settings.upgrade.php-status'))
                ->assertOk()
                ->assertJsonPath('data.can_run', true)
                ->assertJsonPath('data.security_pull', true)
                ->assertJsonPath('data.needs_rebuild', false)
                ->assertJsonPath('data.up_to_date', false)
                ->assertJsonPath('data.latest_tag', $tag)
                ->assertJsonPath('data.dockerfile_tag', $tag)
                ->assertJsonPath('data.critical_runtime_version', $security)
                ->assertJsonPath('data.security_image', 'php:'.$security.'-cli');

            $message = (string) $status->json('data.message');
            $this->assertStringContainsString('Security-release '.$security, $message);
            $this->assertStringContainsString('via een pull van php:'.$security.'-cli', $message);
            $this->assertStringNotContainsString('De image-tag blijft php:'.$tag, $message);
            $this->assertStringNotContainsString('geen nieuwere PHP-image', $message);

            $this->actingAs($admin)
                ->postJson(route('admin.settings.upgrade.php-run'))
                ->assertStatus(500)
                ->assertJsonPath('success', false)
                ->assertJsonFragment(['message' => 'Upgrade mislukt: gestopt in test']);

            $this->assertSame("FROM php:{$security}-cli\n", file_get_contents($root.'/backend/Dockerfile'));
            $this->assertSame("FROM php:{$security}-cli\n", file_get_contents($root.'/backend/Dockerfile.prod'));
            $this->assertSame([true], $compose->pulls);
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function security_release_does_not_succeed_while_the_container_stays_older(): void
    {
        [$security, $tag] = $this->fakeSameMinorFeed(true);
        $root = $this->projectWithCliTag($tag);
        $compose = $this->bindReadyCompose($root);
        $compose->halt = false;
        $admin = $this->superAdmin();
        $service = app(SystemPhpDockerUpgradeService::class);

        try {
            $this->assertTrue($service->runtimeSatisfiesSecurityRelease($security, $security));
            $this->assertFalse($service->runtimeSatisfiesSecurityRelease(PHP_VERSION, $security));

            $failed = $this->actingAs($admin)
                ->postJson(route('admin.settings.upgrade.php-run'))
                ->assertStatus(500)
                ->assertJsonPath('success', false);

            $message = (string) $failed->json('message');
            $this->assertStringContainsString('ouder dan security-release '.$security, $message);
            $this->assertStringNotContainsString('Upgrade voltooid', $message);
            $this->assertSame("FROM php:{$security}-cli\n", file_get_contents($root.'/backend/Dockerfile'));
            $this->assertSame([true], $compose->pulls);

            $status = $this->actingAs($admin)
                ->getJson(route('admin.settings.upgrade.php-status'))
                ->assertOk()
                ->assertJsonPath('data.security_pull', true)
                ->assertJsonPath('data.critical_runtime_version', $security)
                ->assertJsonPath('data.up_to_date', false);

            $this->assertStringContainsString($security, (string) $status->json('data.message'));
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function installed_patch_clears_the_critical_alert_and_shows_the_running_version(): void
    {
        if (preg_match('/^(\d+\.\d+)\.(\d+)/', PHP_VERSION, $matches) !== 1) {
            $this->fail('PHP_VERSION is niet te herleiden tot major.minor.patch.');
        }

        $running = $matches[1].'.'.$matches[2];
        $floating = $matches[1].'-cli';
        $patchTag = $running.'-cli';
        $root = $this->projectWithCliTag($patchTag);
        $this->bindReadyCompose($root);

        Http::clearResolvedInstances();
        $this->app->forgetInstance(Factory::class);
        Http::fake([
            'www.php.net/*' => Http::response([
                $matches[1].'.0' => ['date' => '01 Jan 2026'],
                $running => ['tags' => ['security'], 'date' => '21 Oct 2026'],
            ]),
            'hub.docker.com/*' => Http::response(['name' => $floating], 200),
            'packagist.org/*' => Http::response(['advisories' => []]),
        ]);

        $admin = $this->superAdmin();

        try {
            $status = $this->actingAs($admin)
                ->getJson(route('admin.settings.upgrade.php-status'))
                ->assertOk()
                ->assertJsonPath('data.current_php', PHP_VERSION)
                ->assertJsonPath('data.dockerfile_tag', $patchTag)
                ->assertJsonPath('data.latest_tag', $floating)
                ->assertJsonPath('data.critical_runtime_version', null)
                ->assertJsonPath('data.security_pull', false)
                ->assertJsonPath('data.up_to_date', true)
                ->assertJsonPath('data.can_run', false)
                ->assertJsonPath('data.critical_updates', []);

            $message = (string) $status->json('data.message');
            $this->assertStringContainsString('PHP '.$running.' draait via php:'.$patchTag, $message);
            $this->assertStringNotContainsString('kritieke PHP-update', $message);
            $this->assertStringNotContainsString('wijkt af', $message);

            $page = $this->actingAs($admin)
                ->get(route('admin.settings.upgrade.index'))
                ->assertOk()
                ->assertDontSee('nieuwer dan de draaiende versie', false);
            $this->assertMatchesRegularExpression(
                '/id="btn-php-security-release"[^>]*\bhidden\b/',
                $page->getContent()
            );
            $this->assertMatchesRegularExpression(
                '/id="php-critical-alert"[^>]*\bhidden\b/',
                $page->getContent()
            );
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function same_tag_without_security_release_stays_disabled(): void
    {
        [, $tag] = $this->fakeSameMinorFeed(false);
        $root = $this->projectWithCliTag($tag);
        $compose = $this->bindReadyCompose($root);
        $admin = $this->superAdmin();

        try {
            $status = $this->actingAs($admin)
                ->getJson(route('admin.settings.upgrade.php-status'))
                ->assertOk()
                ->assertJsonPath('data.can_run', false)
                ->assertJsonPath('data.security_pull', false)
                ->assertJsonPath('data.up_to_date', true)
                ->assertJsonPath('data.latest_tag', $tag);

            $this->assertStringContainsString(
                'Er is geen nieuwere PHP-image.',
                (string) $status->json('data.message')
            );

            $this->actingAs($admin)
                ->postJson(route('admin.settings.upgrade.php-run'))
                ->assertStatus(422)
                ->assertJsonPath('success', false);

            $this->assertSame([], $compose->pulls);
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function security_release_compose_plan_pulls_then_force_recreates(): void
    {
        $root = $this->projectWithCliTag('8.5-cli');

        try {
            $compose = app(SystemDockerComposeService::class);
            $build = $compose->composeBuildArgs($root, true);
            $this->assertContains('build', $build);
            $this->assertContains('--pull', $build);
            $this->assertNotContains('--build', $build);

            $recreate = $compose->composeRecreateArgs($root);
            $this->assertContains('up', $recreate);
            $this->assertContains('-d', $recreate);
            $this->assertContains('--force-recreate', $recreate);
            $this->assertNotContains('--build', $recreate);
            $this->assertNotContains('--pull', $recreate);
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function bake_hint_stderr_is_success_on_exit_zero_and_a_real_error_still_fails(): void
    {
        $root = $this->projectWithCliTag('8.5-cli');

        try {
            $compose = app(SystemDockerComposeService::class);
            $php = app(SystemPhpDockerUpgradeService::class);
            $noise = "Compose now can delegate build to bake for better performances\r\n"
                ."Just set COMPOSE_BAKE=true\r\n"
                ."\x1b[1A\x1b[0G\x1b[?25l[+] Building 0.0s (0/1)                                          docker:default\n";

            $this->assertTrue($compose->composeCommandSucceeded(0, $noise));
            $this->assertNull($compose->composeFailureDetail(0, $noise));
            $this->assertFalse($compose->outputMeansImageUnavailable($noise));

            $this->assertFalse($compose->composeCommandSucceeded(1, $noise));
            $blob = (string) $compose->composeFailureDetail(1, $noise);
            $this->assertStringNotContainsString('COMPOSE_BAKE', $blob);
            $this->assertStringNotContainsString('Building 0.0s', $blob);
            $this->assertStringContainsString('exitcode 1', $blob);

            $platform = $noise."failed to solve: php:8.5.12-cli: failed to resolve source metadata for docker.io/library/php:8.5.12-cli: no match for platform in manifest: not found\n";
            $this->assertFalse($compose->composeCommandSucceeded(1, $platform));
            $detail = (string) $compose->composeFailureDetail(1, $platform);
            $this->assertStringContainsString('no match for platform', $detail);
            $this->assertStringNotContainsString('COMPOSE_BAKE', $detail);
            $this->assertStringNotContainsString('Building 0.0s', $detail);
            $this->assertTrue($compose->outputMeansImageUnavailable($platform));

            foreach ([
                'manifest unknown',
                'pull access denied for php',
                'no such image: php:8.5.12-cli',
                'Dockerfile syntax error',
            ] as $error) {
                $this->assertFalse($compose->composeCommandSucceeded(1, $noise.$error));
                $line = (string) $compose->composeFailureDetail(1, $noise.$error);
                $this->assertStringContainsString($error, $line);
                $this->assertStringNotContainsString('COMPOSE_BAKE', $line);
            }

            $this->assertFalse($compose->outputMeansImageUnavailable('pull access denied for php'));
            $this->assertSame('8.5-cli', $php->floatingCliTag('8.5.12-cli'));

            $build = $compose->composeBuildArgs($root, true);
            $progressAt = array_search('--progress', $build, true);
            $plainAt = array_search('plain', $build, true);
            $buildAt = array_search('build', $build, true);
            $this->assertNotFalse($progressAt);
            $this->assertSame($progressAt + 1, $plainAt);
            $this->assertLessThan($buildAt, $progressAt);
            $this->assertContains('--pull', $build);
            $this->assertNotContains('--build', $build);
            $this->assertContains('COMPOSE_BAKE=false', $compose->composeBuildEnv());
        } finally {
            $this->removeProjectTree($root);
        }
    }

    #[Test]
    public function company_admin_does_not_receive_critical_php_notifications(): void
    {
        $this->fakeReleaseFeeds(true);
        $this->superAdmin();
        $companyAdmin = $this->companyAdmin();

        $response = $this->actingAs($companyAdmin)->get(route('admin.settings.upgrade.index'));
        $this->assertFalse($response->isSuccessful());

        $status = $this->actingAs($companyAdmin)->getJson(route('admin.settings.upgrade.php-status'));
        $this->assertFalse($status->isSuccessful());

        $this->assertSame(0, Notification::query()
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->count());
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function fakeReleaseFeeds(bool $critical): array
    {
        if (preg_match('/^(\d+\.\d+)\.(\d+)/', PHP_VERSION, $matches) !== 1) {
            $this->fail('PHP_VERSION is niet te herleiden tot major.minor.patch.');
        }

        $security = $matches[1].'.'.((int) $matches[2] + 1);
        $plainNewer = $matches[1].'.'.((int) $matches[2] + 8);
        $otherMinor = str_starts_with(PHP_VERSION, '8.0.') ? '7.4.99' : '8.0.99';

        $releases = [
            $plainNewer => ['date' => '02 Jan 2026', 'tags' => []],
            '8.5.1' => ['date' => '2026-01-15'],
            $otherMinor => ['date' => '01 Jan 2020', 'tags' => ['security']],
        ];
        if ($critical) {
            $releases[$security] = [
                'announcement' => true,
                'tags' => ['security'],
                'date' => '24 Sep 2026',
            ];
        }

        Http::clearResolvedInstances();
        $this->app->forgetInstance(Factory::class);

        Http::fake([
            'www.php.net/*' => Http::response($releases),
            'hub.docker.com/*' => Http::response(['name' => '8.5-cli'], 200),
            'packagist.org/*' => Http::response([
                'advisories' => $critical ? [
                    'laravel/framework' => [
                        [
                            'advisoryId' => 'PKSA-kritiek-1',
                            'packageName' => 'laravel/framework',
                            'title' => 'Voorbeeldlek in het framework',
                            'severity' => 'critical',
                            'affectedVersions' => '<99.0.0',
                        ],
                        [
                            'advisoryId' => 'PKSA-laag-1',
                            'packageName' => 'laravel/framework',
                            'title' => 'Lage ernst',
                            'severity' => 'low',
                            'affectedVersions' => '<99.0.0',
                        ],
                        [
                            'advisoryId' => 'PKSA-oud-1',
                            'packageName' => 'laravel/framework',
                            'title' => 'Raakt deze versie niet',
                            'severity' => 'high',
                            'affectedVersions' => '<1.0.0',
                        ],
                    ],
                    'not/installed' => [
                        [
                            'advisoryId' => 'PKSA-vreemd-1',
                            'severity' => 'critical',
                            'title' => 'Niet geïnstalleerd',
                            'affectedVersions' => '<99.0.0',
                        ],
                    ],
                ] : [],
            ]),
        ]);

        return [$security, $plainNewer, $otherMinor];
    }

    private function recordedUrlCount(string $needle): int
    {
        return collect(Http::recorded())
            ->filter(function (array $pair) use ($needle): bool {
                return str_contains($pair[0]->url(), $needle);
            })
            ->count();
    }

    private function openCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('type', SystemPhpCriticalUpdateService::TYPE)
            ->whereNull('archived_at')
            ->whereNull('read_at')
            ->count();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function fakeSameMinorFeed(bool $critical): array
    {
        if (preg_match('/^(\d+\.\d+)\.(\d+)/', PHP_VERSION, $matches) !== 1) {
            $this->fail('PHP_VERSION is niet te herleiden tot major.minor.patch.');
        }

        $minor = $matches[1];
        $security = $minor.'.'.((int) $matches[2] + 1);
        $tag = $minor.'-cli';
        $releases = [
            $minor.'.0' => ['date' => '01 Jan 2026'],
        ];
        if ($critical) {
            $releases[$security] = [
                'tags' => ['security'],
                'date' => '21 Oct 2026',
            ];
        }

        Http::clearResolvedInstances();
        $this->app->forgetInstance(Factory::class);
        Http::fake([
            'www.php.net/*' => Http::response($releases),
            'hub.docker.com/*' => Http::response(['name' => $tag], 200),
            'packagist.org/*' => Http::response(['advisories' => []]),
        ]);

        return [$security, $tag];
    }

    private function projectWithCliTag(string $tag): string
    {
        $root = sys_get_temp_dir().'/nexa-php-sec-'.uniqid();
        mkdir($root.'/backend', 0777, true);
        $docker = "FROM php:{$tag}\n";
        file_put_contents($root.'/backend/Dockerfile', $docker);
        file_put_contents($root.'/backend/Dockerfile.prod', $docker);
        file_put_contents($root.'/docker-compose.yml', "services:\n  backend:\n    build: ./backend\n");

        return $root;
    }

    private function bindReadyCompose(string $root): SystemDockerComposeService
    {
        $compose = new class(app(SystemUpgradeService::class)) extends SystemDockerComposeService
        {
            public ?string $projectDir = null;

            /** @var list<bool> */
            public array $pulls = [];

            public bool $halt = true;

            public function ready(): bool
            {
                return true;
            }

            public function dockerSocketAvailable(): bool
            {
                return true;
            }

            public function hostProjectDir(): ?string
            {
                return $this->projectDir;
            }

            public function pending(string $kind): array
            {
                return [];
            }

            public function storePending(string $kind, array $payload): void {}

            public function clearPending(): void {}

            public function recreateStack(?callable $emit, array &$steps, bool $pull = false, ?string $minimumPhp = null): void
            {
                $this->pulls[] = $pull;
                if ($this->halt) {
                    throw new \RuntimeException('gestopt in test');
                }
            }
        };
        $compose->projectDir = $root;
        $this->app->forgetInstance(SystemPhpDockerUpgradeService::class);
        $this->app->instance(SystemDockerComposeService::class, $compose);

        return $compose;
    }

    private function removeProjectTree(string $root): void
    {
        @unlink($root.'/backend/Dockerfile');
        @unlink($root.'/backend/Dockerfile.prod');
        @unlink($root.'/docker-compose.yml');
        @rmdir($root.'/backend');
        @rmdir($root);
    }

    private function superAdmin(): User
    {
        $admin = User::factory()->create(['company_id' => null]);
        $admin->assignRole('super-admin');

        return $admin;
    }

    private function companyAdmin(): User
    {
        $company = Company::query()->create([
            'name' => 'Geen PHP-melding '.uniqid(),
            'is_active' => true,
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');

        return $user;
    }
}

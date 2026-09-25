<?php

namespace Tests\Unit;

use App\Services\SystemLaravelPackageCompatibility;
use App\Services\SystemLaravelUpgradeService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemLaravelPackageCompatibilityTest extends TestCase
{
    #[Test]
    public function caret_and_or_constraints_detect_laravel_majors(): void
    {
        $service = app(SystemLaravelPackageCompatibility::class);

        $this->assertTrue($service->constraintAllowsMajor('^13.0', 13));
        $this->assertFalse($service->constraintAllowsMajor('^12.0', 13));
        $this->assertTrue($service->constraintAllowsMajor('^11.0|^12.0|^13.0', 13));
        $this->assertFalse($service->constraintAllowsMajor('^6.0|^7.0|^8.0|^9.0|^10.0|^11.0|^12.0', 13));
        $this->assertTrue($service->constraintAllowsMajor('>=12.0', 13));
        $this->assertFalse($service->constraintAllowsMajor('>=14.0.0', 13));
        $this->assertTrue($service->constraintAllowsMajor('<14.0.0', 13));
        $this->assertFalse($service->constraintAllowsMajor('<11.48.0', 13));
    }

    #[Test]
    public function illuminate_require_without_13_marks_a_release_incompatible(): void
    {
        $service = app(SystemLaravelPackageCompatibility::class);

        $this->assertFalse($service->releaseSupportsLaravelMajor([
            'require' => ['illuminate/contracts' => '^6.0|^12.0'],
        ], 13));
        $this->assertTrue($service->releaseSupportsLaravelMajor([
            'require' => ['illuminate/contracts' => '^8.0|^9.0|^10.0|^11.0|^12.0|^13.0'],
        ], 13));
        $this->assertTrue($service->releaseSupportsLaravelMajor([
            'require' => ['php' => '^8.3'],
        ], 13));
        $this->assertTrue($service->releaseSupportsLaravelMajor([
            'conflict' => ['laravel/framework' => '<11.48.0 || >=14.0.0'],
        ], 13));
        $this->assertFalse($service->releaseSupportsLaravelMajor([
            'conflict' => ['laravel/framework' => '>=13.0'],
        ], 13));
    }

    #[Test]
    public function current_lock_marks_tinker_incompatible_with_laravel_13(): void
    {
        $incompatible = app(SystemLaravelPackageCompatibility::class)->incompatibleRootPackages(
            13,
            $this->laravel12ComposerJson(),
            $this->laravel12LockJson(),
        );

        $this->assertArrayHasKey('laravel/tinker', $incompatible);
        $this->assertSame('^2.10.1', $incompatible['laravel/tinker']['constraint']);
    }

    #[Test]
    public function already_compatible_tinker_is_not_marked_incompatible(): void
    {
        $incompatible = app(SystemLaravelPackageCompatibility::class)->incompatibleRootPackages(
            13,
            $this->laravel13ComposerJson(),
            $this->laravel13LockJson(),
        );

        $this->assertArrayNotHasKey('laravel/tinker', $incompatible);
    }

    #[Test]
    public function plan_upgrades_tinker_before_laravel_when_a_compatible_release_exists(): void
    {
        Http::fake([
            'repo.packagist.org/p2/laravel/tinker.json' => Http::response([
                'packages' => [
                    'laravel/tinker' => [
                        [
                            'version' => 'v3.0.2',
                            'require' => [
                                'illuminate/console' => '^8.0|^9.0|^10.0|^11.0|^12.0|^13.0',
                            ],
                        ],
                        [
                            'version' => 'v2.11.1',
                            'require' => [
                                'illuminate/console' => '^6.0|^7.0|^8.0|^9.0|^10.0|^11.0|^12.0',
                            ],
                        ],
                    ],
                ],
            ]),
            'repo.packagist.org/p2/phpunit/phpunit.json' => Http::response([
                'packages' => [
                    'phpunit/phpunit' => [
                        ['version' => '12.5.35', 'require' => ['php' => '^8.3']],
                    ],
                ],
            ]),
        ]);

        $plan = app(SystemLaravelPackageCompatibility::class)->planForMajor(
            13,
            12,
            app(SystemLaravelUpgradeService::class)->companionConstraintsForMajor(13),
            $this->laravel12ComposerJson(),
            $this->laravel12LockJson(),
        );

        $this->assertSame([], $plan['blockers']);
        $this->assertSame([], $plan['joint_upgrades']);
        $pre = array_column($plan['pre_upgrades'], 'package');
        $this->assertContains('laravel/tinker', $pre);
        $tinker = collect($plan['pre_upgrades'])->firstWhere('package', 'laravel/tinker');
        $this->assertSame('^3.0', $tinker['new_constraint']);
        $this->assertContains('phpunit/phpunit', $pre);
    }

    #[Test]
    public function plan_blocks_when_no_compatible_package_release_exists(): void
    {
        $composerJson = json_encode([
            'require' => [
                'php' => '^8.2',
                'laravel/framework' => '^12.0',
                'acme/legacy' => '^1.0',
            ],
        ], JSON_THROW_ON_ERROR);
        $lockJson = json_encode([
            'packages' => [[
                'name' => 'acme/legacy',
                'version' => 'v1.2.0',
                'require' => ['laravel/framework' => '^12.0'],
            ]],
            'packages-dev' => [],
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'repo.packagist.org/p2/acme/legacy.json' => Http::response([
                'packages' => [
                    'acme/legacy' => [[
                        'version' => 'v1.2.0',
                        'require' => ['laravel/framework' => '^12.0'],
                    ]],
                ],
            ]),
        ]);

        $plan = app(SystemLaravelPackageCompatibility::class)->planForMajor(
            13,
            12,
            [],
            $composerJson,
            $lockJson,
        );

        $this->assertCount(1, $plan['blockers']);
        $this->assertSame('acme/legacy', $plan['blockers'][0]['package']);
        $this->assertStringContainsString('geen stabiele versie', $plan['blockers'][0]['message']);
    }

    #[Test]
    public function joint_upgrade_is_used_when_the_compatible_release_does_not_support_current_laravel(): void
    {
        $composerJson = json_encode([
            'require' => [
                'laravel/framework' => '^12.0',
                'acme/bridge' => '^1.0',
            ],
        ], JSON_THROW_ON_ERROR);
        $lockJson = json_encode([
            'packages' => [[
                'name' => 'acme/bridge',
                'version' => 'v1.0.0',
                'require' => ['laravel/framework' => '^12.0'],
            ]],
        ], JSON_THROW_ON_ERROR);

        Http::fake([
            'repo.packagist.org/p2/acme/bridge.json' => Http::response([
                'packages' => [
                    'acme/bridge' => [
                        [
                            'version' => 'v2.0.0',
                            'require' => ['laravel/framework' => '^13.0'],
                        ],
                        [
                            'version' => 'v1.0.0',
                            'require' => ['laravel/framework' => '^12.0'],
                        ],
                    ],
                ],
            ]),
        ]);

        $plan = app(SystemLaravelPackageCompatibility::class)->planForMajor(
            13,
            12,
            [],
            $composerJson,
            $lockJson,
        );

        $this->assertSame([], $plan['blockers']);
        $this->assertSame([], $plan['pre_upgrades']);
        $this->assertSame('acme/bridge', $plan['joint_upgrades'][0]['package']);
        $this->assertSame('^2.0', $plan['joint_upgrades'][0]['new_constraint']);
    }

    private function laravel12ComposerJson(): string
    {
        return json_encode([
            'require' => [
                'php' => '^8.2',
                'laravel/framework' => '^12.0',
                'laravel/tinker' => '^2.10.1',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^11.5.3',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function laravel12LockJson(): string
    {
        return json_encode([
            'packages' => [[
                'name' => 'laravel/tinker',
                'version' => 'v2.11.1',
                'require' => [
                    'illuminate/console' => '^6.0|^7.0|^8.0|^9.0|^10.0|^11.0|^12.0',
                ],
            ]],
            'packages-dev' => [[
                'name' => 'phpunit/phpunit',
                'version' => '11.5.56',
                'require' => ['php' => '^8.2'],
            ]],
        ], JSON_THROW_ON_ERROR);
    }

    private function laravel13ComposerJson(): string
    {
        return json_encode([
            'require' => [
                'php' => '^8.3',
                'laravel/framework' => '^13.0',
                'laravel/tinker' => '^3.0',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^12.0',
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function laravel13LockJson(): string
    {
        return json_encode([
            'packages' => [[
                'name' => 'laravel/tinker',
                'version' => 'v3.0.2',
                'require' => [
                    'illuminate/console' => '^8.0|^9.0|^10.0|^11.0|^12.0|^13.0',
                ],
            ]],
            'packages-dev' => [[
                'name' => 'phpunit/phpunit',
                'version' => '12.5.35',
                'require' => ['php' => '^8.3'],
            ]],
        ], JSON_THROW_ON_ERROR);
    }
}

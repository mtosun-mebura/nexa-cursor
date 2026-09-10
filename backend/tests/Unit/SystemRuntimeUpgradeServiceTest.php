<?php

namespace Tests\Unit;

use App\Services\SystemLaravelUpgradeService;
use App\Services\SystemPhpDockerUpgradeService;
use App\Services\SystemDockerComposeService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemRuntimeUpgradeServiceTest extends TestCase
{
    #[Test]
    public function php_parses_and_rewrites_dockerfile_from_tag(): void
    {
        $service = app(SystemPhpDockerUpgradeService::class);

        $this->assertSame('8.3-cli', $service->parseFromTag("FROM php:8.3-cli\nRUN echo hi\n"));
        $this->assertSame(
            "FROM php:8.5-cli\nRUN echo hi\n",
            $service->rewriteDockerfileFrom("FROM php:8.3-cli\nRUN echo hi\n", '8.5-cli')
        );
    }

    #[Test]
    public function php_picks_latest_stable_minor_from_php_net_keys(): void
    {
        $service = app(SystemPhpDockerUpgradeService::class);

        $this->assertSame('8.5', $service->pickLatestStableMinor(['8.5.3', '8.4.12', '8.3.24', '8.1.99']));
        $this->assertNull($service->pickLatestStableMinor(['8.1.2', '7.4.33']));
    }

    #[Test]
    public function php_rewrites_dockerfiles_in_configured_project_root(): void
    {
        $root = sys_get_temp_dir().'/nexa-php-up-'.uniqid();
        mkdir($root.'/backend', 0777, true);
        file_put_contents($root.'/backend/Dockerfile', "FROM php:8.3-cli\n");
        file_put_contents($root.'/backend/Dockerfile.prod', "FROM php:8.3-cli\nWORKDIR /app\n");
        config(['nexa.host_project_dir' => $root]);

        $service = app(SystemPhpDockerUpgradeService::class);
        $service->rewriteDockerfiles('8.5-cli');

        $this->assertSame("FROM php:8.5-cli\n", file_get_contents($root.'/backend/Dockerfile'));
        $this->assertStringStartsWith('FROM php:8.5-cli', (string) file_get_contents($root.'/backend/Dockerfile.prod'));

        $service->rewriteDockerfiles('8.5-cli');
        $this->assertSame("FROM php:8.5-cli\n", file_get_contents($root.'/backend/Dockerfile'));
    }

    #[Test]
    public function compose_binary_is_copied_off_the_source_path(): void
    {
        $source = sys_get_temp_dir().'/nexa-compose-src-'.uniqid();
        $dest = sys_get_temp_dir().'/nexa-compose-dst-'.uniqid();
        file_put_contents($source, "\x7fELF".str_repeat('A', 1200));
        chmod($source, 0755);
        config(['nexa.docker_compose_local_bin' => $dest]);

        $path = app(SystemDockerComposeService::class)->materializeStandaloneComposeBinary($source);

        $this->assertSame($dest, $path);
        $this->assertNotSame($source, $path);
        $this->assertFileExists($path);
        $this->assertSame(filesize($source), filesize($path));
        $this->assertTrue(is_executable($path));

        @unlink($source);
        @unlink($dest);
    }

    #[Test]
    public function php_resolves_latest_cli_tag_from_php_net_and_docker_hub(): void
    {
        Http::fake([
            'www.php.net/*' => Http::response([
                '8.5.1' => ['date' => '2026-01-15'],
                '8.4.10' => ['date' => '2026-01-10'],
            ]),
            'hub.docker.com/*' => Http::response(['name' => '8.5-cli'], 200),
        ]);

        $service = app(SystemPhpDockerUpgradeService::class);

        $this->assertSame('8.5-cli', $service->resolveLatestCliTag());
    }

    #[Test]
    public function laravel_replaces_composer_constraints(): void
    {
        $service = app(SystemLaravelUpgradeService::class);
        $json = <<<'JSON'
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0"
    }
}
JSON;

        $updated = $service->replaceRequireConstraint($json, 'laravel/framework', '^13.0');
        $updated = $service->replaceRequireConstraint($updated, 'php', '^8.3');

        $this->assertStringContainsString('"laravel/framework": "^13.0"', $updated);
        $this->assertStringContainsString('"php": "^8.3"', $updated);
    }

    #[Test]
    public function laravel_picks_latest_version_in_major_from_packagist_payload(): void
    {
        $service = app(SystemLaravelUpgradeService::class);
        $packages = [
            ['version' => 'v13.1.0'],
            ['version' => 'v12.70.0'],
            ['version' => 'v12.69.1'],
            ['version' => '13.0.0-RC1'],
        ];

        $this->assertSame('12.70.0', $service->latestInMajor($packages, 12));
        $this->assertSame('13.1.0', $service->latestInMajor($packages, 13));
        $this->assertNull($service->latestInMajor($packages, 14));
    }

    #[Test]
    public function php_status_is_disabled_during_tests_so_compose_is_never_triggered(): void
    {
        Http::fake([
            'www.php.net/*' => Http::response(['8.5.1' => ['date' => '2026-01-15']]),
            'hub.docker.com/*' => Http::response(['name' => '8.5-cli'], 200),
        ]);

        $status = app(SystemPhpDockerUpgradeService::class)->status();

        $this->assertFalse($status['docker_ready']);
        $this->assertFalse($status['can_run']);
        $this->assertSame('8.5-cli', $status['latest_tag']);
        $this->assertArrayHasKey('up_to_date', $status);
    }

    #[Test]
    public function php_runtime_matches_cli_tag_for_current_minor(): void
    {
        $service = app(SystemPhpDockerUpgradeService::class);
        $parts = explode('.', PHP_VERSION);

        $this->assertTrue($service->runtimeMatchesCliTag($parts[0].'.'.$parts[1].'-cli'));
        $this->assertFalse($service->runtimeMatchesCliTag('7.4-cli'));
    }

    #[Test]
    public function compose_up_rebuilds_the_whole_stack_without_pinning_one_service(): void
    {
        $root = sys_get_temp_dir().'/nexa-compose-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/docker-compose.yml', "services:\n  backend:\n    image: php:8.3-cli\n");

        $command = app(SystemDockerComposeService::class)->composeUpCommand($root, true);

        $this->assertContains('up', $command);
        $this->assertContains('-d', $command);
        $this->assertContains('--build', $command);
        $this->assertSame('--build', $command[array_key_last($command)]);
        $this->assertNotContains('backend', $command);
        $this->assertSame('compose', app(SystemDockerComposeService::class)->composeCliArgs($root, true)[0]);
    }
}

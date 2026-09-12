<?php

namespace Tests\Unit;

use App\Services\SystemDockerComposeService;
use App\Services\SystemLaravelUpgradeService;
use App\Services\SystemPhpDockerUpgradeService;
use App\Services\SystemPostgresDockerUpgradeService;
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
    public function laravel_major_bumps_companion_constraints_from_the_upgrade_guide(): void
    {
        $service = app(SystemLaravelUpgradeService::class);
        $json = <<<'JSON'
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "laravel/tinker": "^2.10.1"
    },
    "require-dev": {
        "phpunit/phpunit": "^11.5.3"
    }
}
JSON;

        $updated = $service->applyMajorComposerConstraints($json, 13);

        $this->assertStringContainsString('"laravel/framework": "^13.0"', $updated);
        $this->assertStringContainsString('"laravel/tinker": "^3.0"', $updated);
        $this->assertStringContainsString('"phpunit/phpunit": "^12.0"', $updated);
        $this->assertStringContainsString('"php": "^8.2"', $updated);
        $this->assertArrayHasKey('laravel/tinker', $service->companionConstraintsForMajor(13));
        $this->assertSame([], $service->companionConstraintsForMajor(14));
    }

    #[Test]
    public function laravel_major_composer_update_includes_present_companions(): void
    {
        $service = app(SystemLaravelUpgradeService::class);
        $command = $service->composerUpdateCommandForMajor(13);

        $this->assertSame('composer', $command[0]);
        $this->assertSame('update', $command[1]);
        $this->assertContains('laravel/framework', $command);
        $this->assertContains('laravel/tinker', $command);
        $this->assertContains('phpunit/phpunit', $command);
        $this->assertNotContains('laravel/boost', $command);
        $this->assertContains('--with-all-dependencies', $command);
        $this->assertContains('--no-progress', $command);
        $this->assertContains('--no-scripts', $command);
        $this->assertNotContains('--dry-run', $command);

        $dryRun = $service->composerUpdateCommand(['laravel/framework'], dryRun: true);
        $this->assertContains('--dry-run', $dryRun);
        $this->assertContains('--no-scripts', $dryRun);
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

    #[Test]
    public function compose_restart_does_not_rebuild_images(): void
    {
        $root = sys_get_temp_dir().'/nexa-compose-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/docker-compose.yml', "services:\n  backend:\n    image: php:8.3-cli\n");

        $command = app(SystemDockerComposeService::class)->composeRestartCommand($root);

        $this->assertContains('restart', $command);
        $this->assertNotContains('up', $command);
        $this->assertNotContains('--build', $command);
        $this->assertNotContains('backend', $command);
    }

    #[Test]
    public function compose_restart_can_target_selected_services(): void
    {
        $root = sys_get_temp_dir().'/nexa-compose-'.uniqid();
        mkdir($root, 0777, true);
        file_put_contents($root.'/docker-compose.yml', "services:\n  backend:\n    image: php:8.3-cli\n");

        $command = app(SystemDockerComposeService::class)->composeRestartCommand($root, ['backend', 'db']);

        $this->assertContains('restart', $command);
        $this->assertContains('backend', $command);
        $this->assertContains('db', $command);
        $this->assertNotContains('--build', $command);
        $this->assertSame('db', $command[array_key_last($command)]);
    }

    #[Test]
    public function restart_flash_names_selected_services(): void
    {
        $service = app(SystemDockerComposeService::class);

        $this->assertSame('Docker-containers zijn herstart.', $service->restartFlash('restart', []));
        $this->assertSame('Docker-container backend is herstart.', $service->restartFlash('restart', ['backend']));
        $this->assertSame('Docker-containers backend, db zijn herstart.', $service->restartFlash('restart', ['backend', 'db']));
        $this->assertSame('Docker-stack is opnieuw gebouwd en herstart.', $service->restartFlash('rebuild', ['backend']));
    }

    #[Test]
    public function docker_exec_command_is_split_without_a_shell(): void
    {
        $service = app(SystemDockerComposeService::class);

        $this->assertSame(['php', '-v'], $service->parseExecCommand('php -v'));
        $this->assertSame(['psql', '--version'], $service->parseExecCommand('  psql --version  '));
        $this->assertSame(['php', 'artisan', 'about'], $service->parseExecCommand('php artisan about'));
        $this->assertSame(['echo', 'hello world'], $service->parseExecCommand('echo "hello world"'));
    }

    #[Test]
    public function docker_exec_command_rejects_empty_or_unbalanced_quotes(): void
    {
        $service = app(SystemDockerComposeService::class);

        try {
            $service->parseExecCommand('   ');
            $this->fail('Leeg commando moet falen.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Voer een commando in', $e->getMessage());
        }

        try {
            $service->parseExecCommand('echo "hello');
            $this->fail('Onafgesloten quote moet falen.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('aanhalingsteken', $e->getMessage());
        }
    }

    #[Test]
    public function postgres_parses_and_rewrites_compose_image_and_volume(): void
    {
        $service = app(SystemPostgresDockerUpgradeService::class);
        $compose = <<<'YAML'
services:
  db:
    image: pgvector/pgvector:pg16
volumes:
  nexa_postgres_data:
    name: ${COMPOSE_PROJECT_NAME:-nexa}_postgres_data
YAML;

        $this->assertSame('pg16', $service->parseImageTag($compose));
        $updated = $service->rewriteVolumeNameForMajor($service->rewriteImageTag($compose, 'pg17'), 17);
        $this->assertStringContainsString('image: pgvector/pgvector:pg17', $updated);
        $this->assertStringContainsString('_postgres_data_pg17', $updated);
        $this->assertStringNotContainsString('image: pgvector/pgvector:pg16', $updated);
    }

    #[Test]
    public function postgres_picks_the_next_available_major_tag(): void
    {
        $service = app(SystemPostgresDockerUpgradeService::class);

        $this->assertSame('pg17', $service->pickNextMajorTag(['pg15', 'pg16', 'pg17', 'pg18'], 16));
        $this->assertSame('pg18', $service->pickNextMajorTag(['pg16', 'pg18'], 16));
        $this->assertNull($service->pickNextMajorTag(['pg15', 'pg16'], 16));
    }

    #[Test]
    public function postgres_rewrites_compose_files_in_configured_project_root(): void
    {
        $root = sys_get_temp_dir().'/nexa-pg-up-'.uniqid();
        mkdir($root, 0777, true);
        $yml = <<<'YAML'
services:
  db:
    image: pgvector/pgvector:pg16
volumes:
  nexa_postgres_data:
    name: ${COMPOSE_PROJECT_NAME:-nexa}_postgres_data
YAML;
        file_put_contents($root.'/docker-compose.postgres.yml', $yml);
        file_put_contents($root.'/docker-compose.deploy.yml', $yml);
        config(['nexa.host_project_dir' => $root]);

        $service = app(SystemPostgresDockerUpgradeService::class);
        $service->rewriteComposeFiles('pg17', 17);

        $postgres = (string) file_get_contents($root.'/docker-compose.postgres.yml');
        $deploy = (string) file_get_contents($root.'/docker-compose.deploy.yml');
        $this->assertStringContainsString('pgvector/pgvector:pg17', $postgres);
        $this->assertStringContainsString('_postgres_data_pg17', $deploy);

        $snapshots = [$root.'/docker-compose.postgres.yml' => $yml];
        $service->restoreComposeSnapshots($snapshots);
        $this->assertStringContainsString('pgvector/pgvector:pg16', (string) file_get_contents($root.'/docker-compose.postgres.yml'));
    }

    #[Test]
    public function docker_flash_is_consumed_only_for_docker_pending(): void
    {
        $service = app(SystemDockerComposeService::class);

        try {
            $service->storePending(SystemDockerComposeService::KIND_LARAVEL, ['flash' => 'niet tonen']);
            $this->assertNull($service->consumeDockerFlash());
            $this->assertSame('laravel', $service->pending(SystemDockerComposeService::KIND_LARAVEL)['kind']);

            $service->storePending(SystemDockerComposeService::KIND_DOCKER, ['flash' => 'Docker-containers zijn herstart.']);
            $this->assertSame('Docker-containers zijn herstart.', $service->consumeDockerFlash());
            $this->assertSame([], $service->pending(SystemDockerComposeService::KIND_DOCKER));
        } finally {
            $service->clearPending();
        }
    }

    #[Test]
    public function docker_container_rows_are_normalized_from_engine_payload(): void
    {
        $rows = app(SystemDockerComposeService::class)->normalizeProjectContainers([
            [
                'Id' => 'def456',
                'Names' => ['/nexa_frontend'],
                'Image' => 'nexa-frontend:latest',
                'State' => 'running',
                'Status' => 'Up 2 hours',
                'Labels' => ['com.docker.compose.service' => 'frontend'],
            ],
            [
                'Id' => 'abc123',
                'Names' => ['/nexa_backend'],
                'Image' => 'nexa-backend:latest',
                'State' => 'running',
                'Status' => 'Up 2 hours',
                'Labels' => ['com.docker.compose.service' => 'backend'],
            ],
        ]);

        $this->assertSame('backend', $rows[0]['service']);
        $this->assertSame('nexa_backend', $rows[0]['name']);
        $this->assertSame('abc123', $rows[0]['id']);
        $this->assertSame('frontend', $rows[1]['service']);
    }
}

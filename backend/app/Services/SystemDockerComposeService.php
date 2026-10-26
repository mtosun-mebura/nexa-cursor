<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class SystemDockerComposeService
{
    public const KIND_PHP = 'php';

    public const KIND_LARAVEL = 'laravel';

    public const KIND_DOCKER = 'docker';

    public const KIND_POSTGRES = 'postgres';

    private const COMPOSE_VERSION = 'v2.36.2';

    private const HELPER_IMAGE = 'docker:27-cli';

    private const HELPER_CONTAINER = 'nexa-upgrade-compose';

    public function __construct(
        protected SystemUpgradeService $upgrades,
    ) {}

    public function ready(): bool
    {
        if (app()->environment('testing') && ! config('nexa.php_docker_upgrade_allow_in_tests')) {
            return false;
        }

        return $this->dockerSocketAvailable() && $this->hostProjectDir() !== null;
    }

    public function dockerSocketAvailable(): bool
    {
        return file_exists('/var/run/docker.sock');
    }

    public function hostProjectDir(): ?string
    {
        $configured = config('nexa.host_project_dir');
        if (is_string($configured) && $this->isUsableHostProjectDir($configured)) {
            return rtrim($configured, '/');
        }

        $env = env('NEXA_HOST_PROJECT_DIR');
        if (is_string($env) && $this->isUsableHostProjectDir($env)) {
            return rtrim($env, '/');
        }

        $inspect = $this->selfContainerInspect();
        $labels = is_array($inspect['Config']['Labels'] ?? null) ? $inspect['Config']['Labels'] : [];
        $workingDir = $labels['com.docker.compose.project.working_dir'] ?? null;
        if (is_string($workingDir) && $this->isUsableHostProjectDir($workingDir)) {
            return rtrim($workingDir, '/');
        }

        if (is_file(base_path('Dockerfile')) && is_file(base_path('../docker-compose.yml'))) {
            $dir = realpath(base_path('..'));

            return is_string($dir) ? $dir : null;
        }

        return null;
    }

    /**
     * Host-pad mag ontbreken in déze container: Coolify mount de repo niet op hetzelfde pad.
     * De helper-container bind-mount het pad via docker.sock.
     */
    private function isUsableHostProjectDir(string $path): bool
    {
        $path = trim($path);
        if ($path === '') {
            return false;
        }

        return is_dir($path) || $this->dockerSocketAvailable();
    }

    /**
     * Bouwt images waarvan de context is veranderd en start de hele compose-stack opnieuw.
     * Bij een security-release eerst `build --pull` (Dockerfile wijst dan op php:X.Y.Z-cli), daarna force-recreate.
     * $minimumPhp wordt in de gebouwde image gecontroleerd vóór het herstarten.
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function recreateStack(?callable $emit, array &$steps, bool $pull = false, ?string $minimumPhp = null): void
    {
        $hostDir = $this->requireHostProjectDir();
        if ($pull) {
            $this->runInHelperContainer(
                $emit,
                $steps,
                $hostDir,
                $this->composeBuildArgs($hostDir, true),
                'PHP-image pullen',
                1800,
                true,
                $this->composeBuildEnv(),
                false,
            );
            if (is_string($minimumPhp) && $minimumPhp !== '') {
                $this->assertBuiltImagePhp($emit, $steps, $hostDir, $minimumPhp);
            }
            $this->runInHelperContainer(
                $emit,
                $steps,
                $hostDir,
                $this->composeRecreateArgs($hostDir),
                'Container opnieuw opzetten',
            );

            return;
        }

        $this->runInHelperContainer(
            $emit,
            $steps,
            $hostDir,
            $this->composeCliArgs($hostDir, true),
            'Docker-stack bouwen en herstarten',
        );
    }

    /**
     * Herstart bestaande containers zonder images opnieuw te bouwen.
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  list<string>  $services
     */
    public function restartStack(?callable $emit, array &$steps, array $services = []): void
    {
        $hostDir = $this->requireHostProjectDir();
        $this->runInHelperContainer(
            $emit,
            $steps,
            $hostDir,
            $this->composeRestartArgs($hostDir, $services),
            $services === []
                ? 'Docker-containers herstarten'
                : 'Docker-containers herstarten ('.implode(', ', $services).')',
            300,
        );
    }

    /**
     * @return array{
     *     ready: bool,
     *     can_restart: bool,
     *     can_rebuild: bool,
     *     containers: list<array{id: string, name: string, service: string, image: string, state: string, status: string}>,
     *     flash: string|null,
     *     message: string,
     *     can_exec: bool
     * }
     */
    public function status(): array
    {
        $ready = $this->ready();
        $flash = $this->consumeDockerFlash();
        $containers = $ready ? $this->projectContainers() : [];
        $count = count($containers);
        $message = 'Docker is in deze omgeving niet beschikbaar (geen socket of host-projectmap).';
        if ($ready) {
            $message = $count === 0
                ? 'Docker is beschikbaar, maar er zijn geen compose-containers gevonden.'
                : $count.' container'.($count === 1 ? '' : 's').' in deze stack.';
        }

        return [
            'ready' => $ready,
            'can_restart' => $ready,
            'can_rebuild' => $ready && $this->upgrades->webUpgradeEnabled(),
            'can_exec' => $ready && $count > 0,
            'containers' => $containers,
            'flash' => $flash,
            'message' => $message,
        ];
    }

    /**
     * @param  callable(array<string, mixed>): void|null  $emit
     * @param  list<string>  $services
     * @return array{success: bool, message: string, reconnect?: bool}
     */
    public function run(User $user, string $action, ?callable $emit = null, array $services = []): array
    {
        $action = $action === 'rebuild' ? 'rebuild' : 'restart';
        if (! $this->ready()) {
            throw new \RuntimeException('Docker is niet beschikbaar.');
        }
        if ($action === 'rebuild' && ! $this->upgrades->webUpgradeEnabled()) {
            throw new \RuntimeException('Web-upgrades zijn uitgeschakeld. Zet NEXA_WEB_UPGRADE_ENABLED=true in .env.');
        }

        $existing = $this->pendingState();
        $existingKind = is_string($existing['kind'] ?? null) ? $existing['kind'] : null;
        if ($existingKind !== null && $existingKind !== self::KIND_DOCKER) {
            throw new \RuntimeException('Er loopt al een upgrade. Wacht tot die is afgerond.');
        }

        $resolved = [];
        if ($action === 'restart') {
            $resolved = $this->resolveRestartServices($services);
            if ($services !== [] && $resolved === []) {
                throw new \RuntimeException('Geen geldige Docker-services geselecteerd om te herstarten.');
            }
        }

        $flash = $this->restartFlash($action, $resolved);
        $steps = [];
        $this->storePending(self::KIND_DOCKER, [
            'action' => $action,
            'flash' => $flash,
            'services' => $resolved,
            'user_id' => $user->id,
        ]);

        try {
            if ($action === 'rebuild') {
                $this->recreateStack($emit, $steps);
            } else {
                $this->restartStack($emit, $steps, $resolved);
            }
        } catch (\Throwable $e) {
            $this->clearPending();
            throw $e;
        }

        return [
            'success' => true,
            'message' => $flash,
            'reconnect' => true,
        ];
    }

    /**
     * @param  list<mixed>  $requested
     * @return list<string>
     */
    public function resolveRestartServices(array $requested): array
    {
        if ($requested === []) {
            return [];
        }

        $known = [];
        foreach ($this->projectContainers() as $row) {
            $service = trim((string) ($row['service'] ?? ''));
            if ($service !== '' && $service !== '—') {
                $known[$service] = true;
            }
        }

        $resolved = [];
        foreach ($requested as $name) {
            if (! is_string($name)) {
                continue;
            }
            $name = trim($name);
            if ($name !== '' && isset($known[$name]) && ! in_array($name, $resolved, true)) {
                $resolved[] = $name;
            }
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $services
     */
    public function restartFlash(string $action, array $services): string
    {
        if ($action === 'rebuild') {
            return 'Docker-stack is opnieuw gebouwd en herstart.';
        }
        if ($services === []) {
            return 'Docker-containers zijn herstart.';
        }
        if (count($services) === 1) {
            return 'Docker-container '.$services[0].' is herstart.';
        }

        return 'Docker-containers '.implode(', ', $services).' zijn herstart.';
    }

    /**
     * @return list<string>
     */
    public function parseExecCommand(string $command): array
    {
        $command = trim($command);
        if ($command === '') {
            throw new \InvalidArgumentException('Voer een commando in.');
        }
        if (strlen($command) > 4000) {
            throw new \InvalidArgumentException('Commando is te lang (max. 4000 tekens).');
        }
        if (preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', $command) === 1) {
            throw new \InvalidArgumentException('Commando bevat ongeldige tekens.');
        }

        $argv = [];
        $current = '';
        $quote = null;
        $length = strlen($command);
        for ($i = 0; $i < $length; $i++) {
            $ch = $command[$i];
            if ($quote !== null) {
                if ($ch === '\\' && $i + 1 < $length) {
                    $current .= $command[$i + 1];
                    $i++;

                    continue;
                }
                if ($ch === $quote) {
                    $quote = null;

                    continue;
                }
                $current .= $ch;

                continue;
            }
            if ($ch === '"' || $ch === "'") {
                $quote = $ch;

                continue;
            }
            if ($ch === ' ' || $ch === "\t") {
                if ($current !== '') {
                    $argv[] = $current;
                    $current = '';
                }

                continue;
            }
            $current .= $ch;
        }
        if ($quote !== null) {
            throw new \InvalidArgumentException('Onafgesloten aanhalingsteken in commando.');
        }
        if ($current !== '') {
            $argv[] = $current;
        }
        if ($argv === []) {
            throw new \InvalidArgumentException('Voer een commando in.');
        }
        if (count($argv) > 40) {
            throw new \InvalidArgumentException('Commando heeft te veel argumenten.');
        }

        return $argv;
    }

    /**
     * @param  list<string>|null  $argv
     * @return array{success: bool, exit_code: int, output: string, service: string, container: string}
     */
    public function execInService(string $service, string $command, ?array $argv = null, int $timeoutSeconds = 60, ?string $sinkPath = null): array
    {
        if (! $this->ready()) {
            throw new \RuntimeException('Docker is niet beschikbaar.');
        }

        $argv = $argv ?? $this->parseExecCommand($command);
        $row = $this->containerByService($service);
        if ($row === null) {
            throw new \RuntimeException('Container "'.$service.'" hoort niet bij deze stack.');
        }
        if (strtolower((string) $row['state']) !== 'running') {
            throw new \RuntimeException('Container '.$service.' draait niet.');
        }
        $name = (string) $row['name'];
        if ($name === '' || $name === '—') {
            throw new \RuntimeException('Container-naam voor '.$service.' is onbekend.');
        }

        $cmd = array_merge(['docker', 'exec', $name], $argv);
        if ($sinkPath !== null) {
            $cmd = [
                'sh',
                '-c',
                'docker exec '.escapeshellarg($name).' '.implode(' ', array_map('escapeshellarg', $argv)).
                ' > '.escapeshellarg($sinkPath),
            ];
        }

        $steps = [];
        $result = $this->runHelperCommand(
            null,
            $steps,
            $cmd,
            'docker exec '.$service,
            $timeoutSeconds,
            false,
        );

        $output = $sinkPath !== null
            ? (is_file($sinkPath) ? 'Dump geschreven ('.filesize($sinkPath).' bytes).' : $result['output'])
            : $result['output'];

        return [
            'success' => $result['exit_code'] === 0,
            'exit_code' => $result['exit_code'],
            'output' => $this->truncateComposeOutput((string) $output, 64000),
            'service' => $service,
            'container' => $name,
        ];
    }

    /**
     * @return array{id: string, name: string, service: string, image: string, state: string, status: string}|null
     */
    public function containerByService(string $service): ?array
    {
        $service = trim($service);
        if ($service === '') {
            return null;
        }
        foreach ($this->projectContainers() as $row) {
            if ($row['service'] === $service) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function containerEnv(string $service): array
    {
        $row = $this->containerByService($service);
        $id = is_array($row) ? (string) $row['id'] : '';
        if ($id === '') {
            return [];
        }

        try {
            $inspect = $this->dockerRequest('get', '/containers/'.$id.'/json', null, 15);
        } catch (\Throwable) {
            return [];
        }
        $list = $inspect->json('Config.Env');
        if (! is_array($list)) {
            return [];
        }

        $env = [];
        foreach ($list as $line) {
            if (! is_string($line)) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $env[substr($line, 0, $pos)] = substr($line, $pos + 1);
        }

        return $env;
    }

    /**
     * @param  list<string>  $subcommand
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function runComposeSubcommand(
        ?callable $emit,
        array &$steps,
        array $subcommand,
        string $label,
        int $timeoutSeconds = 1800,
    ): void {
        $hostDir = $this->requireHostProjectDir();
        $this->runInHelperContainer(
            $emit,
            $steps,
            $hostDir,
            $this->composeSubcommandArgs($hostDir, $subcommand),
            $label,
            $timeoutSeconds,
        );
    }

    /**
     * @param  list<string>  $cmd
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function runHelperCommand(
        ?callable $emit,
        array &$steps,
        array $cmd,
        string $label,
        int $timeoutSeconds = 1800,
        bool $throwOnFailure = true,
    ): array {
        $hostDir = $this->requireHostProjectDir();

        return $this->runInHelperContainer($emit, $steps, $hostDir, $cmd, $label, $timeoutSeconds, $throwOnFailure);
    }

    public function waitForServiceHealthy(string $service, int $timeoutSeconds = 180, ?callable $emit = null): void
    {
        $deadline = time() + $timeoutSeconds;
        $attempt = 0;
        while (time() < $deadline) {
            $attempt++;
            $row = $this->containerByService($service);
            $status = is_array($row) ? strtolower((string) $row['status']) : '';
            $state = is_array($row) ? strtolower((string) $row['state']) : '';
            if ($state === 'running' && (str_contains($status, '(healthy)') || str_contains($status, 'healthy'))) {
                return;
            }
            if ($attempt === 1 || $attempt % 5 === 0) {
                $statusLabel = is_array($row) ? (string) ($row['status'] ?? 'onbekend') : 'onbekend';
                $this->emitComposeNotes($emit, $service.' niet gezond: '.$statusLabel, 'Wachten op '.$service);
            }
            sleep(2);
        }

        throw new \RuntimeException('Container '.$service.' werd niet gezond binnen '.$timeoutSeconds.' seconden.');
    }

    /**
     * @return list<array{id: string, name: string, service: string, image: string, state: string, status: string}>
     */
    public function projectContainers(): array
    {
        $inspect = $this->selfContainerInspect();
        $labels = is_array($inspect['Config']['Labels'] ?? null) ? $inspect['Config']['Labels'] : [];
        $project = (string) ($labels['com.docker.compose.project'] ?? '');
        if ($project === '') {
            return [];
        }

        $filters = rawurlencode(json_encode(['label' => ['com.docker.compose.project='.$project]], JSON_THROW_ON_ERROR));
        try {
            $response = $this->dockerRequest('get', '/containers/json?all=1&filters='.$filters, null, 10);
        } catch (\Throwable) {
            return [];
        }
        if (! $response->successful() || ! is_array($response->json())) {
            return [];
        }

        return $this->normalizeProjectContainers($response->json());
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array{id: string, name: string, service: string, image: string, state: string, status: string}>
     */
    public function normalizeProjectContainers(array $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $itemLabels = is_array($item['Labels'] ?? null) ? $item['Labels'] : [];
            $names = is_array($item['Names'] ?? null) ? $item['Names'] : [];
            $name = ltrim((string) ($names[0] ?? ''), '/');
            $service = (string) ($itemLabels['com.docker.compose.service'] ?? '');
            $rows[] = [
                'id' => (string) ($item['Id'] ?? ''),
                'name' => $name !== '' ? $name : '—',
                'service' => $service !== '' ? $service : '—',
                'image' => (string) ($item['Image'] ?? '—'),
                'state' => (string) ($item['State'] ?? '—'),
                'status' => (string) ($item['Status'] ?? '—'),
            ];
        }

        usort($rows, fn (array $a, array $b): int => strcmp($a['service'], $b['service']));

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function composeUpCommand(string $hostDir, bool $build = true): array
    {
        $args = $this->composeCliArgs($hostDir, $build);
        $binary = $this->composeCommand();
        if ($binary === ['docker', 'compose']) {
            return array_merge(['docker'], $args);
        }

        return array_merge($binary, array_slice($args, 1));
    }

    /**
     * @return list<string>
     */
    public function composeCliArgs(string $hostDir, bool $build = true): array
    {
        $sub = ['up', '-d'];
        if ($build) {
            $sub[] = '--build';
        }

        return $this->composeSubcommandArgs($hostDir, $sub);
    }

    /**
     * `docker compose build --pull` haalt de basisimage (php:8.5-cli) opnieuw op.
     * `up --build --pull` zet de pull-policy op build en pullet die basisimage niet.
     *
     * `--progress plain` is een globale vlag: de build-subcommand van Compose 2.33 kent die niet.
     * Zonder plain progress tekent een TTY één frame (`Building 0.0s (0/1)`) en verbergt de echte fout.
     *
     * @return list<string>
     */
    public function composeBuildArgs(string $hostDir, bool $pull = false): array
    {
        $sub = ['build'];
        if ($pull) {
            $sub[] = '--pull';
        }

        return $this->composeSubcommandArgs($hostDir, $sub, ['--progress', 'plain']);
    }

    /**
     * Alleen voor `build --pull`. De Bake-hint op stderr is geen fout; bake zelf blijft uit.
     *
     * @return list<string>
     */
    public function composeBuildEnv(): array
    {
        return ['COMPOSE_BAKE=false'];
    }

    /**
     * Exit 0 met alleen de Bake-hint is succes. Een echte foutregel faalt ook bij exit 0.
     */
    public function composeCommandSucceeded(int $exitCode, string $output): bool
    {
        if ($this->realComposeErrorLine($output) !== null) {
            return false;
        }

        return $exitCode === 0;
    }

    public function composeFailureDetail(int $exitCode, string $output): ?string
    {
        if ($this->composeCommandSucceeded($exitCode, $output)) {
            return null;
        }

        $error = $this->realComposeErrorLine($output);
        if ($error !== null) {
            return $error;
        }

        $clean = trim($this->sanitizeComposeOutput($output));
        if ($clean !== '') {
            return $this->truncateComposeOutput($clean, 500);
        }

        return 'docker compose stopte met exitcode '.$exitCode.'.';
    }

    public function outputMeansImageUnavailable(string $output): bool
    {
        return preg_match(
            '/manifest unknown|no such image|no match for platform|failed to resolve source metadata/i',
            $this->sanitizeComposeOutput($output)
        ) === 1;
    }

    /**
     * @return list<string>
     */
    public function composeRecreateArgs(string $hostDir): array
    {
        return $this->composeSubcommandArgs($hostDir, ['up', '-d', '--force-recreate']);
    }

    /**
     * @param  list<string>  $services
     * @return list<string>
     */
    public function composeRestartArgs(string $hostDir, array $services = []): array
    {
        return $this->composeSubcommandArgs($hostDir, array_merge(['restart'], $services));
    }

    /**
     * @param  list<string>  $services
     * @return list<string>
     */
    public function composeRestartCommand(string $hostDir, array $services = []): array
    {
        $args = $this->composeRestartArgs($hostDir, $services);
        $binary = $this->composeCommand();
        if ($binary === ['docker', 'compose']) {
            return array_merge(['docker'], $args);
        }

        return array_merge($binary, array_slice($args, 1));
    }

    /**
     * @param  list<string>  $subcommand
     * @param  list<string>  $globalFlags
     * @return list<string>
     */
    public function composeSubcommandArgs(string $hostDir, array $subcommand, array $globalFlags = []): array
    {
        $compose = $this->composeInvocation($hostDir);
        $args = array_merge(['compose'], $globalFlags);
        if ($compose['project'] !== null) {
            $args = array_merge($args, ['-p', $compose['project']]);
        }

        return array_merge($args, [
            '-f', $compose['file'],
            '--project-directory', $hostDir,
        ], $subcommand);
    }

    private function requireHostProjectDir(): string
    {
        $hostDir = $this->hostProjectDir();
        if ($hostDir === null) {
            throw new \RuntimeException('Host-projectmap is onbekend. Zet NEXA_HOST_PROJECT_DIR of mount de repo in de container.');
        }

        return $hostDir;
    }

    public function consumeDockerFlash(): ?string
    {
        $pending = $this->pending(self::KIND_DOCKER);
        if ($pending === []) {
            return null;
        }

        $flash = trim((string) ($pending['flash'] ?? ''));
        $this->clearPending();

        return $flash !== '' ? $flash : 'Docker-containers zijn herstart.';
    }

    /**
     * @return array<string, mixed>
     */
    public function pending(string $kind): array
    {
        $data = $this->pendingState();
        if (($data['kind'] ?? null) !== $kind) {
            return [];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function storePending(string $kind, array $payload): void
    {
        File::ensureDirectoryExists(dirname($this->pendingPath()));
        file_put_contents(
            $this->pendingPath(),
            json_encode(array_merge($payload, ['kind' => $kind]), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    public function clearPending(): void
    {
        if (is_file($this->pendingPath())) {
            @unlink($this->pendingPath());
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function pendingState(): array
    {
        $path = $this->pendingPath();
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return array{command: list<string>, file: string, service: string, project: string|null}
     */
    public function composeInvocation(string $hostDir): array
    {
        $inspect = $this->selfContainerInspect();
        $labels = is_array($inspect['Config']['Labels'] ?? null) ? $inspect['Config']['Labels'] : [];
        $configFiles = (string) ($labels['com.docker.compose.project.config_files'] ?? '');
        $firstFile = trim(explode(',', $configFiles)[0] ?? '');
        $service = (string) ($labels['com.docker.compose.service'] ?? 'backend');
        $project = (string) ($labels['com.docker.compose.project'] ?? '');

        $file = $firstFile !== ''
            ? $firstFile
            : (is_file($hostDir.'/docker-compose.yml')
                ? $hostDir.'/docker-compose.yml'
                : $hostDir.'/docker-compose.deploy.yml');

        if ($firstFile === '' && ! is_file($file)) {
            throw new \RuntimeException('Geen docker-compose-bestand gevonden in '.$hostDir);
        }

        return [
            'command' => $this->composeCommand(),
            'file' => $file,
            'service' => $service !== '' ? $service : 'backend',
            'project' => $project !== '' ? $project : null,
        ];
    }

    /**
     * @return list<string>
     */
    private function composeCommand(): array
    {
        if ($this->commandExists('docker')) {
            $probe = Process::fromShellCommandline('docker compose version', base_path(), null, null, 15);
            $probe->run();
            if ($probe->isSuccessful()) {
                return ['docker', 'compose'];
            }
        }

        if ($this->commandExists('docker-compose')) {
            return ['docker-compose'];
        }

        return [$this->ensureStandaloneCompose()];
    }

    private function ensureStandaloneCompose(): string
    {
        $downloaded = storage_path('app/bin/docker-compose');
        if (! $this->isSafeComposeBinary($downloaded)) {
            $this->downloadStandaloneCompose($downloaded);
        }

        return $this->materializeStandaloneComposeBinary($downloaded);
    }

    /**
     * Voer compose nooit uit vanaf een bind-mount (ETXTBSY op Docker Desktop)
     * of terwijl de HTTP-sink het bestand nog open heeft.
     */
    public function materializeStandaloneComposeBinary(string $sourcePath): string
    {
        if (! is_file($sourcePath) || filesize($sourcePath) < 1000) {
            throw new \RuntimeException('Docker Compose-binary ontbreekt of is onvolledig.');
        }

        $dest = $this->containerLocalComposePath();
        if (
            is_file($dest)
            && filesize($dest) === filesize($sourcePath)
            && is_executable($dest)
        ) {
            return $dest;
        }

        File::ensureDirectoryExists(dirname($dest));
        $tmp = $dest.'.tmp';
        if (! @copy($sourcePath, $tmp)) {
            @unlink($tmp);
            throw new \RuntimeException('Kon Docker Compose niet naar een lokale schijf kopiëren.');
        }
        chmod($tmp, 0755);
        @unlink($dest);
        if (! @rename($tmp, $dest)) {
            if (! @copy($tmp, $dest)) {
                @unlink($tmp);
                throw new \RuntimeException('Kon Docker Compose niet activeren buiten de bind-mount.');
            }
            @unlink($tmp);
            chmod($dest, 0755);
        }

        return $dest;
    }

    private function downloadStandaloneCompose(string $path): void
    {
        File::ensureDirectoryExists(dirname($path));
        $arch = php_uname('m');
        $arch = (str_contains($arch, 'aarch64') || str_contains($arch, 'arm64')) ? 'aarch64' : 'x86_64';
        $url = 'https://github.com/docker/compose/releases/download/'.self::COMPOSE_VERSION.'/docker-compose-linux-'.$arch;
        $tmp = $path.'.download';
        @unlink($tmp);

        $response = Http::timeout(120)->sink($tmp)->get($url);
        $ok = $response->successful();
        unset($response);

        if (! $ok || ! $this->isSafeComposeBinary($tmp)) {
            @unlink($tmp);
            throw new \RuntimeException('Kon Docker Compose niet downloaden. Installeer docker CLI in de image of probeer later opnieuw.');
        }

        chmod($tmp, 0755);
        @unlink($path);
        if (! @rename($tmp, $path)) {
            if (! @copy($tmp, $path)) {
                @unlink($tmp);
                throw new \RuntimeException('Kon Docker Compose niet opslaan.');
            }
            @unlink($tmp);
        }
        chmod($path, 0755);
    }

    private function containerLocalComposePath(): string
    {
        $configured = config('nexa.docker_compose_local_bin');

        return is_string($configured) && $configured !== ''
            ? $configured
            : '/tmp/nexa-docker-compose';
    }

    private function isSafeComposeBinary(string $path): bool
    {
        if (! is_file($path) || ! is_readable($path) || filesize($path) < 1000) {
            return false;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $magic = fread($handle, 4);
        fclose($handle);

        return $magic === "\x7fELF";
    }

    /**
     * @return array<string, mixed>
     */
    public function selfContainerInspect(): array
    {
        if (! $this->dockerSocketAvailable()) {
            return [];
        }

        $id = getenv('HOSTNAME') ?: (string) @file_get_contents('/etc/hostname');
        $id = trim($id);
        if ($id === '') {
            return [];
        }

        try {
            $response = $this->dockerRequest('get', '/containers/'.$id.'/json', null, 5);

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Compose/CLI draait in een aparte container, zodat het stoppen van `backend`
     * de upgrade niet afkapt (ETXTBSY/zelf-kill vanaf de bind-mount).
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     * @param  list<string>  $cmd
     * @param  list<string>  $extraEnv
     * @return array{output: string, exit_code: int}
     */
    private function runInHelperContainer(
        ?callable $emit,
        array &$steps,
        string $hostDir,
        array $cmd,
        string $label,
        int $timeoutSeconds = 1800,
        bool $throwOnFailure = true,
        array $extraEnv = [],
        ?bool $tty = null,
    ): array {
        $this->upgrades->emitProgress($emit, $steps, $label, 'running');
        $this->ensureHelperImage();
        $this->dockerDeleteContainer(self::HELPER_CONTAINER);

        $created = $this->dockerRequest('post', '/containers/create?name='.urlencode(self::HELPER_CONTAINER), [
            'Image' => self::HELPER_IMAGE,
            'Cmd' => $cmd,
            'WorkingDir' => $hostDir,
            'Tty' => $tty ?? true,
            'Env' => array_merge([
                'DOCKER_HOST=unix:///var/run/docker.sock',
                'PWD='.$hostDir,
                'COMPOSE_PROJECT_DIR='.$hostDir,
            ], $extraEnv),
            'HostConfig' => [
                'Binds' => [
                    '/var/run/docker.sock:/var/run/docker.sock',
                    $hostDir.':'.$hostDir,
                ],
                'AutoRemove' => false,
            ],
        ], 30);

        $id = (string) ($created->json('Id') ?? '');
        if (! $created->successful() || $id === '') {
            throw new \RuntimeException('Kon de Docker-upgrade-helper niet starten: '.$this->dockerError($created));
        }

        $started = $this->dockerRequest('post', '/containers/'.$id.'/start', null, 30);
        if (! $started->successful()) {
            $this->dockerDeleteContainer(self::HELPER_CONTAINER);
            throw new \RuntimeException('Kon de Docker-upgrade-helper niet starten: '.$this->dockerError($started));
        }

        $output = '';
        $seen = 0;
        $deadline = time() + $timeoutSeconds;
        while (time() < $deadline) {
            $inspect = $this->dockerRequest('get', '/containers/'.$id.'/json', null, 15);
            $state = is_array($inspect->json('State')) ? $inspect->json('State') : [];
            $logs = $this->dockerRequest('get', '/containers/'.$id.'/logs?stdout=1&stderr=1', null, 15);
            $decoded = $this->decodeDockerLogs((string) $logs->body());
            if (strlen($decoded) > $seen) {
                $chunk = substr($decoded, $seen);
                $seen = strlen($decoded);
                $output .= $chunk;
                $this->emitComposeNotes($emit, $chunk, $label);
            }

            if (! ($state['Running'] ?? false)) {
                $exit = (int) ($state['ExitCode'] ?? 1);
                $this->dockerDeleteContainer(self::HELPER_CONTAINER);
                $detail = $this->composeFailureDetail($exit, $output);
                if ($detail !== null && $throwOnFailure) {
                    throw new \RuntimeException(trim($label.' mislukt: '.$detail));
                }

                $ok = $detail === null;
                $this->upgrades->emitProgress($emit, $steps, $label.($ok ? ' voltooid' : ' (exit '.$exit.')'), $ok ? 'done' : 'failed');

                return ['output' => $output, 'exit_code' => $exit];
            }

            sleep(2);
        }

        $this->dockerDeleteContainer(self::HELPER_CONTAINER);
        throw new \RuntimeException($label.' timeout na '.((int) ($timeoutSeconds / 60)).' minuten.');
    }

    private function ensureHelperImage(): void
    {
        $inspect = $this->dockerRequest('get', '/images/'.rawurlencode(self::HELPER_IMAGE).'/json', null, 15);
        if ($inspect->successful()) {
            return;
        }

        $pull = $this->dockerRequest(
            'post',
            '/images/create?fromImage='.urlencode('docker').'&tag='.urlencode('27-cli'),
            null,
            300,
        );
        if (! $pull->successful()) {
            throw new \RuntimeException('Kon image '.self::HELPER_IMAGE.' niet ophalen: '.$this->dockerError($pull));
        }
    }

    private function dockerDeleteContainer(string $name): void
    {
        try {
            $this->dockerRequest('delete', '/containers/'.rawurlencode($name).'?force=1', null, 20);
        } catch (\Throwable) {
            // vorige helper mag ontbreken
        }
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function dockerRequest(
        string $method,
        string $path,
        ?array $json = null,
        int $timeout = 30,
        ?string $sinkPath = null,
    ): Response {
        $request = Http::withOptions([
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock',
            ],
        ])->timeout($timeout)->connectTimeout(5);
        if ($sinkPath !== null) {
            $request = $request->sink($sinkPath);
        }

        $url = 'http://localhost/v1.41'.$path;

        return match (strtolower($method)) {
            'get' => $request->get($url),
            'delete' => $request->delete($url),
            'post' => $json === null ? $request->post($url) : $request->asJson()->post($url, $json),
            default => throw new \InvalidArgumentException('Onbekende Docker-API-methode: '.$method),
        };
    }

    private function dockerError(Response $response): string
    {
        $message = $response->json('message');
        if (is_string($message) && $message !== '') {
            return $message;
        }

        $body = trim((string) $response->body());

        return $body !== '' ? $body : ('HTTP '.$response->status());
    }

    private function decodeDockerLogs(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        $first = ord($raw[0]);
        if ($first !== 1 && $first !== 2) {
            return $raw;
        }

        $out = '';
        $offset = 0;
        $length = strlen($raw);
        while ($offset + 8 <= $length) {
            $size = unpack('N', substr($raw, $offset + 4, 4));
            $frameSize = is_array($size) ? (int) ($size[1] ?? 0) : 0;
            $offset += 8;
            if ($frameSize < 0 || $offset + $frameSize > $length) {
                break;
            }
            $out .= substr($raw, $offset, $frameSize);
            $offset += $frameSize;
        }

        return $out !== '' ? $out : $raw;
    }

    private function emitComposeNotes(?callable $emit, string $chunk, string $label = 'Docker-stack bouwen en herstarten'): void
    {
        if ($emit === null) {
            return;
        }

        foreach (preg_split("/\r\n|\n|\r/", $this->stripAnsi($chunk)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $this->isComposeNoiseLine($line)) {
                continue;
            }
            $emit(['type' => 'note', 'note' => $label.': '.$line]);
        }
    }

    /**
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function assertBuiltImagePhp(?callable $emit, array &$steps, string $hostDir, string $minimumPhp): void
    {
        $result = $this->runInHelperContainer(
            $emit,
            $steps,
            $hostDir,
            $this->composePhpVersionArgs($hostDir),
            'PHP-versie in de image controleren',
            180,
            true,
            [],
            false,
        );
        $built = $this->extractPhpVersion($result['output']);
        if ($built === null) {
            throw new \RuntimeException('Kon de PHP-versie in de gebouwde image niet lezen.');
        }
        if ($this->phpVersionSatisfies($built, $minimumPhp)) {
            return;
        }

        throw new \RuntimeException(
            'De gebouwde image draait PHP '.$built.', ouder dan security-release '.$minimumPhp.'.'
        );
    }

    /**
     * @return list<string>
     */
    private function composePhpVersionArgs(string $hostDir): array
    {
        $service = $this->composeInvocation($hostDir)['service'];

        return $this->composeSubcommandArgs($hostDir, [
            'run', '--rm', '--no-deps', '-T', '--entrypoint', 'php', $service, '-r', 'echo PHP_VERSION;',
        ]);
    }

    private function phpVersionSatisfies(string $running, string $required): bool
    {
        $running = $this->extractPhpVersion($running) ?? '';
        $required = $this->extractPhpVersion($required) ?? '';
        if ($required === '') {
            return true;
        }

        return $running !== '' && version_compare($running, $required, '>=');
    }

    private function extractPhpVersion(string $output): ?string
    {
        $clean = $this->sanitizeComposeOutput($output);
        if (preg_match_all('/\d+\.\d+\.\d+/', $clean, $matches) < 1) {
            return null;
        }

        $found = null;
        foreach ($matches[0] as $version) {
            if (version_compare($version, '8.0.0', '>=')) {
                $found = $version;
            }
        }

        return $found;
    }

    private function realComposeErrorLine(string $output): ?string
    {
        $clean = $this->sanitizeComposeOutput($output);
        $patterns = [
            '/failed to (?:solve|resolve source metadata)[^\n]*/i',
            '/no match for platform in manifest[^\n]*/i',
            '/manifest unknown[^\n]*/i',
            '/pull access denied[^\n]*/i',
            '/no such image[^\n]*/i',
            '/dockerfile[^\n]*(?:syntax|parse)[^\n]*/i',
            '/(?:syntax|parse) error[^\n]*/i',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean, $matches) === 1) {
                return trim($matches[0]);
            }
        }

        return null;
    }

    private function sanitizeComposeOutput(string $output): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $this->stripAnsi($output)) ?: [];
        $kept = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $this->isComposeNoiseLine($line)) {
                continue;
            }
            $kept[] = $line;
        }

        return implode("\n", $kept);
    }

    private function isComposeNoiseLine(string $line): bool
    {
        if (preg_match('/delegate build to bake/i', $line) === 1) {
            return true;
        }
        if (preg_match('/^Just set COMPOSE_BAKE=true\.?$/i', $line) === 1) {
            return true;
        }
        if (preg_match('/^\[\+\]\s+Building\b/i', $line) === 1 && preg_match('/error|failed|denied|unknown|not found/i', $line) !== 1) {
            return true;
        }

        return false;
    }

    private function stripAnsi(string $output): string
    {
        $stripped = preg_replace('/\x1B(?:[@-Z\\-_]|\[[0-?]*[ -\/]*[@-~])/', '', $output);

        return is_string($stripped) ? $stripped : $output;
    }

    private function truncateComposeOutput(string $output, int $max = 6000): string
    {
        $output = trim($output);
        if (strlen($output) <= $max) {
            return $output;
        }

        return substr($output, -$max);
    }

    private function pendingPath(): string
    {
        return storage_path('app/system-upgrade/pending.json');
    }

    private function commandExists(string $binary): bool
    {
        $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary), base_path(), null, null, 10);
        $process->run();

        return $process->isSuccessful();
    }
}

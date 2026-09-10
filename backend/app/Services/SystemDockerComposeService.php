<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class SystemDockerComposeService
{
    public const KIND_PHP = 'php';

    public const KIND_LARAVEL = 'laravel';

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
        if (is_string($configured) && is_dir($configured)) {
            return rtrim($configured, '/');
        }

        $env = env('NEXA_HOST_PROJECT_DIR');
        if (is_string($env) && is_dir($env)) {
            return rtrim($env, '/');
        }

        $inspect = $this->selfContainerInspect();
        $labels = is_array($inspect['Config']['Labels'] ?? null) ? $inspect['Config']['Labels'] : [];
        $workingDir = $labels['com.docker.compose.project.working_dir'] ?? null;
        if (is_string($workingDir) && is_dir($workingDir)) {
            return rtrim($workingDir, '/');
        }

        if (is_file(base_path('Dockerfile')) && is_file(base_path('../docker-compose.yml'))) {
            $dir = realpath(base_path('..'));

            return is_string($dir) ? $dir : null;
        }

        return null;
    }

    /**
     * Bouwt images waarvan de context is veranderd en start de hele compose-stack opnieuw.
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    public function recreateStack(?callable $emit, array &$steps): void
    {
        $hostDir = $this->hostProjectDir();
        if ($hostDir === null) {
            throw new \RuntimeException('Host-projectmap is onbekend. Zet NEXA_HOST_PROJECT_DIR of mount de repo in de container.');
        }

        $this->runComposeInHelperContainer($emit, $steps, $hostDir);
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
        $compose = $this->composeInvocation($hostDir);
        $args = ['compose'];
        if ($compose['project'] !== null) {
            $args = array_merge($args, ['-p', $compose['project']]);
        }

        $args = array_merge($args, [
            '-f', $compose['file'],
            '--project-directory', $hostDir,
            'up', '-d',
        ]);

        if ($build) {
            $args[] = '--build';
        }

        return $args;
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

        $file = $firstFile !== '' && is_file($firstFile)
            ? $firstFile
            : (is_file($hostDir.'/docker-compose.yml')
                ? $hostDir.'/docker-compose.yml'
                : $hostDir.'/docker-compose.deploy.yml');

        if (! is_file($file)) {
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
     * Compose draait in een aparte container, zodat het stoppen van `backend`
     * de upgrade niet afkapt (ETXTBSY/zelf-kill vanaf de bind-mount).
     *
     * @param  list<array{label: string, status: string, output?: string}>  $steps
     */
    private function runComposeInHelperContainer(?callable $emit, array &$steps, string $hostDir): void
    {
        $this->upgrades->emitProgress($emit, $steps, 'Docker-stack bouwen en herstarten', 'running');
        $this->ensureHelperImage();
        $this->dockerDeleteContainer(self::HELPER_CONTAINER);

        $args = $this->composeCliArgs($hostDir, true);
        $created = $this->dockerRequest('post', '/containers/create?name='.urlencode(self::HELPER_CONTAINER), [
            'Image' => self::HELPER_IMAGE,
            'Cmd' => $args,
            'WorkingDir' => $hostDir,
            'Tty' => true,
            'Env' => [
                'DOCKER_HOST=unix:///var/run/docker.sock',
                'PWD='.$hostDir,
                'COMPOSE_PROJECT_DIR='.$hostDir,
            ],
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
        $deadline = time() + 1800;
        while (time() < $deadline) {
            $inspect = $this->dockerRequest('get', '/containers/'.$id.'/json', null, 15);
            $state = is_array($inspect->json('State')) ? $inspect->json('State') : [];
            $logs = $this->dockerRequest('get', '/containers/'.$id.'/logs?stdout=1&stderr=1', null, 15);
            $decoded = $this->decodeDockerLogs((string) $logs->body());
            if (strlen($decoded) > $seen) {
                $chunk = substr($decoded, $seen);
                $seen = strlen($decoded);
                $output .= $chunk;
                $this->emitComposeNotes($emit, $chunk);
            }

            if (! ($state['Running'] ?? false)) {
                $exit = (int) ($state['ExitCode'] ?? 1);
                $this->dockerDeleteContainer(self::HELPER_CONTAINER);
                if ($exit !== 0) {
                    throw new \RuntimeException(trim(
                        'Docker-stack bouwen en herstarten mislukt: '.$this->truncateComposeOutput($output)
                    ));
                }

                $this->upgrades->emitProgress($emit, $steps, 'Docker-stack bouwen en herstarten voltooid', 'done');

                return;
            }

            sleep(2);
        }

        $this->dockerDeleteContainer(self::HELPER_CONTAINER);
        throw new \RuntimeException('Docker-stack bouwen en herstarten timeout na 30 minuten.');
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
    private function dockerRequest(string $method, string $path, ?array $json = null, int $timeout = 30): \Illuminate\Http\Client\Response
    {
        $request = Http::withOptions([
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock',
            ],
        ])->timeout($timeout)->connectTimeout(5);

        $url = 'http://localhost/v1.41'.$path;

        return match (strtolower($method)) {
            'get' => $request->get($url),
            'delete' => $request->delete($url),
            'post' => $json === null ? $request->post($url) : $request->asJson()->post($url, $json),
            default => throw new \InvalidArgumentException('Onbekende Docker-API-methode: '.$method),
        };
    }

    private function dockerError(\Illuminate\Http\Client\Response $response): string
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

    private function emitComposeNotes(?callable $emit, string $chunk): void
    {
        if ($emit === null) {
            return;
        }

        foreach (preg_split("/\r\n|\n|\r/", $chunk) ?: [] as $line) {
            $line = trim($line);
            if ($line !== '') {
                $emit(['type' => 'note', 'note' => 'Docker-stack bouwen en herstarten: '.$line]);
            }
        }
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

<?php

namespace App\Services;

use App\Support\TenantSync\TenantSyncConnectionConfig;
use App\Support\TenantSync\TenantSyncDatabaseUrl;
use RuntimeException;
use Symfony\Component\Process\Process;

final class TenantSyncSshTunnelService
{
    private ?Process $process = null;

    private ?int $localPort = null;

    private ?TenantSyncConnectionConfig $activeConfig = null;

    private ?string $askpassScript = null;

    private ?string $askpassPasswordFile = null;

    public function __construct(
        private readonly TenantSyncSettingsService $settings,
    ) {}

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runIsolated(callable $callback, ?TenantSyncConnectionConfig $config = null): mixed
    {
        try {
            return $callback();
        } finally {
            $this->close();
        }
    }

    public function applyTunnelToDatabaseUrl(string $url, ?TenantSyncConnectionConfig $config = null): string
    {
        $config ??= $this->settings->connectionConfig();
        if (! $config->sshEnabled) {
            return $url;
        }

        $localPort = $this->ensureOpen($config);

        return TenantSyncDatabaseUrl::replaceHostPort($url, '127.0.0.1', $localPort);
    }

    public function close(): void
    {
        if ($this->process !== null) {
            if ($this->process->isRunning()) {
                $this->process->stop(3, \defined('SIGTERM') ? \SIGTERM : 15);
            }
            $this->process = null;
        }

        $this->localPort = null;
        $this->activeConfig = null;

        if ($this->askpassScript !== null && is_file($this->askpassScript)) {
            @unlink($this->askpassScript);
            $this->askpassScript = null;
        }

        if ($this->askpassPasswordFile !== null && is_file($this->askpassPasswordFile)) {
            @unlink($this->askpassPasswordFile);
            $this->askpassPasswordFile = null;
        }
    }

    private function ensureOpen(TenantSyncConnectionConfig $config): int
    {
        if ($this->localPort !== null && $this->process?->isRunning() && $this->configsMatch($this->activeConfig, $config)) {
            return $this->localPort;
        }

        $this->close();
        $this->settings->validateConfig($config);

        if (! $this->sshBinaryAvailable()) {
            throw new RuntimeException(
                'SSH-client ontbreekt in de backend-container. Installeer openssh-client (zie Dockerfile) en bouw de container opnieuw.'
            );
        }

        $localPort = $this->allocateLocalPort();
        $remoteHost = $config->remoteDbHost;
        $remotePort = $config->remoteDbPort;

        $command = [
            'ssh',
            '-N',
            '-L', sprintf('127.0.0.1:%d:%s:%d', $localPort, $remoteHost, $remotePort),
            '-p', (string) $config->sshPort,
            '-o', 'BatchMode=no',
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ExitOnForwardFailure=yes',
            '-o', 'ServerAliveInterval=30',
            sprintf('%s@%s', $config->sshUsername, $config->sshHost),
        ];

        $env = [
            'PATH' => getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
            'HOME' => getenv('HOME') ?: '/tmp',
            'SSH_ASKPASS' => $this->createAskpassScript((string) $config->sshPassword),
            'SSH_ASKPASS_REQUIRE' => 'force',
            'DISPLAY' => getenv('DISPLAY') ?: ':0',
        ];

        $process = new Process($command, null, $env);
        $process->setTimeout(null);
        $process->start();

        $this->waitForLocalPort($localPort, $process);

        $this->process = $process;
        $this->localPort = $localPort;
        $this->activeConfig = $config;

        return $localPort;
    }

    private function sshBinaryAvailable(): bool
    {
        $process = new Process(['which', 'ssh']);
        $process->run();

        return $process->isSuccessful();
    }

    private function allocateLocalPort(): int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($socket === false) {
            throw new RuntimeException('Kon geen lokaal tunnel-poortnummer reserveren: '.$errstr);
        }

        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        if (! is_string($address) || ! str_contains($address, ':')) {
            throw new RuntimeException('Kon lokaal tunnel-poortnummer niet bepalen.');
        }

        return (int) substr($address, strrpos($address, ':') + 1);
    }

    private function waitForLocalPort(int $port, Process $process): void
    {
        $deadline = microtime(true) + 20.0;

        while (microtime(true) < $deadline) {
            if (! $process->isRunning()) {
                throw new RuntimeException(
                    'SSH-tunnel startte niet: '.trim($process->getErrorOutput() ?: $process->getOutput() ?: 'onbekende fout')
                );
            }

            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.4);
            if ($connection !== false) {
                fclose($connection);

                return;
            }

            usleep(200_000);
        }

        throw new RuntimeException('SSH-tunnel timeout: lokale poort '.$port.' werd niet bereikbaar binnen 20 seconden.');
    }

    private function createAskpassScript(string $password): string
    {
        if ($this->askpassScript !== null && is_file($this->askpassScript)) {
            return $this->askpassScript;
        }

        $path = tempnam(sys_get_temp_dir(), 'nexa_ssh_askpass_');
        if ($path === false) {
            throw new RuntimeException('Kon SSH_ASKPASS-script niet aanmaken.');
        }

        $passFile = tempnam(sys_get_temp_dir(), 'nexa_ssh_pass_');
        if ($passFile === false) {
            throw new RuntimeException('Kon tijdelijk wachtwoordbestand voor SSH niet aanmaken.');
        }

        if (file_put_contents($passFile, $password) === false) {
            @unlink($passFile);
            throw new RuntimeException('Kon SSH-wachtwoord niet wegschrijven.');
        }
        chmod($passFile, 0600);

        $script = "#!/bin/sh\ntr -d '\\n' < ".escapeshellarg($passFile)."\n";
        file_put_contents($path, $script);
        chmod($path, 0700);

        $this->askpassScript = $path;
        $this->askpassPasswordFile = $passFile;

        return $path;
    }

    private function configsMatch(?TenantSyncConnectionConfig $a, TenantSyncConnectionConfig $b): bool
    {
        if ($a === null) {
            return false;
        }

        return $a->sshHost === $b->sshHost
            && $a->sshPort === $b->sshPort
            && $a->sshUsername === $b->sshUsername
            && $a->sshPassword === $b->sshPassword
            && $a->remoteDbHost === $b->remoteDbHost
            && $a->remoteDbPort === $b->remoteDbPort;
    }
}

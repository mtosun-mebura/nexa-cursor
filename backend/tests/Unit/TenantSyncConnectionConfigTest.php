<?php

namespace Tests\Unit;

use App\Services\TenantWebsiteBundleService;
use App\Support\TenantSync\TenantSyncConnectionConfig;
use Tests\TestCase;

class TenantSyncConnectionConfigTest extends TestCase
{
    public function test_explain_sync_connection_error_includes_ssh_tunnel_hint(): void
    {
        $config = new TenantSyncConnectionConfig(
            sshEnabled: true,
            sshHost: 'prod.example.com',
            sshPort: 22,
            sshUsername: 'deploy',
            sshPassword: 'ssh-pass',
            remoteDbHost: '127.0.0.1',
            remoteDbPort: 5432,
            databaseUrl: 'pgsql://nexa@127.0.0.1:5432/nexa_prod',
            databasePassword: 'db-pass',
        );

        $message = app(TenantWebsiteBundleService::class)->explainSyncTargetConnectionError(
            new \RuntimeException('SQLSTATE[08006] server closed the connection unexpectedly'),
            $config
        );

        $this->assertStringContainsString('SSH-tunnel', $message);
        $this->assertStringContainsString('prod.example.com', $message);
        $this->assertStringContainsString('127.0.0.1:5432', $message);
        $this->assertStringContainsString('docker-compose.deploy.yml', $message);
    }

    public function test_explain_sync_connection_error_without_ssh_returns_original_message(): void
    {
        $config = new TenantSyncConnectionConfig(
            sshEnabled: false,
            sshHost: '',
            sshPort: 22,
            sshUsername: '',
            sshPassword: null,
            remoteDbHost: '127.0.0.1',
            remoteDbPort: 5432,
            databaseUrl: 'pgsql://nexa@db.example.com:5432/nexa_prod',
            databasePassword: 'db-pass',
        );

        $message = app(TenantWebsiteBundleService::class)->explainSyncTargetConnectionError(
            new \RuntimeException('Direct connection refused'),
            $config
        );

        $this->assertSame('Direct connection refused', $message);
    }
}

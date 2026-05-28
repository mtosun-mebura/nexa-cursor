<?php

namespace App\Support\TenantSync;

final class TenantSyncConnectionConfig
{
    public function __construct(
        public readonly bool $sshEnabled,
        public readonly string $sshHost,
        public readonly int $sshPort,
        public readonly string $sshUsername,
        public readonly ?string $sshPassword,
        public readonly string $remoteDbHost,
        public readonly int $remoteDbPort,
        public readonly string $databaseUrl,
        public readonly ?string $databasePassword,
    ) {}

    public function resolvedDatabaseUrl(): string
    {
        $url = trim($this->databaseUrl);
        $password = $this->databasePassword;
        if ($password === null || $password === '') {
            $password = TenantSyncDatabaseUrl::extractPassword($url);
        }

        if ($password !== null && $password !== '') {
            $url = TenantSyncDatabaseUrl::injectPassword(TenantSyncDatabaseUrl::stripPassword($url), $password);
        }

        return $url;
    }

    public function displayDatabaseUrl(): string
    {
        return TenantSyncDatabaseUrl::stripPassword(trim($this->databaseUrl));
    }
}

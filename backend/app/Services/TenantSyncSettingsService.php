<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Support\TenantSync\TenantSyncConnectionConfig;
use App\Support\TenantSync\TenantSyncDatabaseUrl;
use App\Support\TenantSync\TenantSyncEncryptedSettings;
use App\Support\TenantSync\TenantSyncSecretInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

final class TenantSyncSettingsService
{
    public const KEY_DB_URL = 'tenant_sync_target_database_url';

    public const KEY_DB_PASSWORD = 'tenant_sync_target_database_password_enc';

    public const KEY_SSH_ENABLED = 'tenant_sync_ssh_enabled';

    public const KEY_SSH_HOST = 'tenant_sync_ssh_host';

    public const KEY_SSH_PORT = 'tenant_sync_ssh_port';

    public const KEY_SSH_USERNAME = 'tenant_sync_ssh_username';

    public const KEY_SSH_PASSWORD = 'tenant_sync_ssh_password_enc';

    public const KEY_SSH_REMOTE_DB_HOST = 'tenant_sync_ssh_remote_db_host';

    public const KEY_SSH_REMOTE_DB_PORT = 'tenant_sync_ssh_remote_db_port';

    public const KEY_SSH_DB_USERNAME = 'tenant_sync_ssh_db_username';

    public const KEY_SSH_DB_DATABASE = 'tenant_sync_ssh_db_database';

    /**
     * @return array<string, mixed>
     */
    public function formSettings(): array
    {
        $url = (string) GeneralSetting::get(self::KEY_DB_URL, '');
        $storedDbUser = trim((string) GeneralSetting::get(self::KEY_SSH_DB_USERNAME, ''));
        $storedDbName = trim((string) GeneralSetting::get(self::KEY_SSH_DB_DATABASE, ''));
        $hasDbPassword = TenantSyncEncryptedSettings::get(self::KEY_DB_PASSWORD) !== null
            || TenantSyncDatabaseUrl::extractPassword($url) !== null;

        return [
            'tenant_sync_target_database_url' => TenantSyncDatabaseUrl::stripPassword($url),
            'tenant_sync_has_database_password' => $hasDbPassword,
            'tenant_sync_ssh_db_username' => $storedDbUser !== '' ? $storedDbUser : TenantSyncDatabaseUrl::parseUser($url),
            'tenant_sync_ssh_db_database' => $storedDbName !== '' ? $storedDbName : TenantSyncDatabaseUrl::parseDatabase($url),
            'tenant_sync_has_ssh_db_password' => $hasDbPassword,
            'tenant_sync_push_enabled' => GeneralSetting::get('tenant_sync_push_enabled', '0') === '1',
            'tenant_sync_ssh_enabled' => GeneralSetting::get(self::KEY_SSH_ENABLED, '0') === '1',
            'tenant_sync_ssh_host' => (string) GeneralSetting::get(self::KEY_SSH_HOST, ''),
            'tenant_sync_ssh_port' => (string) GeneralSetting::get(self::KEY_SSH_PORT, '22'),
            'tenant_sync_ssh_username' => (string) GeneralSetting::get(self::KEY_SSH_USERNAME, ''),
            'tenant_sync_has_ssh_password' => TenantSyncEncryptedSettings::get(self::KEY_SSH_PASSWORD) !== null,
            'tenant_sync_ssh_remote_db_host' => (string) GeneralSetting::get(self::KEY_SSH_REMOTE_DB_HOST, '127.0.0.1'),
            'tenant_sync_ssh_remote_db_port' => (string) GeneralSetting::get(self::KEY_SSH_REMOTE_DB_PORT, '5432'),
        ];
    }

    public function connectionConfig(?Request $request = null): TenantSyncConnectionConfig
    {
        if ($request !== null) {
            return $this->configFromRequest($request);
        }

        $url = (string) GeneralSetting::get(self::KEY_DB_URL, '');
        if (trim($url) === '') {
            $url = trim((string) env('TENANT_SYNC_TARGET_DATABASE_URL', ''));
        }

        return $this->makeConnectionConfig(
            sshEnabled: GeneralSetting::get(self::KEY_SSH_ENABLED, '0') === '1',
            sshHost: trim((string) GeneralSetting::get(self::KEY_SSH_HOST, '')),
            sshPort: max(1, min(65535, (int) GeneralSetting::get(self::KEY_SSH_PORT, '22'))),
            sshUsername: trim((string) GeneralSetting::get(self::KEY_SSH_USERNAME, '')),
            sshPassword: TenantSyncEncryptedSettings::get(self::KEY_SSH_PASSWORD),
            remoteDbHost: trim((string) GeneralSetting::get(self::KEY_SSH_REMOTE_DB_HOST, '127.0.0.1')) ?: '127.0.0.1',
            remoteDbPort: max(1, min(65535, (int) GeneralSetting::get(self::KEY_SSH_REMOTE_DB_PORT, '5432'))),
            databaseUrl: $url,
            databasePassword: TenantSyncEncryptedSettings::get(self::KEY_DB_PASSWORD),
        );
    }

    public function saveFromRequest(Request $request): void
    {
        if ($request->boolean('tenant_sync_target_database_password_clear')) {
            TenantSyncEncryptedSettings::set(self::KEY_DB_PASSWORD, null);
        }
        if ($request->boolean('tenant_sync_ssh_password_clear')) {
            TenantSyncEncryptedSettings::set(self::KEY_SSH_PASSWORD, null);
        }
        if ($request->boolean('tenant_sync_ssh_db_password_clear')) {
            TenantSyncEncryptedSettings::set(self::KEY_DB_PASSWORD, null);
        }

        $sshEnabled = $request->boolean('tenant_sync_ssh_enabled');
        GeneralSetting::set(self::KEY_SSH_ENABLED, $sshEnabled ? '1' : '0');
        GeneralSetting::set('tenant_sync_push_enabled', $request->boolean('tenant_sync_push_enabled') ? '1' : '0');

        if ($sshEnabled) {
            $this->saveSshModeFromRequest($request);

            return;
        }

        $this->saveDirectModeFromRequest($request);
    }

    public function validateRequest(Request $request): void
    {
        $sshEnabled = $request->boolean('tenant_sync_ssh_enabled');

        $rules = [
            'tenant_sync_push_enabled' => ['nullable', 'boolean'],
            'tenant_sync_ssh_enabled' => ['nullable', 'boolean'],
            'tenant_sync_target_database_password' => ['nullable', 'string', 'max:500'],
            'tenant_sync_target_database_password_clear' => ['nullable', 'boolean'],
            'tenant_sync_ssh_password' => ['nullable', 'string', 'max:500'],
            'tenant_sync_ssh_password_clear' => ['nullable', 'boolean'],
            'tenant_sync_ssh_db_password' => ['nullable', 'string', 'max:500'],
            'tenant_sync_ssh_db_password_clear' => ['nullable', 'boolean'],
        ];

        if ($sshEnabled) {
            $rules[self::KEY_SSH_HOST] = ['required', 'string', 'max:255'];
            $rules[self::KEY_SSH_PORT] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules[self::KEY_SSH_USERNAME] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_username'] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_database'] = ['required', 'string', 'max:255'];
            $rules[self::KEY_SSH_REMOTE_DB_HOST] = ['nullable', 'string', 'max:255'];
            $rules[self::KEY_SSH_REMOTE_DB_PORT] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules[self::KEY_DB_URL] = ['nullable', 'string', 'max:2000'];
        } else {
            $rules[self::KEY_DB_URL] = ['required', 'string', 'max:2000'];
            $rules[self::KEY_SSH_HOST] = ['nullable', 'string', 'max:255'];
            $rules[self::KEY_SSH_PORT] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules[self::KEY_SSH_USERNAME] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_username'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_database'] = ['nullable', 'string', 'max:255'];
            $rules[self::KEY_SSH_REMOTE_DB_HOST] = ['nullable', 'string', 'max:255'];
            $rules[self::KEY_SSH_REMOTE_DB_PORT] = ['nullable', 'integer', 'min:1', 'max:65535'];
        }

        Validator::make($request->all(), $rules)->validate();
    }

    public function validateConfig(TenantSyncConnectionConfig $config): void
    {
        if ($config->resolvedDatabaseUrl() === '') {
            throw new \InvalidArgumentException(
                $config->sshEnabled
                    ? 'Vul database-gebruiker en database-naam in bij SSH-tunnel.'
                    : 'Vul een database-URL in.'
            );
        }

        if (! $config->sshEnabled) {
            return;
        }

        if ($config->sshHost === '') {
            throw new \InvalidArgumentException('SSH-host is verplicht wanneer SSH-tunnel aan staat.');
        }

        if ($config->sshUsername === '') {
            throw new \InvalidArgumentException('SSH-gebruikersnaam is verplicht wanneer SSH-tunnel aan staat.');
        }

        if ($config->sshPassword === null || $config->sshPassword === '') {
            throw new \InvalidArgumentException('SSH-wachtwoord is verplicht wanneer SSH-tunnel aan staat (of sla eerst op).');
        }

        if (TenantSyncDatabaseUrl::parseUser($config->databaseUrl) === '') {
            throw new \InvalidArgumentException('Database-gebruiker is verplicht in het SSH-blok.');
        }

        if (TenantSyncDatabaseUrl::parseDatabase($config->databaseUrl) === '') {
            throw new \InvalidArgumentException('Database-naam is verplicht in het SSH-blok.');
        }

        $hasPassword = $config->databasePassword !== null && $config->databasePassword !== '';
        if (! $hasPassword) {
            throw new \InvalidArgumentException('Database-wachtwoord is verplicht in het SSH-blok (of sla eerst op).');
        }
    }

    private function saveDirectModeFromRequest(Request $request): void
    {
        $rawUrl = trim((string) $request->input(self::KEY_DB_URL, ''));
        $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_target_database_password'));
        if ($dbPassword === '') {
            $dbPassword = TenantSyncDatabaseUrl::extractPassword($rawUrl) ?? '';
        }

        GeneralSetting::set(self::KEY_DB_URL, TenantSyncDatabaseUrl::stripPassword($rawUrl));

        if ($dbPassword !== '') {
            TenantSyncEncryptedSettings::set(self::KEY_DB_PASSWORD, $dbPassword);
        }

        GeneralSetting::set(self::KEY_SSH_HOST, trim((string) $request->input(self::KEY_SSH_HOST, '')));
        GeneralSetting::set(self::KEY_SSH_PORT, (string) max(1, min(65535, (int) $request->input(self::KEY_SSH_PORT, 22))));
        GeneralSetting::set(self::KEY_SSH_USERNAME, trim((string) $request->input(self::KEY_SSH_USERNAME, '')));

        $sshPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_password'));
        if ($sshPassword !== '') {
            TenantSyncEncryptedSettings::set(self::KEY_SSH_PASSWORD, $sshPassword);
        }
    }

    private function saveSshModeFromRequest(Request $request): void
    {
        $dbUser = trim((string) $request->input('tenant_sync_ssh_db_username', ''));
        $dbName = trim((string) $request->input('tenant_sync_ssh_db_database', ''));
        $remoteHost = trim((string) $request->input(self::KEY_SSH_REMOTE_DB_HOST, '127.0.0.1')) ?: '127.0.0.1';
        $remotePort = max(1, min(65535, (int) $request->input(self::KEY_SSH_REMOTE_DB_PORT, 5432)));

        $url = TenantSyncDatabaseUrl::buildConnection('pgsql', $dbUser, $remoteHost, $remotePort, $dbName);
        GeneralSetting::set(self::KEY_DB_URL, $url);
        GeneralSetting::set(self::KEY_SSH_DB_USERNAME, $dbUser);
        GeneralSetting::set(self::KEY_SSH_DB_DATABASE, $dbName);

        $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_db_password'));
        if ($dbPassword !== '') {
            TenantSyncEncryptedSettings::set(self::KEY_DB_PASSWORD, $dbPassword);
        }

        GeneralSetting::set(self::KEY_SSH_HOST, trim((string) $request->input(self::KEY_SSH_HOST, '')));
        GeneralSetting::set(self::KEY_SSH_PORT, (string) max(1, min(65535, (int) $request->input(self::KEY_SSH_PORT, 22))));
        GeneralSetting::set(self::KEY_SSH_USERNAME, trim((string) $request->input(self::KEY_SSH_USERNAME, '')));
        GeneralSetting::set(self::KEY_SSH_REMOTE_DB_HOST, $remoteHost);
        GeneralSetting::set(self::KEY_SSH_REMOTE_DB_PORT, (string) $remotePort);

        $sshPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_password'));
        if ($sshPassword !== '') {
            TenantSyncEncryptedSettings::set(self::KEY_SSH_PASSWORD, $sshPassword);
        }
    }

    private function configFromRequest(Request $request): TenantSyncConnectionConfig
    {
        $stored = $this->connectionConfig();
        $sshEnabled = $request->has('tenant_sync_ssh_enabled')
            ? $request->boolean('tenant_sync_ssh_enabled')
            : $stored->sshEnabled;

        if ($sshEnabled) {
            $dbUser = trim((string) $request->input('tenant_sync_ssh_db_username', ''));
            if ($dbUser === '') {
                $dbUser = trim((string) GeneralSetting::get(self::KEY_SSH_DB_USERNAME, ''));
            }
            $dbName = trim((string) $request->input('tenant_sync_ssh_db_database', ''));
            if ($dbName === '') {
                $dbName = trim((string) GeneralSetting::get(self::KEY_SSH_DB_DATABASE, ''));
            }
            $remoteHost = trim((string) $request->input(self::KEY_SSH_REMOTE_DB_HOST, $stored->remoteDbHost)) ?: '127.0.0.1';
            $remotePort = max(1, min(65535, (int) $request->input(self::KEY_SSH_REMOTE_DB_PORT, $stored->remoteDbPort)));

            $url = $dbUser !== '' && $dbName !== ''
                ? TenantSyncDatabaseUrl::buildConnection('pgsql', $dbUser, $remoteHost, $remotePort, $dbName)
                : $stored->databaseUrl;

            $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_db_password'));
            if ($dbPassword === '') {
                $dbPassword = $stored->databasePassword;
            }

            return $this->makeConnectionConfig(
                sshEnabled: true,
                sshHost: trim((string) $request->input(self::KEY_SSH_HOST, $stored->sshHost)),
                sshPort: max(1, min(65535, (int) $request->input(self::KEY_SSH_PORT, $stored->sshPort))),
                sshUsername: trim((string) $request->input(self::KEY_SSH_USERNAME, $stored->sshUsername)),
                sshPassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_ssh_password', $stored->sshPassword),
                remoteDbHost: $remoteHost,
                remoteDbPort: $remotePort,
                databaseUrl: $url,
                databasePassword: $dbPassword !== '' ? $dbPassword : null,
            );
        }

        $url = TenantSyncDatabaseUrl::stripPassword(trim((string) $request->input(self::KEY_DB_URL, '')));
        if ($url === '') {
            $url = $stored->databaseUrl;
        }

        return $this->makeConnectionConfig(
            sshEnabled: false,
            sshHost: trim((string) $request->input(self::KEY_SSH_HOST, $stored->sshHost)),
            sshPort: max(1, min(65535, (int) $request->input(self::KEY_SSH_PORT, $stored->sshPort))),
            sshUsername: trim((string) $request->input(self::KEY_SSH_USERNAME, $stored->sshUsername)),
            sshPassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_ssh_password', $stored->sshPassword),
            remoteDbHost: trim((string) $request->input(self::KEY_SSH_REMOTE_DB_HOST, $stored->remoteDbHost)) ?: '127.0.0.1',
            remoteDbPort: max(1, min(65535, (int) $request->input(self::KEY_SSH_REMOTE_DB_PORT, $stored->remoteDbPort))),
            databaseUrl: $url,
            databasePassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_target_database_password', $stored->databasePassword),
        );
    }

    private function resolvePasswordFromRequest(Request $request, string $field, ?string $stored): ?string
    {
        $password = TenantSyncSecretInput::normalize($request->input($field));

        return $password !== '' ? $password : $stored;
    }

    private function makeConnectionConfig(
        bool $sshEnabled,
        string $sshHost,
        int $sshPort,
        string $sshUsername,
        ?string $sshPassword,
        string $remoteDbHost,
        int $remoteDbPort,
        string $databaseUrl,
        ?string $databasePassword,
    ): TenantSyncConnectionConfig {
        return new TenantSyncConnectionConfig(
            sshEnabled: $sshEnabled,
            sshHost: $sshHost,
            sshPort: $sshPort,
            sshUsername: $sshUsername,
            sshPassword: $sshPassword,
            remoteDbHost: $remoteDbHost,
            remoteDbPort: $remoteDbPort,
            databaseUrl: $databaseUrl,
            databasePassword: $databasePassword,
        );
    }
}

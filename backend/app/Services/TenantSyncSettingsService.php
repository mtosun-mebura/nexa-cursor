<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\TenantSyncTarget;
use App\Support\TenantSync\TenantSyncConnectionConfig;
use App\Support\TenantSync\TenantSyncDatabaseUrl;
use App\Support\TenantSync\TenantSyncSecretInput;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

final class TenantSyncSettingsService
{
    /**
     * Alle geconfigureerde doel-omgevingen, gesorteerd op naam.
     *
     * @return Collection<int, TenantSyncTarget>
     */
    public function targets(): Collection
    {
        return TenantSyncTarget::query()->orderBy('name')->get();
    }

    /**
     * De omgeving waar naartoe gesynct wordt (actief gemarkeerd, anders de eerste).
     */
    public function activeTarget(): ?TenantSyncTarget
    {
        return TenantSyncTarget::query()->where('is_active', true)->orderBy('name')->first()
            ?? TenantSyncTarget::query()->orderBy('name')->first();
    }

    public function findTarget(?int $id): ?TenantSyncTarget
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        return TenantSyncTarget::query()->find($id);
    }

    /**
     * Formulierwaarden voor één omgeving (default: de actieve).
     *
     * @return array<string, mixed>
     */
    public function formSettings(?TenantSyncTarget $target = null): array
    {
        $target ??= $this->activeTarget();

        if ($target === null) {
            return [
                'tenant_sync_target_id' => 0,
                'tenant_sync_name' => '',
                'tenant_sync_target_database_url' => '',
                'tenant_sync_has_database_password' => false,
                'tenant_sync_ssh_db_username' => '',
                'tenant_sync_ssh_db_database' => '',
                'tenant_sync_has_ssh_db_password' => false,
                'tenant_sync_push_enabled' => false,
                'tenant_sync_ssh_enabled' => false,
                'tenant_sync_ssh_host' => '',
                'tenant_sync_ssh_port' => '22',
                'tenant_sync_ssh_username' => '',
                'tenant_sync_has_ssh_password' => false,
                'tenant_sync_ssh_remote_db_host' => '127.0.0.1',
                'tenant_sync_ssh_remote_db_port' => '5432',
            ];
        }

        $url = (string) $target->database_url;
        $hasDbPassword = $target->databasePassword() !== null
            || TenantSyncDatabaseUrl::extractPassword($url) !== null;
        $storedDbUser = trim((string) $target->db_username);
        $storedDbName = trim((string) $target->db_database);

        return [
            'tenant_sync_target_id' => (int) $target->id,
            'tenant_sync_name' => (string) $target->name,
            'tenant_sync_target_database_url' => TenantSyncDatabaseUrl::stripPassword($url),
            'tenant_sync_has_database_password' => $hasDbPassword,
            'tenant_sync_ssh_db_username' => $storedDbUser !== '' ? $storedDbUser : TenantSyncDatabaseUrl::parseUser($url),
            'tenant_sync_ssh_db_database' => $storedDbName !== '' ? $storedDbName : TenantSyncDatabaseUrl::parseDatabase($url),
            'tenant_sync_has_ssh_db_password' => $hasDbPassword,
            'tenant_sync_push_enabled' => (bool) $target->push_enabled,
            'tenant_sync_ssh_enabled' => (bool) $target->ssh_enabled,
            'tenant_sync_ssh_host' => (string) $target->ssh_host,
            'tenant_sync_ssh_port' => (string) ($target->ssh_port ?: 22),
            'tenant_sync_ssh_username' => (string) $target->ssh_username,
            'tenant_sync_has_ssh_password' => $target->sshPassword() !== null,
            'tenant_sync_ssh_remote_db_host' => (string) ($target->remote_db_host ?: '127.0.0.1'),
            'tenant_sync_ssh_remote_db_port' => (string) ($target->remote_db_port ?: 5432),
        ];
    }

    public function connectionConfig(?Request $request = null): TenantSyncConnectionConfig
    {
        if ($request !== null) {
            return $this->configFromRequest($request);
        }

        $active = $this->activeTarget();
        if ($active !== null) {
            return $active->toConnectionConfig();
        }

        // Fallback op .env wanneer er nog geen omgeving is geconfigureerd.
        $url = trim((string) env('TENANT_SYNC_TARGET_DATABASE_URL', ''));

        return new TenantSyncConnectionConfig(
            sshEnabled: false,
            sshHost: '',
            sshPort: 22,
            sshUsername: '',
            sshPassword: null,
            remoteDbHost: '127.0.0.1',
            remoteDbPort: 5432,
            databaseUrl: $url,
            databasePassword: null,
        );
    }

    public function saveFromRequest(Request $request): TenantSyncTarget
    {
        $target = $this->findTarget((int) $request->input('tenant_sync_target_id')) ?? new TenantSyncTarget;
        $isNew = ! $target->exists;

        $target->name = trim((string) $request->input('tenant_sync_name')) ?: 'Naamloze omgeving';

        $sshEnabled = $request->boolean('tenant_sync_ssh_enabled');
        $target->ssh_enabled = $sshEnabled;
        $target->push_enabled = $request->boolean('tenant_sync_push_enabled');

        if ($sshEnabled) {
            $this->applySshMode($target, $request);
        } else {
            $this->applyDirectMode($target, $request);
        }

        // Wachtwoord-clears
        if ($request->boolean('tenant_sync_target_database_password_clear')
            || $request->boolean('tenant_sync_ssh_db_password_clear')) {
            $target->setDatabasePassword(null);
        }
        if ($request->boolean('tenant_sync_ssh_password_clear')) {
            $target->setSshPassword(null);
        }

        // Eerste omgeving is meteen de actieve.
        if ($isNew && TenantSyncTarget::query()->where('is_active', true)->doesntExist()) {
            $target->is_active = true;
        }

        $target->save();

        $this->mirrorActivePushFlag();

        return $target;
    }

    public function validateRequest(Request $request): void
    {
        $sshEnabled = $request->boolean('tenant_sync_ssh_enabled');

        $rules = [
            'tenant_sync_target_id' => ['nullable', 'integer'],
            'tenant_sync_name' => ['required', 'string', 'max:120'],
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
            $rules['tenant_sync_ssh_host'] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_port'] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules['tenant_sync_ssh_username'] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_username'] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_database'] = ['required', 'string', 'max:255'];
            $rules['tenant_sync_ssh_remote_db_host'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_remote_db_port'] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules['tenant_sync_target_database_url'] = ['nullable', 'string', 'max:2000'];
        } else {
            $rules['tenant_sync_target_database_url'] = ['required', 'string', 'max:2000'];
            $rules['tenant_sync_ssh_host'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_port'] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules['tenant_sync_ssh_username'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_username'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_db_database'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_remote_db_host'] = ['nullable', 'string', 'max:255'];
            $rules['tenant_sync_ssh_remote_db_port'] = ['nullable', 'integer', 'min:1', 'max:65535'];
        }

        Validator::make($request->all(), $rules, [
            'tenant_sync_name.required' => 'Geef de omgeving een naam.',
        ])->validate();
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

    /**
     * Maak een lege nieuwe omgeving aan en markeer die als actief.
     */
    public function createTarget(?string $name = null): TenantSyncTarget
    {
        $name = trim((string) $name);
        if ($name === '') {
            $name = $this->uniqueDefaultName();
        }

        $target = TenantSyncTarget::query()->create([
            'name' => $name,
            'ssh_enabled' => false,
            'ssh_port' => 22,
            'remote_db_host' => '127.0.0.1',
            'remote_db_port' => 5432,
            'push_enabled' => false,
            'is_active' => false,
        ]);

        $this->activate((int) $target->id);

        return $target;
    }

    public function activate(int $id): void
    {
        $target = $this->findTarget($id);
        if ($target === null) {
            return;
        }

        TenantSyncTarget::query()->where('id', '!=', $target->id)->update(['is_active' => false]);
        $target->update(['is_active' => true]);

        $this->mirrorActivePushFlag();
    }

    public function deleteTarget(int $id): void
    {
        $target = $this->findTarget($id);
        if ($target === null) {
            return;
        }

        $wasActive = (bool) $target->is_active;
        $target->delete();

        if ($wasActive) {
            $next = TenantSyncTarget::query()->orderBy('name')->first();
            if ($next !== null) {
                $this->activate((int) $next->id);

                return;
            }
        }

        $this->mirrorActivePushFlag();
    }

    private function applyDirectMode(TenantSyncTarget $target, Request $request): void
    {
        $rawUrl = trim((string) $request->input('tenant_sync_target_database_url', ''));
        $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_target_database_password'));
        if ($dbPassword === '') {
            $dbPassword = TenantSyncDatabaseUrl::extractPassword($rawUrl) ?? '';
        }

        $target->database_url = TenantSyncDatabaseUrl::stripPassword($rawUrl);
        if ($dbPassword !== '') {
            $target->setDatabasePassword($dbPassword);
        }

        $target->ssh_host = trim((string) $request->input('tenant_sync_ssh_host', '')) ?: null;
        $target->ssh_port = max(1, min(65535, (int) $request->input('tenant_sync_ssh_port', 22)));
        $target->ssh_username = trim((string) $request->input('tenant_sync_ssh_username', '')) ?: null;

        $sshPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_password'));
        if ($sshPassword !== '') {
            $target->setSshPassword($sshPassword);
        }
    }

    private function applySshMode(TenantSyncTarget $target, Request $request): void
    {
        $dbUser = trim((string) $request->input('tenant_sync_ssh_db_username', ''));
        $dbName = trim((string) $request->input('tenant_sync_ssh_db_database', ''));
        $remoteHost = trim((string) $request->input('tenant_sync_ssh_remote_db_host', '127.0.0.1')) ?: '127.0.0.1';
        $remotePort = max(1, min(65535, (int) $request->input('tenant_sync_ssh_remote_db_port', 5432)));

        $target->database_url = TenantSyncDatabaseUrl::buildConnection('pgsql', $dbUser, $remoteHost, $remotePort, $dbName);
        $target->db_username = $dbUser !== '' ? $dbUser : null;
        $target->db_database = $dbName !== '' ? $dbName : null;

        $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_db_password'));
        if ($dbPassword !== '') {
            $target->setDatabasePassword($dbPassword);
        }

        $target->ssh_host = trim((string) $request->input('tenant_sync_ssh_host', '')) ?: null;
        $target->ssh_port = max(1, min(65535, (int) $request->input('tenant_sync_ssh_port', 22)));
        $target->ssh_username = trim((string) $request->input('tenant_sync_ssh_username', '')) ?: null;
        $target->remote_db_host = $remoteHost;
        $target->remote_db_port = $remotePort;

        $sshPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_password'));
        if ($sshPassword !== '') {
            $target->setSshPassword($sshPassword);
        }
    }

    private function configFromRequest(Request $request): TenantSyncConnectionConfig
    {
        $stored = $this->findTarget((int) $request->input('tenant_sync_target_id'))?->toConnectionConfig()
            ?? $this->connectionConfig();

        $sshEnabled = $request->has('tenant_sync_ssh_enabled')
            ? $request->boolean('tenant_sync_ssh_enabled')
            : $stored->sshEnabled;

        if ($sshEnabled) {
            $dbUser = trim((string) $request->input('tenant_sync_ssh_db_username', ''));
            if ($dbUser === '') {
                $dbUser = TenantSyncDatabaseUrl::parseUser($stored->databaseUrl);
            }
            $dbName = trim((string) $request->input('tenant_sync_ssh_db_database', ''));
            if ($dbName === '') {
                $dbName = TenantSyncDatabaseUrl::parseDatabase($stored->databaseUrl);
            }
            $remoteHost = trim((string) $request->input('tenant_sync_ssh_remote_db_host', $stored->remoteDbHost)) ?: '127.0.0.1';
            $remotePort = max(1, min(65535, (int) $request->input('tenant_sync_ssh_remote_db_port', $stored->remoteDbPort)));

            $url = $dbUser !== '' && $dbName !== ''
                ? TenantSyncDatabaseUrl::buildConnection('pgsql', $dbUser, $remoteHost, $remotePort, $dbName)
                : $stored->databaseUrl;

            $dbPassword = TenantSyncSecretInput::normalize($request->input('tenant_sync_ssh_db_password'));
            if ($dbPassword === '') {
                $dbPassword = $stored->databasePassword;
            }

            return new TenantSyncConnectionConfig(
                sshEnabled: true,
                sshHost: trim((string) $request->input('tenant_sync_ssh_host', $stored->sshHost)),
                sshPort: max(1, min(65535, (int) $request->input('tenant_sync_ssh_port', $stored->sshPort))),
                sshUsername: trim((string) $request->input('tenant_sync_ssh_username', $stored->sshUsername)),
                sshPassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_ssh_password', $stored->sshPassword),
                remoteDbHost: $remoteHost,
                remoteDbPort: $remotePort,
                databaseUrl: $url,
                databasePassword: $dbPassword !== '' ? $dbPassword : null,
            );
        }

        $url = TenantSyncDatabaseUrl::stripPassword(trim((string) $request->input('tenant_sync_target_database_url', '')));
        if ($url === '') {
            $url = $stored->databaseUrl;
        }

        return new TenantSyncConnectionConfig(
            sshEnabled: false,
            sshHost: trim((string) $request->input('tenant_sync_ssh_host', $stored->sshHost)),
            sshPort: max(1, min(65535, (int) $request->input('tenant_sync_ssh_port', $stored->sshPort))),
            sshUsername: trim((string) $request->input('tenant_sync_ssh_username', $stored->sshUsername)),
            sshPassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_ssh_password', $stored->sshPassword),
            remoteDbHost: trim((string) $request->input('tenant_sync_ssh_remote_db_host', $stored->remoteDbHost)) ?: '127.0.0.1',
            remoteDbPort: max(1, min(65535, (int) $request->input('tenant_sync_ssh_remote_db_port', $stored->remoteDbPort))),
            databaseUrl: $url,
            databasePassword: $this->resolvePasswordFromRequest($request, 'tenant_sync_target_database_password', $stored->databasePassword),
        );
    }

    private function resolvePasswordFromRequest(Request $request, string $field, ?string $stored): ?string
    {
        $password = TenantSyncSecretInput::normalize($request->input($field));

        return $password !== '' ? $password : $stored;
    }

    /**
     * Houd de legacy globale push-vlag gelijk aan de actieve omgeving,
     * zodat de bestaande sync-services onveranderd blijven werken.
     */
    private function mirrorActivePushFlag(): void
    {
        $active = $this->activeTarget();
        GeneralSetting::set('tenant_sync_push_enabled', $active && $active->push_enabled ? '1' : '0');
    }

    private function uniqueDefaultName(): string
    {
        $base = 'Nieuwe omgeving';
        if (TenantSyncTarget::query()->where('name', $base)->doesntExist()) {
            return $base;
        }

        $i = 2;
        while (TenantSyncTarget::query()->where('name', $base.' '.$i)->exists()) {
            $i++;
        }

        return $base.' '.$i;
    }
}

<?php

namespace App\Models;

use App\Support\TenantSync\TenantSyncConnectionConfig;
use App\Support\TenantSync\TenantSyncSecretInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Eén named doel-omgeving voor tenant-sync (bijv. "Productie", "Acceptatie").
 *
 * @property int $id
 * @property string $name
 * @property bool $ssh_enabled
 * @property string|null $ssh_host
 * @property int $ssh_port
 * @property string|null $ssh_username
 * @property string|null $ssh_password_enc
 * @property string $remote_db_host
 * @property int $remote_db_port
 * @property string|null $db_username
 * @property string|null $db_database
 * @property string|null $database_url
 * @property string|null $database_password_enc
 * @property bool $push_enabled
 * @property bool $is_active
 */
class TenantSyncTarget extends Model
{
    private const ENC_PREFIX = 'enc:';

    protected $fillable = [
        'name',
        'ssh_enabled',
        'ssh_host',
        'ssh_port',
        'ssh_username',
        'ssh_password_enc',
        'remote_db_host',
        'remote_db_port',
        'db_username',
        'db_database',
        'database_url',
        'database_password_enc',
        'push_enabled',
        'is_active',
    ];

    protected $casts = [
        'ssh_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'is_active' => 'boolean',
        'ssh_port' => 'integer',
        'remote_db_port' => 'integer',
    ];

    public function sshPassword(): ?string
    {
        return self::decrypt($this->ssh_password_enc);
    }

    public function databasePassword(): ?string
    {
        return self::decrypt($this->database_password_enc);
    }

    public function setSshPassword(?string $plain): void
    {
        $this->ssh_password_enc = self::encrypt($plain);
    }

    public function setDatabasePassword(?string $plain): void
    {
        $this->database_password_enc = self::encrypt($plain);
    }

    public function toConnectionConfig(): TenantSyncConnectionConfig
    {
        return new TenantSyncConnectionConfig(
            sshEnabled: (bool) $this->ssh_enabled,
            sshHost: trim((string) $this->ssh_host),
            sshPort: max(1, min(65535, (int) ($this->ssh_port ?: 22))),
            sshUsername: trim((string) $this->ssh_username),
            sshPassword: $this->sshPassword(),
            remoteDbHost: trim((string) $this->remote_db_host) ?: '127.0.0.1',
            remoteDbPort: max(1, min(65535, (int) ($this->remote_db_port ?: 5432))),
            databaseUrl: trim((string) $this->database_url),
            databasePassword: $this->databasePassword(),
        );
    }

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || trim($plain) === '') {
            return null;
        }

        return self::ENC_PREFIX.Crypt::encryptString(trim($plain));
    }

    public static function decrypt(?string $stored): ?string
    {
        $stored = trim((string) $stored);
        if ($stored === '') {
            return null;
        }

        if (! str_starts_with($stored, self::ENC_PREFIX)) {
            return TenantSyncSecretInput::normalize($stored);
        }

        try {
            $plain = Crypt::decryptString(substr($stored, strlen(self::ENC_PREFIX)));

            return TenantSyncSecretInput::normalize($plain);
        } catch (\Throwable) {
            return null;
        }
    }
}

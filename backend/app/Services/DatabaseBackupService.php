<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use App\Support\DestructiveDatabaseGuard;
use App\Support\TenantSync\TenantSyncConnectionConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DatabaseBackupService
{
    public function __construct(
        protected DatabaseBackupSettingsService $settings,
        protected ModuleDatabaseService $moduleDatabase,
    ) {}

    /**
     * @return list<DatabaseBackup>
     */
    public function listBackups(int $limit = 50): array
    {
        return DatabaseBackup::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function createBackup(string $trigger = DatabaseBackup::TRIGGER_MANUAL): DatabaseBackup
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $timestamp = now((string) config('database_backup.display_timezone', 'Europe/Amsterdam'))->format('Y-m-d_His');
        $filename = "nexa-backup_{$timestamp}.dump";
        $relativeDir = trim((string) config('database_backup.storage_directory', 'database-backups'), '/');
        $relativePath = $relativeDir.'/'.$filename;

        Storage::disk('local')->makeDirectory($relativeDir);
        $absolutePath = Storage::disk('local')->path($relativePath);

        $backup = DatabaseBackup::query()->create([
            'filename' => $filename,
            'disk_path' => $relativePath,
            'connection' => $connection,
            'database_name' => $database,
            'status' => DatabaseBackup::STATUS_PENDING,
            'trigger' => $trigger,
        ]);

        try {
            if ($connection === 'sqlite') {
                $this->backupSqlite($database, $absolutePath);
            } elseif (in_array($connection, ['pgsql', 'mysql', 'mariadb'], true)) {
                $this->backupPostgresOrMysql($connection, $database, $absolutePath);
            } else {
                throw new RuntimeException('Database-driver '.$connection.' wordt niet ondersteund voor backups.');
            }

            $size = File::exists($absolutePath) ? (int) filesize($absolutePath) : 0;
            if ($size <= 0) {
                throw new RuntimeException('Backupbestand is leeg of ontbreekt.');
            }

            $backup->update([
                'size_bytes' => $size,
                'status' => DatabaseBackup::STATUS_COMPLETED,
                'completed_at' => now(),
                'error_message' => null,
            ]);

            $this->copyToSyncTargetIfConfigured($absolutePath, $filename);
            $this->pruneExpiredBackups();
        } catch (\Throwable $e) {
            $backup->update([
                'status' => DatabaseBackup::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
            if (File::exists($absolutePath)) {
                File::delete($absolutePath);
            }

            throw $e;
        }

        return $backup->fresh();
    }

    public function restore(DatabaseBackup $backup): void
    {
        DestructiveDatabaseGuard::assertAllowedForDatabaseRestore();

        if ($backup->status !== DatabaseBackup::STATUS_COMPLETED || ! $backup->fileExists()) {
            throw new RuntimeException('Deze backup is niet beschikbaar voor herstel.');
        }

        $connection = $backup->connection ?: (string) config('database.default');
        $database = $backup->database_name ?: (string) config("database.connections.{$connection}.database");
        $path = $backup->absolutePath();

        if ($connection === 'sqlite') {
            $this->restoreSqlite($database, $path);

            return;
        }

        if ($connection === 'pgsql') {
            $this->restorePostgres($connection, $database, $path);

            return;
        }

        throw new RuntimeException('Herstel wordt alleen ondersteund voor PostgreSQL en SQLite.');
    }

    public function deleteBackup(DatabaseBackup $backup): void
    {
        if (Storage::disk('local')->exists($backup->disk_path)) {
            Storage::disk('local')->delete($backup->disk_path);
        }
        $backup->delete();
    }

    /**
     * @param  list<int>  $ids
     * @return array{deleted: int, skipped: int}
     */
    public function deleteBackups(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $deleted = 0;
        $skipped = 0;

        DatabaseBackup::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get()
            ->each(function (DatabaseBackup $backup) use (&$deleted, &$skipped): void {
                if ($backup->status === DatabaseBackup::STATUS_PENDING) {
                    $skipped++;

                    return;
                }

                $this->deleteBackup($backup);
                $deleted++;
            });

        return ['deleted' => $deleted, 'skipped' => $skipped];
    }

    public function pruneExpiredBackups(): int
    {
        $cutoff = now()->subDays($this->settings->retentionDays());
        $removed = 0;

        DatabaseBackup::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(20, function ($backups) use (&$removed): void {
                foreach ($backups as $backup) {
                    $this->deleteBackup($backup);
                    $removed++;
                }
            });

        return $removed;
    }

    private function backupSqlite(string $database, string $absolutePath): void
    {
        if ($database === ':memory:') {
            throw new RuntimeException('SQLite :memory: kan niet worden geback-upt.');
        }

        $source = $database;
        if (! str_starts_with($source, '/')) {
            $source = database_path($database);
        }

        if (! File::exists($source)) {
            throw new RuntimeException('SQLite-databasebestand niet gevonden: '.$source);
        }

        if (! copy($source, $absolutePath)) {
            throw new RuntimeException('Kon SQLite-database niet kopiëren.');
        }
    }

    private function restoreSqlite(string $database, string $absolutePath): void
    {
        $target = $database;
        if (! str_starts_with($target, '/')) {
            $target = database_path($database);
        }

        if (! copy($absolutePath, $target)) {
            throw new RuntimeException('Kon SQLite-database niet herstellen.');
        }
    }

    private function backupPostgresOrMysql(string $connection, string $database, string $absolutePath): void
    {
        if ($connection === 'pgsql') {
            $this->runPgDump($connection, $database, $absolutePath);

            return;
        }

        throw new RuntimeException('MySQL-backups worden nog niet ondersteund; gebruik PostgreSQL.');
    }

    private function restorePostgres(string $connection, string $database, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");
        $env = $this->databaseProcessEnv($config);
        $binary = $this->resolvePostgresBinary('pg_restore_binary', 'pg_restore');

        $command = [
            $binary,
            '--clean',
            '--if-exists',
            '--no-owner',
            '--no-acl',
            '--dbname='.$this->pgConnectionString($config, $database),
            $absolutePath,
        ];

        $result = Process::timeout(3600)->env($env)->run($command);
        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'pg_restore mislukt.'));
        }
    }

    private function runPgDump(string $connection, string $database, string $absolutePath): void
    {
        $config = config("database.connections.{$connection}");
        $env = $this->databaseProcessEnv($config);
        $binary = $this->resolvePostgresBinary('pg_dump_binary', 'pg_dump');

        $command = [
            $binary,
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--file='.$absolutePath,
            '--dbname='.$this->pgConnectionString($config, $database),
        ];

        $result = Process::timeout(3600)->env($env)->run($command);
        if (! $result->successful()) {
            throw new RuntimeException(trim($result->errorOutput() ?: $result->output() ?: 'pg_dump mislukt.'));
        }
    }

    private function resolvePostgresBinary(string $configKey, string $defaultName): string
    {
        $configured = trim((string) config("database_backup.{$configKey}", $defaultName));

        $candidates = array_values(array_unique(array_filter([
            $configured !== '' ? $configured : null,
            $defaultName,
            '/usr/bin/'.$defaultName,
            '/usr/local/bin/'.$defaultName,
        ])));

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '/') && is_executable($candidate)) {
                return $candidate;
            }

            $resolved = $this->resolveExecutableInPath($candidate);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        throw new RuntimeException(sprintf(
            '%s niet gevonden. Installeer postgresql-client in de backend-container (docker compose build backend && docker compose up -d backend) of zet %s in .env.',
            $defaultName,
            strtoupper($configKey)
        ));
    }

    private function resolveExecutableInPath(string $command): ?string
    {
        $result = Process::run(['sh', '-c', 'command -v '.escapeshellarg($command).' 2>/dev/null']);
        if (! $result->successful()) {
            return null;
        }

        $path = trim($result->output());
        if ($path === '' || ! is_executable($path)) {
            return null;
        }

        return $path;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private function databaseProcessEnv(array $config): array
    {
        $env = [];
        if (! empty($config['password'])) {
            $env['PGPASSWORD'] = (string) $config['password'];
        }

        return $env;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function pgConnectionString(array $config, string $database): string
    {
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (int) ($config['port'] ?? 5432);
        $user = (string) ($config['username'] ?? '');

        return sprintf('postgresql://%s@%s:%d/%s', rawurlencode($user), $host, $port, rawurlencode($database));
    }

    private function copyToSyncTargetIfConfigured(string $localPath, string $filename): void
    {
        $target = $this->settings->syncTarget();
        if ($target === null) {
            return;
        }

        try {
            $remoteDir = '/tmp/nexa-db-backups';
            $remotePath = $remoteDir.'/'.$filename;
            $config = $target->toConnectionConfig();

            if (! $config->sshEnabled) {
                Log::info('database_backup_offsite_skip', ['reason' => 'sync_target_without_ssh']);

                return;
            }

            app(TenantSyncSshTunnelService::class)->runIsolated(function () use ($config, $localPath, $remoteDir, $remotePath): void {
                $mkdir = $this->buildSshCommand($config, 'mkdir -p '.escapeshellarg($remoteDir));
                Process::timeout(600)->run($mkdir)->throw();
                Process::timeout(3600)->run($this->buildScpCommand($config, $localPath, $remotePath))->throw();
            }, $config);
        } catch (\Throwable $e) {
            Log::warning('database_backup_offsite_copy_failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * @return list<string>
     */
    private function buildScpCommand(TenantSyncConnectionConfig $config, string $localPath, string $remotePath): array
    {
        $user = $config->sshUsername;
        $host = $config->sshHost;
        $port = $config->sshPort ?: 22;

        return [
            'scp',
            '-P', (string) $port,
            '-o', 'StrictHostKeyChecking=accept-new',
            $localPath,
            sprintf('%s@%s:%s', $user, $host, $remotePath),
        ];
    }

    /**
     * @return list<string>
     */
    private function buildSshCommand(TenantSyncConnectionConfig $config, string $remoteCommand): array
    {
        return [
            'ssh',
            '-p', (string) ($config->sshPort ?: 22),
            '-o', 'StrictHostKeyChecking=accept-new',
            sprintf('%s@%s', $config->sshUsername, $config->sshHost),
            $remoteCommand,
        ];
    }
}

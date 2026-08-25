<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupSettingsService;
use Illuminate\Console\Command;

class RunScheduledDatabaseBackupCommand extends Command
{
    protected $signature = 'database:backup-scheduled';

    protected $description = 'Voer een geplande database-backup uit wanneer de instellingen dat voorschrijven.';

    public function handle(
        DatabaseBackupSettingsService $settings,
        DatabaseBackupService $backups,
    ): int {
        if (! $settings->isDueForScheduledRun()) {
            return self::SUCCESS;
        }

        try {
            $backup = $backups->createBackup('scheduled');
            $settings->markScheduledRunCompleted();
            $this->info('Backup voltooid: '.$backup->filename.' ('.$backup->humanSize().')');
        } catch (\Throwable $e) {
            $this->error('Geplande backup mislukt: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

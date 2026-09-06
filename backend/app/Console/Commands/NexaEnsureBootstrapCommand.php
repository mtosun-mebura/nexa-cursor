<?php

namespace App\Console\Commands;

use App\Services\CentralWelcomePageService;
use App\Services\ModuleSchemaService;
use Database\Seeders\ApplicationBootstrapSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Herstelt essentiële basisdata zonder bestaande data te wissen.
 */
class NexaEnsureBootstrapCommand extends Command
{
    protected $signature = 'nexa:ensure-bootstrap';

    protected $description = 'Zorg dat super-admin, rollen en centrale marketingpagina\'s aanwezig zijn (geen dataverlies, wachtwoorden blijven ongewijzigd).';

    public function handle(CentralWelcomePageService $central): int
    {
        $this->info('ApplicationBootstrapSeeder (rollen, super-admin, referentiedata) …');
        Artisan::call('db:seed', [
            '--class' => ApplicationBootstrapSeeder::class,
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));

        $this->info('Centrale marketingpagina\'s controleren …');
        $pages = $central->ensureMarketingPagesExist();
        $this->info($pages->count().' centrale pagina\'s aanwezig.');

        $this->newLine();
        $this->info('Klaar. Super-admin: '.ModuleSchemaService::SUPERADMIN_EMAIL);

        return self::SUCCESS;
    }
}

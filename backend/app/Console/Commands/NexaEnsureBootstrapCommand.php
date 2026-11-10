<?php

namespace App\Console\Commands;

use App\Services\CentralWelcomePageService;
use App\Services\ModuleSchemaService;
use App\Services\NexaTaxiWelcomePageService;
use Database\Seeders\ApplicationBootstrapSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Herstelt essentiële basisdata zonder bestaande data te wissen.
 */
class NexaEnsureBootstrapCommand extends Command
{
    protected $signature = 'nexa:ensure-bootstrap';

    protected $description = 'Zorg dat super-admin, rollen, centrale marketingpagina\'s en Nexa Taxi-tenantwebsite aanwezig zijn (geen dataverlies; bestaande wachtwoorden worden nooit gewijzigd).';

    public function handle(CentralWelcomePageService $central, NexaTaxiWelcomePageService $nexaTaxi): int
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

        $company = $nexaTaxi->resolveCompany();
        if ($company !== null) {
            $this->info('Nexa Taxi-tenantwebsite (nexataxi.nl) controleren …');
            $taxiPages = $nexaTaxi->ensureMarketingPagesExist($company);
            $this->info($taxiPages->count().' pagina\'s voor tenant '.$company->name.' (id '.$company->id.').');
        } else {
            $this->warn('Tenant "Nexa Taxi" niet gevonden — nexataxi.nl-pagina\'s overgeslagen.');
        }

        $this->newLine();
        $this->info('Klaar. Super-admin: '.ModuleSchemaService::SUPERADMIN_EMAIL);

        return self::SUCCESS;
    }
}

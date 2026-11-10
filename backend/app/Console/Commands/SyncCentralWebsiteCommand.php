<?php

namespace App\Console\Commands;

use App\Services\CentralWelcomePageService;
use App\Services\NexaTaxiWelcomePageService;
use Illuminate\Console\Command;

class SyncCentralWebsiteCommand extends Command
{
    protected $signature = 'nexa:sync-central-website
                            {--force : Overschrijf bestaande content van de centrale / Nexa Taxi-pagina\'s}
                            {--skip-taxi : Alleen NEXA Suite, geen tenant Nexa Taxi}';

    protected $description = 'Maak of (met --force) vul de NEXA Suite-hoofdwebsite en de Nexa Taxi-tenantwebsite (nexataxi.nl)';

    public function handle(CentralWelcomePageService $central, NexaTaxiWelcomePageService $nexaTaxi): int
    {
        if ($this->option('force')) {
            $pages = $central->syncDefaultContent();
            $this->info('Centrale website-content overschreven ('.$pages->count().' pagina\'s).');
        } else {
            $pages = $central->ensureMarketingPagesExist();
            $this->info('Centrale website-pagina\'s aanwezig ('.$pages->count().' pagina\'s). Bestaande content is niet overschreven.');
            $this->line('Gebruik --force om marketing-defaults opnieuw te zetten.');
        }

        foreach ($pages as $page) {
            $this->line('- '.$page->slug.' · '.$page->title);
        }

        if (! $this->option('skip-taxi')) {
            $company = $nexaTaxi->resolveCompany();
            if ($company === null) {
                $this->warn('Tenant "Nexa Taxi" niet gevonden — overgeslagen.');
            } elseif ($this->option('force')) {
                $taxiPages = $nexaTaxi->syncDefaultContent($company);
                $this->info('Nexa Taxi-tenantwebsite overschreven ('.$taxiPages->count().' pagina\'s).');
                foreach ($taxiPages as $page) {
                    $this->line('- [taxi] '.$page->slug.' · '.$page->title);
                }
            } else {
                $taxiPages = $nexaTaxi->ensureMarketingPagesExist($company);
                $this->info('Nexa Taxi-tenantwebsite aanwezig ('.$taxiPages->count().' pagina\'s voor '.$company->name.').');
                foreach ($taxiPages as $page) {
                    $this->line('- [taxi] '.$page->slug.' · '.$page->title);
                }
            }
        }

        return self::SUCCESS;
    }
}

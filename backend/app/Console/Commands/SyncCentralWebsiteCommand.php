<?php

namespace App\Console\Commands;

use App\Services\CentralWelcomePageService;
use Illuminate\Console\Command;

class SyncCentralWebsiteCommand extends Command
{
    protected $signature = 'nexa:sync-central-website
                            {--force : Overschrijf bestaande content van de centrale pagina\'s}';

    protected $description = 'Maak of (met --force) vul de Nexa SaaS-hoofdwebsite (home, taxi, contractvervoer, website, contact)';

    public function handle(CentralWelcomePageService $central): int
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

        return self::SUCCESS;
    }
}
